<?php

namespace App\Http\Controllers\Saturnus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\FormItem;
use App\Models\UnregistrasiItem;
use App\Models\User;
use App\Models\FormApproval;
use App\Models\FormComment;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    /**
     * Display a listing of the items and dashboard statistics.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $category = $request->input('category');

        // Statistics
        $stats = [
            'total' => Item::count(),
            'registered' => Item::where('status', 'registered')->count(),
            'unregistered' => Item::where('status', 'unregistered')->count(),
            'consumables' => Item::where('category', 'Consumable')->count(),
        ];

        // Base Query
        $query = Item::query();

        // Apply Search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply Status Filter
        if (in_array($status, ['registered', 'unregistered'])) {
            $query->where('status', $status);
        }

        // Apply Category Filter
        if ($category) {
            $query->where('category', $category);
        }

        // Fetch paginated results
        $items = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Get unique categories for the filter dropdown
        $categories = Item::select('category')->distinct()->pluck('category')->toArray();
        if (empty($categories)) {
            $categories = ['Consumable', 'Asset', 'Electronics', 'Office Stationery', 'Others'];
        }

        return view('saturnus.dashboard', compact('items', 'stats', 'categories', 'search', 'status', 'category'));
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        // Auto-generate code: BRG-YYYYMMDD-XXXX
        $today = now()->format('Ymd');
        
        // Find the latest item today to get the next sequential number
        $latestItem = Item::where('item_code', 'like', "BRG-{$today}-%")
            ->orderBy('item_code', 'desc')
            ->first();

        $nextNumber = 1;
        if ($latestItem) {
            // Extract sequence number
            $parts = explode('-', $latestItem->item_code);
            if (count($parts) === 3) {
                $nextNumber = intval($parts[2]) + 1;
            }
        }

        $itemCode = 'BRG-' . $today . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        Item::create([
            'item_code' => $itemCode,
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'],
            'status' => 'registered',
            'registered_at' => now(),
        ]);

        return redirect()->route('saturnus.dashboard')->with('success', 'Barang "' . $validated['name'] . '" berhasil didaftarkan dengan kode: ' . $itemCode);
    }

    /**
     * Unregister an item.
     */
    public function unregister(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        if ($item->status === 'unregistered') {
            return redirect()->route('saturnus.dashboard')->with('error', 'Barang ini sudah berstatus unregistrasi.');
        }

        $validated = $request->validate([
            'unregistration_reason' => 'required|string|min:5|max:1000',
        ], [
            'unregistration_reason.required' => 'Alasan pembatalan registrasi wajib diisi.',
            'unregistration_reason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        $item->update([
            'status' => 'unregistered',
            'unregistered_at' => now(),
            'unregistration_reason' => $validated['unregistration_reason'],
        ]);

        return redirect()->route('saturnus.dashboard')->with('success', 'Registrasi barang "' . $item->name . '" berhasil dibatalkan (unregistered).');
    }

    /**
     * Clean up any orphan FormApproval or FormComment records whose form_number no longer exists in FormItem.
     */
    private function cleanupOrphanFormRecords(): void
    {
        $allActiveFormNumbers = FormItem::pluck('form_number')->filter()->unique()->toArray();
        FormApproval::whereNotIn('form_number', $allActiveFormNumbers)->delete();
        FormComment::whereNotIn('form_number', $allActiveFormNumbers)->delete();
    }

    /**
     * Get list of allowed departments for a given user.
     *
     * @param  \App\Models\User  $user
     * @return array<string>
     */
    public function getUserAllowedDepartments($user): array
    {
        $userDept = strtoupper(trim($user->department ?? ''));
        $userRole = strtoupper(trim($user->role ?? ''));

        // If department is 'Production / Dies Assy' or role contains both 'PRODUCTION' and 'DIES ASSY'
        if (
            (str_contains($userDept, 'PRODUCTION') && str_contains($userDept, 'DIES ASSY'))
            || (str_contains($userRole, 'PRODUCTION') && str_contains($userRole, 'DIES ASSY'))
            || $userDept === 'PRODUCTION / DIES ASSY'
            || $userDept === 'PRODUCTION/DIES ASSY'
        ) {
            return ['PRODUCTION', 'DIES ASSY', 'DIESASSY', 'DIES-ASSY', 'PRODUCTION / DIES ASSY', 'PRODUCTION/DIES ASSY'];
        }

        if (str_contains($userDept, '/')) {
            $splits = array_map('trim', explode('/', $userDept));
            return array_values(array_filter($splits));
        }

        return $userDept ? [$userDept] : ['PRODUCTION'];
    }

    /**
     * Check if a form's department is allowed for a user.
     *
     * @param  \App\Models\User  $user
     * @param  string|null       $formDept
     * @return bool
     */
    public function isDepartmentAllowed($user, ?string $formDept): bool
    {
        if (empty($formDept)) {
            return true;
        }
        $userRole = strtoupper(trim($user->role ?? ''));
        if (
            in_array($userRole, ['MASTER', 'ADMIN'])
            || str_contains($userRole, 'ACCOUNTING')
            || str_contains($userRole, 'ACC')
            || str_contains($userRole, 'WAREHOUSE')
        ) {
            return true;
        }

        $formDeptUpper = strtoupper(trim($formDept));
        $allowed = $this->getUserAllowedDepartments($user);

        foreach ($allowed as $a) {
            if ($formDeptUpper === $a || str_contains($formDeptUpper, $a) || str_contains($a, $formDeptUpper)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare shared data for Saturnus form registration pages.
     */
    private function getRegistrasiFormData(Request $request): array
    {
        $currentUser = auth()->user();
        $userRole = strtoupper(trim($currentUser->role ?? 'USER'));

        $this->cleanupOrphanFormRecords();
        $allExistingItems = FormItem::with('user')->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get();
        $users = User::orderBy('id', 'asc')->get();

        // Auto-create FormApproval record for each unique form_number in form_items if not exists
        $distinctForms = $allExistingItems->pluck('form_number')->filter()->unique();
        foreach ($distinctForms as $fNo) {
            $firstItem = $allExistingItems->firstWhere('form_number', $fNo);
            $creator = $firstItem?->user;
            $parts = explode('/', $fNo);
            $fDept = (count($parts) >= 2 && !empty($parts[1])) ? $parts[1] : null;
            $reqDept = $firstItem?->created_by_dept ?? $creator?->department ?? $fDept ?? ($currentUser->department ?? 'Production');

            FormApproval::firstOrCreate(
                ['form_number' => $fNo],
                [
                    'user_id'          => $creator?->id ?? $currentUser->id,
                    'requestor_name'   => $firstItem?->created_by_name ?? $creator?->name ?? ($currentUser->name ?? 'User'),
                    'requestor_dept'   => $reqDept,
                    'form_date'        => $firstItem?->created_at ? $firstItem->created_at->format('d-m-Y') : date('d-m-Y'),
                    'status'           => 'Butuh Approval Staff / Section Head',
                    'user_signed_at'   => $firstItem?->created_at ?? now(),
                    'user_signer_name' => $firstItem?->created_by_name ?? ($currentUser->name ?? 'User'),
                    'user_comment'     => 'Formulir pendaftaran diajukan.',
                ]
            );
        }

        // Unrestricted roles can see all forms: Master/Admin, Accounting, Warehouse Consumable
        $canViewAllDepartments = in_array($userRole, ['MASTER', 'ADMIN'])
            || str_contains($userRole, 'ACCOUNTING')
            || str_contains($userRole, 'ACC')
            || str_contains($userRole, 'WAREHOUSE');

        if ($canViewAllDepartments) {
            $formItems = $allExistingItems;
            $formApprovals = FormApproval::with('user')->get();
            $formComments = FormComment::with('user')->orderBy('created_at', 'asc')->get();
        } else {
            // Restricted roles (User, Staff): only see forms created by their allowed department(s)
            $formItems = $allExistingItems->filter(function($item) use ($currentUser) {
                $itemDept = strtoupper(trim($item->created_by_dept ?? $item->user?->department ?? ''));
                if ($this->isDepartmentAllowed($currentUser, $itemDept)) {
                    return true;
                }
                if ($item->form_number && str_contains($item->form_number, '/')) {
                    $parts = explode('/', $item->form_number);
                    if (isset($parts[1]) && $this->isDepartmentAllowed($currentUser, $parts[1])) {
                        return true;
                    }
                }
                return false;
            })->values();

            $formApprovals = FormApproval::with('user')->get()->filter(function($approval) use ($currentUser) {
                $apprDept = strtoupper(trim($approval->requestor_dept ?? $approval->user?->department ?? ''));
                if ($this->isDepartmentAllowed($currentUser, $apprDept)) {
                    return true;
                }
                if ($approval->form_number && str_contains($approval->form_number, '/')) {
                    $parts = explode('/', $approval->form_number);
                    if (isset($parts[1]) && $this->isDepartmentAllowed($currentUser, $parts[1])) {
                        return true;
                    }
                }
                return false;
            })->values();

            $allComments = FormComment::with('user')->orderBy('created_at', 'asc')->get();
            $formComments = $allComments->filter(function($c) use ($currentUser) {
                if ($this->isDepartmentAllowed($currentUser, $c->user_dept)) {
                    return true;
                }
                if ($c->form_number && str_contains($c->form_number, '/')) {
                    $parts = explode('/', $c->form_number);
                    if (isset($parts[1]) && $this->isDepartmentAllowed($currentUser, $parts[1])) {
                        return true;
                    }
                }
                return false;
            })->values();
        }

        $activeFormNoParam = $request->query('form');

        // If restricted user attempts to access a form parameter of another department, reset it
        if (!$canViewAllDepartments && $activeFormNoParam) {
            $parts = explode('/', $activeFormNoParam);
            $targetDept = isset($parts[1]) ? strtoupper(trim($parts[1])) : '';
            if ($targetDept && !$this->isDepartmentAllowed($currentUser, $targetDept)) {
                $activeFormNoParam = null;
            }
        }

        $rawRegistered = FormItem::select('kode_barang', 'nama_barang', 'form_number', 'created_by_dept')->whereNotNull('kode_barang')->get();
        $rawUnregistered = UnregistrasiItem::select('kode_barang', 'nama_barang', 'form_number', 'created_by_dept')->whereNotNull('kode_barang')->get();

        if ($canViewAllDepartments) {
            $allRegisteredCodes = $rawRegistered;
            $allUnregisteredCodes = $rawUnregistered;
        } else {
            $allRegisteredCodes = $rawRegistered->filter(function($i) use ($currentUser) {
                return $this->isDepartmentAllowed($currentUser, $i->created_by_dept ?? '');
            })->values();

            $allUnregisteredCodes = $rawUnregistered->filter(function($i) use ($currentUser) {
                return $this->isDepartmentAllowed($currentUser, $i->created_by_dept ?? '');
            })->values();
        }

        return compact(
            'formItems',
            'users',
            'formApprovals',
            'activeFormNoParam',
            'formComments',
            'allRegisteredCodes',
            'allUnregisteredCodes'
        );
    }

    /**
     * Display the Form Pendaftaran Barang Consumable page (Lembar Cetak).
     */
    public function formRegistrasi(Request $request)
    {
        $data = $this->getRegistrasiFormData($request);
        return view('saturnus.form_registrasi', $data);
    }

    /**
     * Display the Proses Approval view directly.
     */
    public function prosesApproval(Request $request)
    {
        $data = $this->getRegistrasiFormData($request);
        return view('saturnus.proses_approval', $data);
    }

    /**
     * Display the Data View Explorer directly.
     */
    public function dataView(Request $request)
    {
        $data = $this->getRegistrasiFormData($request);
        return view('saturnus.data_view', $data);
    }

    /**
     * Store a new form item entry.
     */
    public function storeFormItem(Request $request)
    {
        // Handle B3 / NON B3 Category
        $kategoriSelection = $request->input('kategori_b3');
        $isB3 = false;
        $isNonB3 = false;

        if ($kategoriSelection === 'b3' || $request->has('is_b3')) {
            $isB3 = true;
            $isNonB3 = false;
        } elseif ($kategoriSelection === 'non_b3' || $request->has('is_non_b3')) {
            $isB3 = false;
            $isNonB3 = true;
        } else {
            // Default to NON B3 if not specified
            $isNonB3 = true;
        }

        $validated = $request->validate([
            'form_number'        => 'nullable|string|max:100',
            'kode_barang'        => 'required|string|max:100',
            'nama_barang'        => 'required|string|max:255',
            'harga'              => 'required|numeric|min:0',
            'estimasi_usia_pakai'=> 'required|string|max:100',
            'kategori_penggunaan'=> 'required|string|max:100',
            'kategori_ukuran'    => 'required|string|max:100',
            'min'                => 'required|integer|min:0',
            'titik_order'        => 'required|integer|min:0',
            'max'                => 'required|integer|min:0',
            'lead_time'          => 'required|string|max:100',
            'is_b3'              => 'nullable',
            'is_non_b3'          => 'nullable',
            'kategori_b3'        => 'nullable|string',
        ], [
            'kode_barang.required'         => 'Kode barang wajib diisi dan tidak boleh kosong.',
            'nama_barang.required'         => 'Nama barang wajib diisi.',
            'harga.required'               => 'Harga wajib diisi.',
            'harga.numeric'                => 'Harga harus berupa angka.',
            'estimasi_usia_pakai.required' => 'Estimasi usia pakai wajib diisi.',
            'kategori_penggunaan.required' => 'Kategori penggunaan wajib diisi.',
            'kategori_ukuran.required'     => 'Kategori ukuran wajib diisi.',
            'min.required'                 => 'Nilai Min stok wajib diisi.',
            'titik_order.required'         => 'Nilai Titik Order wajib diisi.',
            'max.required'                 => 'Nilai Max stok wajib diisi.',
            'lead_time.required'           => 'Lead time pengadaan wajib diisi.',
        ]);

        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? 'USER'));
        $currentUserDept = strtoupper(trim($currentUser->department ?? 'PRODUCTION'));
        $canViewAllDepartments = in_array($currentUserRole, ['MASTER', 'ADMIN'])
            || str_contains($currentUserRole, 'ACCOUNTING')
            || str_contains($currentUserRole, 'ACC')
            || str_contains($currentUserRole, 'WAREHOUSE');

        $kodeBarangInput = trim($validated['kode_barang']);

        // Duplicate checks on kode_barang
        $existingUnreg = UnregistrasiItem::where('kode_barang', $kodeBarangInput)->latest()->first();
        if ($existingUnreg) {
            $isAllowedDept = $canViewAllDepartments || $this->isDepartmentAllowed($currentUser, $existingUnreg->created_by_dept ?? '');
            $formRef = $isAllowedDept ? "pada Form Unregistrasi {$existingUnreg->form_number}" : "pada sistem";
            $nameRef = ($isAllowedDept && !empty($existingUnreg->nama_barang)) ? " ({$existingUnreg->nama_barang})" : "";
            $msg = "Peringatan: Kode barang '{$kodeBarangInput}'{$nameRef} telah di-discontinue sebelumnya {$formRef}!";
            return back()->withErrors(['kode_barang' => $msg])->withInput()->with('error', $msg);
        }

        $targetForm = $request->input('form_number');
        if (!$canViewAllDepartments && $targetForm) {
            $parts = explode('/', $targetForm);
            $targetDept = isset($parts[1]) ? strtoupper(trim($parts[1])) : '';
            if ($targetDept && !$this->isDepartmentAllowed($currentUser, $targetDept)) {
                $targetForm = null;
            }
        }

        if (empty($targetForm)) {
            $monthYear = date('m-Y');
            $defaultDept = (str_contains($currentUserDept, 'PRODUCTION') && str_contains($currentUserDept, 'DIES ASSY')) ? 'PRODUCTION' : $currentUserDept;
            $targetForm = "01/{$defaultDept}/{$monthYear}";
        }

        // Formulir yang sudah berhasil dibuat tidak boleh ditambahkan item lagi
        if (FormItem::where('form_number', $targetForm)->exists()) {
            $msg = "Formulir {$targetForm} sudah berhasil dibuat. Penambahan item ke formulir yang sudah ada tidak diperbolehkan. Silakan klik '+ Form Baru'.";
            return back()->withErrors(['form_number' => $msg])->withInput()->with('error', $msg);
        }

        $validated['kode_barang']     = $kodeBarangInput;
        $validated['form_number']     = $targetForm;
        $validated['is_b3']           = $isB3;
        $validated['is_non_b3']       = $isNonB3;
        $validated['user_id']         = $currentUser->id;
        $validated['created_by_name'] = $currentUser->name ?? 'User';
        $validated['created_by_dept'] = $currentUser->department ?? 'Production';

        FormItem::create($validated);

        // Ensure FormApproval exists for the targetForm
        if ($targetForm) {
            FormApproval::firstOrCreate(
                ['form_number' => $targetForm],
                [
                    'user_id'          => $currentUser->id,
                    'requestor_name'   => $currentUser->name ?? 'User',
                    'requestor_dept'   => $currentUser->department ?? 'Production',
                    'form_date'        => date('d-m-Y'),
                    'status'           => 'Butuh Approval Staff / Section Head',
                    'user_signed_at'   => now(),
                    'user_signer_name' => $currentUser->name ?? 'User',
                    'user_comment'     => 'Formulir pendaftaran diajukan.',
                ]
            );
        }

        $redirectParams = $targetForm ? ['form' => $targetForm] : [];

        return redirect()->route('saturnus.form_registrasi', $redirectParams)
            ->with('success', 'Formulir ' . $targetForm . ' untuk barang "' . $validated['nama_barang'] . '" berhasil dibuat.');
    }

    /**
     * Handle approval submission for a form with real database persistence.
     */
    public function approveForm(Request $request)
    {
        $request->validate([
            'form_number' => 'required|string',
            'role'        => 'required|string|in:staff,accounting,warehouse,user',
            'name'        => 'required|string|max:255',
            'comment'     => 'nullable|string|max:1000',
        ]);

        $formNo = $request->input('form_number');
        $role = strtolower($request->input('role'));
        $name = trim($request->input('name'));
        $comment = trim($request->input('comment')) ?: 'Disetujui.';

        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? ''));
        $isMaster = in_array($currentUserRole, ['MASTER', 'ADMIN']);

        $firstItem = FormItem::where('form_number', $formNo)->first();
        $parts = explode('/', $formNo);
        $inferredDept = (count($parts) >= 2 && !empty($parts[1])) ? strtoupper(trim($parts[1])) : null;
        $reqDept = $firstItem?->created_by_dept ?? $inferredDept ?? ($currentUser->department ?? 'Production');
        $reqName = $firstItem?->created_by_name ?? ($currentUser->name ?? 'User');
        $reqUserId = $firstItem?->user_id ?? $currentUser->id;

        $approval = FormApproval::firstOrCreate(
            ['form_number' => $formNo],
            [
                'user_id'          => $reqUserId,
                'requestor_name'   => $reqName,
                'requestor_dept'   => $reqDept,
                'form_date'        => $firstItem?->created_at ? $firstItem->created_at->format('d-m-Y') : date('d-m-Y'),
                'status'           => 'Butuh Approval Staff / Section Head',
                'user_signed_at'   => now(),
                'user_signer_name' => $reqName,
                'user_comment'     => 'Formulir diajukan.',
            ]
        );

        $msg = 'Status persetujuan berhasil diperbarui.';

        if ($role === 'staff') {
            if (!$isMaster && !str_contains($currentUserRole, 'STAFF')) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Akses Ditolak: Hanya Role Staff atau Master yang dapat menyetujui tahap ini.'], 403);
                }
                return back()->with('error', 'Akses Ditolak: Hanya Role Staff atau Master yang dapat menyetujui tahap ini.');
            }

            // Department authorization for Staff: Staff can only approve forms from their allowed department(s)
            if (!$isMaster) {
                $parts = explode('/', $formNo);
                $formDept = (count($parts) >= 2 && !empty($parts[1]))
                    ? strtoupper(trim($parts[1]))
                    : strtoupper(trim($approval->requestor_dept ?? ''));

                if ($formDept && !$this->isDepartmentAllowed($currentUser, $formDept)) {
                    $userDeptName = $currentUser->department ?? 'Anda';
                    $errMsg = "Akses Ditolak: Anda login sebagai Staff Departemen {$userDeptName}. Anda hanya berwenang menyetujui formulir dari departemen Anda sendiri (Form {$formNo} berasal dari Departemen {$formDept}).";
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json(['success' => false, 'message' => $errMsg], 403);
                    }
                    return back()->with('error', $errMsg);
                }
            }

            $approval->staff_signed_at = now();
            $approval->staff_signer_name = $name;
            $approval->staff_comment = $comment;
            $approval->status = 'Butuh Approval Accounting';
            $approval->save();

            $msg = "Form $formNo berhasil disetujui oleh Staff ($name). Tahap selanjutnya: Butuh Approval Accounting.";
        } elseif ($role === 'accounting') {
            if (!$isMaster && !str_contains($currentUserRole, 'ACC')) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Akses Ditolak: Hanya Role Accounting atau Master yang dapat menyetujui tahap ini.'], 403);
                }
                return back()->with('error', 'Akses Ditolak: Hanya Role Accounting atau Master yang dapat menyetujui tahap ini.');
            }

            if (!$approval->staff_signed_at && !$isMaster) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Akses Ditolak: Tahap Staff harus disetujui terlebih dahulu.'], 422);
                }
                return back()->with('error', 'Akses Ditolak: Tahap Staff harus disetujui terlebih dahulu.');
            }

            $approval->accounting_signed_at = now();
            $approval->accounting_signer_name = $name;
            $approval->accounting_comment = $comment;
            $approval->status = 'Butuh Approval Warehouse Consumable';
            $approval->save();

            $msg = "Form $formNo berhasil disetujui oleh Accounting ($name). Tahap selanjutnya: Butuh Approval Warehouse Consumable.";
        } elseif ($role === 'warehouse') {
            if (!$isMaster && !str_contains($currentUserRole, 'WAREHOUSE')) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Akses Ditolak: Hanya Role Warehouse Consumable atau Master yang dapat menyelesaikan registrasi ini.'], 403);
                }
                return back()->with('error', 'Akses Ditolak: Hanya Role Warehouse Consumable atau Master yang dapat menyelesaikan registrasi ini.');
            }

            if (!$approval->accounting_signed_at && !$isMaster) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Akses Ditolak: Tahap Accounting harus disetujui terlebih dahulu.'], 422);
                }
                return back()->with('error', 'Akses Ditolak: Tahap Accounting harus disetujui terlebih dahulu.');
            }

            $approval->warehouse_signed_at = now();
            $approval->warehouse_signer_name = $name;
            $approval->warehouse_comment = $comment;
            $approval->status = 'Item Telah didaftarkan';
            $approval->save();

            $msg = "Form $formNo telah berhasil diregistrasi oleh Warehouse Consumable ($name). Proses Selesai.";
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => $msg,
                'approval' => $approval,
            ]);
        }

        return redirect()->route('saturnus.proses_approval', ['form' => $formNo])
            ->with('success', $msg);
    }

    /**
     * Delete an entire form / checksheet permanently.
     */
    public function deleteFormChecksheet(Request $request)
    {
        $formNo = $request->input('form_number');
        if (!$formNo) {
            return redirect()->route('saturnus.data_view')->with('error', 'Form number tidak valid.');
        }

        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? ''));
        $isMaster = in_array($currentUserRole, ['MASTER', 'ADMIN']);

        // Only Master / Admin can delete form checksheet
        if (!$isMaster) {
            return redirect()->route('saturnus.data_view')
                ->with('error', 'Akses ditolak: Hanya Role Master yang memiliki wewenang untuk menghapus form registrasi.');
        }

        FormItem::where('form_number', $formNo)->forceDelete();
        FormApproval::where('form_number', $formNo)->delete();
        FormComment::where('form_number', $formNo)->delete();
        $this->cleanupOrphanFormRecords();

        return redirect()->route('saturnus.data_view')
            ->with('success', 'Formulir "' . $formNo . '" berhasil dihapus secara permanen.');
    }

    /**
     * Delete a form item entry.
     */
    public function deleteFormItem($id)
    {
        $item = FormItem::findOrFail($id);
        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? ''));
        $isMaster = in_array($currentUserRole, ['MASTER', 'ADMIN']);

        if (!$isMaster) {
            $itemDept = strtoupper(trim($item->created_by_dept ?? $item->user?->department ?? ''));
            if (!$itemDept && str_contains($item->form_number, '/')) {
                $parts = explode('/', $item->form_number);
                $itemDept = isset($parts[1]) ? strtoupper(trim($parts[1])) : '';
            }
            if ($itemDept && !$this->isDepartmentAllowed($currentUser, $itemDept)) {
                return redirect()->route('saturnus.form_registrasi')
                    ->with('error', 'Akses ditolak: Anda tidak memiliki wewenang menghapus barang dari departemen lain.');
            }
        }

        $name = $item->nama_barang;
        $targetForm = $item->form_number;
        $item->forceDelete();

        if (FormItem::where('form_number', $targetForm)->count() === 0) {
            FormApproval::where('form_number', $targetForm)->delete();
            FormComment::where('form_number', $targetForm)->delete();
            $firstItem = FormItem::orderBy('created_at', 'asc')->first();
            $targetForm = $firstItem ? $firstItem->form_number : null;
        }

        $this->cleanupOrphanFormRecords();

        $redirectParams = $targetForm ? ['form' => $targetForm] : [];

        return redirect()->route('saturnus.form_registrasi', $redirectParams)
            ->with('success', 'Data "' . $name . '" berhasil dihapus.');
    }

    /**
     * Store a comment for a form checksheet. All authenticated roles can post comments.
     */
    public function storeComment(Request $request)
    {
        $request->validate([
            'form_number' => 'required|string',
            'comment'     => 'required|string|min:1|max:2000',
        ], [
            'comment.required' => 'Komentar tidak boleh kosong.',
            'comment.max'      => 'Komentar maksimal 2000 karakter.',
        ]);

        $formNo = $request->input('form_number');
        $commentText = trim($request->input('comment'));
        $currentUser = auth()->user();

        $userRole = strtoupper(trim($currentUser->role ?? 'USER'));
        $canViewAllDepartments = in_array($userRole, ['MASTER', 'ADMIN'])
            || str_contains($userRole, 'ACCOUNTING')
            || str_contains($userRole, 'ACC')
            || str_contains($userRole, 'WAREHOUSE');

        if (!$canViewAllDepartments) {
            $parts = explode('/', $formNo);
            $formDept = (count($parts) >= 2 && !empty($parts[1])) ? strtoupper(trim($parts[1])) : '';
            if ($formDept && !$this->isDepartmentAllowed($currentUser, $formDept)) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Akses ditolak untuk formulir departemen lain.'], 403);
                }
                return back()->with('error', 'Akses ditolak untuk formulir departemen lain.');
            }
        }

        $comment = FormComment::create([
            'form_number' => $formNo,
            'user_id'     => $currentUser->id,
            'user_name'   => $currentUser->name ?? 'User',
            'user_dept'   => $currentUser->department ?? 'Production',
            'user_role'   => $currentUser->role ?? 'User',
            'comment'     => $commentText,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Komentar berhasil ditambahkan.',
                'comment' => [
                    'id'             => $comment->id,
                    'form_number'    => $comment->form_number,
                    'user_id'        => $comment->user_id,
                    'user_name'      => $comment->user_name,
                    'user_dept'      => $comment->user_dept,
                    'user_role'      => $comment->user_role,
                    'comment'        => $comment->comment,
                    'created_at_raw' => $comment->created_at?->toISOString() ?? now()->toISOString(),
                    'created_at'     => $comment->created_at ? $comment->created_at->setTimezone('Asia/Jakarta')->format('d-m-Y H:i') : now('Asia/Jakarta')->format('d-m-Y H:i'),
                    'can_delete'     => in_array(strtoupper(trim($currentUser->role ?? '')), ['MASTER', 'ADMIN']),
                ],
            ]);
        }

        return redirect()->route('saturnus.form_registrasi', ['form' => $formNo])
            ->with('success', 'Komentar berhasil ditambahkan.');
    }

    /**
     * Delete a comment. Only Master/Admin role can delete comments.
     */
    public function deleteComment(Request $request, $id)
    {
        $comment = FormComment::findOrFail($id);
        $currentUser = auth()->user();
        $userRole = strtoupper(trim($currentUser->role ?? 'USER'));
        $isMaster = in_array($userRole, ['MASTER', 'ADMIN']);

        if (!$isMaster) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Akses Ditolak: Hanya Role Master yang memiliki hak untuk menghapus komentar.'], 403);
            }
            return back()->with('error', 'Akses Ditolak: Hanya Role Master yang memiliki hak untuk menghapus komentar.');
        }

        $formNo = $comment->form_number;
        $comment->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Komentar berhasil dihapus.',
            ]);
        }

        return redirect()->route('saturnus.form_registrasi', ['form' => $formNo])
            ->with('success', 'Komentar berhasil dihapus.');
    }
}
