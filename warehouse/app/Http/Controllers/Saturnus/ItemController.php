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
use Illuminate\Support\Facades\Auth;

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

        $stats = [
            'total' => Item::count(),
            'registered' => Item::where('status', 'registered')->count(),
            'unregistered' => Item::where('status', 'unregistered')->count(),
            'consumables' => Item::where('category', 'Consumable')->count(),
        ];

        $query = Item::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['registered', 'unregistered'])) {
            $query->where('status', $status);
        }

        if ($category) {
            $query->where('category', $category);
        }

        $items = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

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
        $this->abortIfGuest();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $today = now()->format('Ymd');
        
        $latestItem = Item::where('item_code', 'like', "BRG-{$today}-%")
            ->orderBy('item_code', 'desc')
            ->first();

        $nextNumber = 1;
        if ($latestItem) {
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
        $this->abortIfGuest();

        $item = Item::findOrFail($id);

        if ($item->status === 'unregistered') {
            return redirect()->route('saturnus.dashboard')->with('error', 'Barang ini sudah berstatus unregistrasi.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'Alasan unregistrasi wajib diisi.',
        ]);

        $item->update([
            'status' => 'unregistered',
            'unregistered_at' => now(),
            'unregistration_reason' => $validated['reason'],
        ]);

        return redirect()->route('saturnus.dashboard')->with('success', 'Barang "' . $item->name . '" (' . $item->item_code . ') berhasil di-unregistrasi.');
    }

    private function cleanupOrphanFormRecords(): void
    {
        $existingFormNumbers = FormItem::pluck('form_number')->filter()->unique()->toArray();
        if (empty($existingFormNumbers)) {
            FormApproval::query()->delete();
            FormComment::query()->delete();
        } else {
            FormApproval::whereNotIn('form_number', $existingFormNumbers)->delete();
            FormComment::whereNotIn('form_number', $existingFormNumbers)->delete();
        }
    }

    public function getUserAllowedDepartments($user): array
    {
        $userDept = strtoupper(trim($user->department ?? ''));
        $userRole = strtoupper(trim($user->role ?? ''));

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
     * Display Form Registrasi page.
     */
    public function formRegistrasi(Request $request)
    {
        $activeTab = $request->query('tab');
        $currentUser = auth()->user();
        $userRole = strtoupper(trim($currentUser->role ?? 'USER'));

        if ($activeTab === 'account-master' && !in_array($userRole, ['MASTER', 'ADMIN'])) {
            return redirect()->route('saturnus.form_registrasi')->with('error', 'Akses ditolak. Fitur Account Master hanya dapat diakses oleh Role Master.');
        }

        $this->cleanupOrphanFormRecords();
        $allExistingItems = FormItem::with('user')->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get();
        $users = User::orderBy('id', 'asc')->get();

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

        $canViewAllDepartments = in_array($userRole, ['MASTER', 'ADMIN'])
            || str_contains($userRole, 'ACCOUNTING')
            || str_contains($userRole, 'ACC')
            || str_contains($userRole, 'WAREHOUSE');

        if ($canViewAllDepartments) {
            $formItems = $allExistingItems;
            $formApprovals = FormApproval::with('user')->get();
            $formComments = FormComment::with('user')->orderBy('created_at', 'asc')->get();
        } else {
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

        return view('saturnus.form_registrasi', compact(
            'formItems',
            'users',
            'formApprovals',
            'activeFormNoParam',
            'formComments',
            'allRegisteredCodes',
            'allUnregisteredCodes',
            'activeTab'
        ));
    }

    /**
     * Store item in Form Registrasi.
     */
    public function storeFormItem(Request $request)
    {
        $this->abortIfGuest();

        $validated = $request->validate([
            'nama_barang'         => 'required|string|max:255',
            'harga'               => 'nullable|numeric|min:0',
            'estimasi_usia_pakai' => 'nullable|string|max:100',
            'kategori_penggunaan' => 'nullable|string|max:100',
            'kategori_ukuran'     => 'nullable|string|max:100',
            'min'                 => 'nullable|integer|min:0',
            'titik_order'         => 'nullable|integer|min:0',
            'max'                 => 'nullable|integer|min:0',
            'lead_time'           => 'nullable|string|max:100',
            'form_number'         => 'nullable|string|max:100',
            'form_action_type'    => 'nullable|string|in:new_form,add_item',
        ]);

        $user = auth()->user();
        $userTag = strtoupper($user->department ?? $user->name ?? 'PRODUCTION');
        if (str_contains($userTag, 'PRODUCTION') && str_contains($userTag, 'DIES ASSY')) {
            $userTag = 'PRODUCTION';
        }

        $defaultFormNo = '01/' . $userTag . '/' . date('m-Y');
        $actionType = $request->input('form_action_type', 'add_item');
        $formNumber = trim($validated['form_number'] ?? '');

        if ($actionType === 'new_form' || empty($formNumber)) {
            $lastTodayItem = FormItem::where('form_number', 'like', "%/{$userTag}/" . date('m-Y'))
                ->orderBy('form_number', 'desc')
                ->first();

            $nextSeq = 1;
            if ($lastTodayItem && preg_match('/^(\d+)\//', $lastTodayItem->form_number, $matches)) {
                $nextSeq = intval($matches[1]) + 1;
            }

            $formNumber = str_pad($nextSeq, 2, '0', STR_PAD_LEFT) . '/' . $userTag . '/' . date('m-Y');
        }

        $approval = FormApproval::where('form_number', $formNumber)->first();
        if ($approval && $approval->status === 'SELESAI (SUDAH TERDAFTAR)') {
            return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])
                ->with('error', 'Formulir ini sudah berstatus SELESAI dan tidak dapat ditambahkan item baru.');
        }

        $kategoriPenggunaan = $request->input('kategori_penggunaan');
        $isB3 = ($kategoriPenggunaan === 'B3');
        $isNonB3 = ($kategoriPenggunaan === 'NON B3');

        $item = FormItem::create([
            'form_number'         => $formNumber,
            'user_id'             => $user->id,
            'created_by_name'     => $user->name,
            'created_by_dept'     => $user->department ?? 'Production',
            'nama_barang'         => $validated['nama_barang'],
            'harga'               => $validated['harga'] ?? null,
            'estimasi_usia_pakai' => $validated['estimasi_usia_pakai'] ?? null,
            'kategori_penggunaan' => $kategoriPenggunaan,
            'kategori_ukuran'     => $validated['kategori_ukuran'] ?? null,
            'min'                 => $validated['min'] ?? null,
            'titik_order'         => $validated['titik_order'] ?? null,
            'max'                 => $validated['max'] ?? null,
            'lead_time'           => $validated['lead_time'] ?? null,
            'is_b3'               => $isB3,
            'is_non_b3'           => $isNonB3,
        ]);

        if (!$approval) {
            FormApproval::create([
                'form_number'      => $formNumber,
                'user_id'          => $user->id,
                'requestor_name'   => $user->name,
                'requestor_dept'   => $user->department ?? 'Production',
                'form_date'        => date('d-m-Y'),
                'status'           => 'Butuh Approval Staff / Section Head',
                'user_signed_at'   => now(),
                'user_signer_name' => $user->name,
                'user_comment'     => 'Formulir pendaftaran diajukan.',
            ]);
        }

        return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])
            ->with('success', 'Barang "' . $validated['nama_barang'] . '" berhasil ditambahkan pada Form [' . $formNumber . '].');
    }

    /**
     * Approve Form Registrasi workflow.
     */
    public function approveForm(Request $request)
    {
        $this->abortIfGuest();

        $validated = $request->validate([
            'form_number'  => 'required|string',
            'stage'        => 'required|string|in:user,staff,accounting,warehouse',
            'signer_name'  => 'required|string|max:255',
            'comment'      => 'nullable|string|max:500',
            'assigned_codes' => 'nullable|array',
        ]);

        $user = auth()->user();
        $userRole = strtoupper(trim($user->role ?? 'USER'));
        $stage = $validated['stage'];
        $formNumber = $validated['form_number'];

        $approval = FormApproval::firstOrCreate(
            ['form_number' => $formNumber],
            [
                'user_id'        => $user->id,
                'requestor_name' => $user->name,
                'requestor_dept' => $user->department ?? 'Production',
                'form_date'      => date('d-m-Y'),
                'status'         => 'Butuh Approval Staff / Section Head',
            ]
        );

        $formItems = FormItem::where('form_number', $formNumber)->get();
        $hasAsset = $formItems->contains(fn($it) => $it->kategori_aset === 'ASET');

        $now = now();
        $signerName = $validated['signer_name'];
        $comment = $validated['comment'] ?? null;

        if ($stage === 'staff') {
            if (!in_array($userRole, ['MASTER', 'ADMIN', 'STAFF', 'STAFF (PRODUCTION / DIES ASSY)'])) {
                return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])
                    ->with('error', 'Hanya Staff / Section Head yang berhak menyetujui tahap ini.');
            }

            $approval->staff_signed_at   = $now;
            $approval->staff_signer_name = $signerName;
            $approval->staff_comment     = $comment;

            if ($hasAsset) {
                $approval->status = 'Butuh Approval Accounting (Aset)';
                $msg = 'Persetujuan Staff berhasil. Formulir diteruskan ke Accounting untuk verifikasi Aset.';
            } else {
                $approval->status = 'Butuh Registrasi Kode (Warehouse Consumable)';
                $msg = 'Persetujuan Staff berhasil. Formulir diteruskan ke Warehouse Consumable.';
            }
        } elseif ($stage === 'accounting') {
            if (!in_array($userRole, ['MASTER', 'ADMIN', 'ACCOUNTING'])) {
                return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])
                    ->with('error', 'Hanya Accounting yang berhak menyetujui tahap ini.');
            }

            if (!$approval->staff_signed_at) {
                return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])
                    ->with('error', 'Tahap Staff harus disetujui terlebih dahulu sebelum Accounting.');
            }

            $approval->accounting_signed_at   = $now;
            $approval->accounting_signer_name = $signerName;
            $approval->accounting_comment     = $comment;
            $approval->status = 'Butuh Registrasi Kode (Warehouse Consumable)';
            $msg = 'Persetujuan Accounting berhasil. Formulir diteruskan ke Warehouse Consumable.';
        } elseif ($stage === 'warehouse') {
            if (!in_array($userRole, ['MASTER', 'ADMIN', 'WAREHOUSE CONSUMABLE', 'PPIC WAREHOUSE'])) {
                return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])
                    ->with('error', 'Hanya Warehouse Consumable yang berhak melakukan Registrasi Akhir.');
            }

            if (!$approval->staff_signed_at) {
                return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])
                    ->with('error', 'Tahap Staff harus disetujui terlebih dahulu.');
            }
            if ($hasAsset && !$approval->accounting_signed_at) {
                return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])
                    ->with('error', 'Tahap Accounting harus disetujui terlebih dahulu karena terdapat barang ASET.');
            }

            $assignedCodes = $request->input('assigned_codes', []);
            foreach ($formItems as $it) {
                if (isset($assignedCodes[$it->id]) && !empty(trim($assignedCodes[$it->id]))) {
                    $code = trim($assignedCodes[$it->id]);
                    $it->update(['kode_barang' => $code]);

                    Item::updateOrCreate(
                        ['item_code' => $code],
                        [
                            'name'          => $it->nama_barang,
                            'category'      => $it->kategori_aset === 'ASET' ? 'Asset' : 'Consumable',
                            'description'   => "Terdaftar dari Form {$formNumber} ({$it->kategori_penggunaan})",
                            'status'        => 'registered',
                            'registered_at' => now(),
                        ]
                    );
                }
            }

            $approval->warehouse_signed_at   = $now;
            $approval->warehouse_signer_name = $signerName;
            $approval->warehouse_comment     = $comment;
            $approval->status = 'SELESAI (SUDAH TERDAFTAR)';
            $msg = 'Registrasi Berhasil! Seluruh kode barang telah didaftarkan ke sistem.';
        }

        $approval->save();

        if (!empty($comment)) {
            FormComment::create([
                'form_number' => $formNumber,
                'user_id'     => $user->id,
                'user_name'   => $signerName,
                'user_dept'   => $user->department ?? 'General',
                'user_role'   => $userRole,
                'comment'     => "[Tahap " . strtoupper($stage) . "] " . $comment,
            ]);
        }

        return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])->with('success', $msg);
    }

    /**
     * Delete entire checksheet.
     */
    public function deleteFormChecksheet(Request $request)
    {
        $this->abortIfGuest();

        $formNumber = $request->input('form_number');
        if (empty($formNumber)) {
            return redirect()->route('saturnus.form_registrasi')->with('error', 'Nomor formulir tidak valid.');
        }

        FormItem::where('form_number', $formNumber)->delete();
        FormApproval::where('form_number', $formNumber)->delete();
        FormComment::where('form_number', $formNumber)->delete();

        return redirect()->route('saturnus.form_registrasi')
            ->with('success', "Formulir [{$formNumber}] beserta seluruh data item dan approval berhasil dihapus.");
    }

    /**
     * Delete single item from form.
     */
    public function deleteFormItem($id)
    {
        $this->abortIfGuest();

        $item = FormItem::findOrFail($id);
        $formNumber = $item->form_number;
        $itemName = $item->nama_barang;
        $item->delete();

        $remaining = FormItem::where('form_number', $formNumber)->count();
        if ($remaining === 0) {
            FormApproval::where('form_number', $formNumber)->delete();
            FormComment::where('form_number', $formNumber)->delete();
            return redirect()->route('saturnus.form_registrasi')
                ->with('success', "Barang \"{$itemName}\" dihapus. Formulir [{$formNumber}] otomatis ditutup karena tidak memiliki item.");
        }

        return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])
            ->with('success', "Barang \"{$itemName}\" berhasil dihapus dari formulir.");
    }

    /**
     * Store comment.
     */
    public function storeComment(Request $request)
    {
        $this->abortIfGuest();

        $validated = $request->validate([
            'form_number' => 'required|string',
            'comment'     => 'required|string|max:1000',
        ], [
            'comment.required' => 'Komentar tidak boleh kosong.',
        ]);

        $user = auth()->user();

        FormComment::create([
            'form_number' => $validated['form_number'],
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'user_dept'   => $user->department ?? 'General',
            'user_role'   => $user->role ?? 'User',
            'comment'     => $validated['comment'],
        ]);

        return redirect()->route('saturnus.form_registrasi', ['form' => $validated['form_number']])
            ->with('success', 'Komentar berhasil ditambahkan.');
    }

    /**
     * Delete comment.
     */
    public function deleteComment(Request $request, $id)
    {
        $this->abortIfGuest();

        $comment = FormComment::findOrFail($id);
        $currentUser = auth()->user();
        $userRole = strtoupper(trim($currentUser->role ?? ''));

        if ($comment->user_id !== $currentUser->id && !in_array($userRole, ['MASTER', 'ADMIN'])) {
            return back()->with('error', 'Anda tidak memiliki hak untuk menghapus komentar ini.');
        }

        $formNumber = $comment->form_number;
        $comment->delete();

        return redirect()->route('saturnus.form_registrasi', ['form' => $formNumber])
            ->with('success', 'Komentar berhasil dihapus.');
    }
}
