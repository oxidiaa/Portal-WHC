<?php

namespace App\Http\Controllers\Saturnus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UnregistrasiItem;
use App\Models\UnregistrasiApproval;
use App\Models\UnregistrasiComment;
use App\Models\FormItem;
use App\Models\User;
use App\Services\ApprovalEmailNotificationService;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FormUnregistrasiController extends Controller
{
    private function isDepartmentAllowed($user, string $formDept): bool
    {
        $userRole = strtoupper(trim($user->role ?? 'USER'));
        if (in_array($userRole, ['MASTER', 'ADMIN']) || str_contains($userRole, 'WAREHOUSE') || str_contains($userRole, 'ACCOUNTING') || str_contains($userRole, 'ACC')) {
            return true;
        }

        $userDept = strtoupper(trim($user->department ?? ''));
        $formDept = strtoupper(trim($formDept));

        if ($userDept === $formDept) {
            return true;
        }

        if (
            (str_contains($userDept, 'PRODUCTION') && str_contains($userDept, 'DIES ASSY'))
            || (str_contains($userRole, 'PRODUCTION') && str_contains($userRole, 'DIES ASSY'))
            || $userDept === 'PRODUCTION / DIES ASSY'
            || $userDept === 'PRODUCTION/DIES ASSY'
        ) {
            return in_array($formDept, ['PRODUCTION', 'DIES ASSY', 'DIESASSY', 'DIES-ASSY', 'PRODUCTION / DIES ASSY', 'PRODUCTION/DIES ASSY']);
        }

        return false;
    }

    private function cleanupOrphanFormRecords(): void
    {
        $existingFormNumbers = UnregistrasiItem::pluck('form_number')->filter()->unique()->toArray();

        if (empty($existingFormNumbers)) {
            UnregistrasiApproval::query()->delete();
            UnregistrasiComment::query()->delete();
        } else {
            UnregistrasiApproval::whereNotIn('form_number', $existingFormNumbers)->delete();
            UnregistrasiComment::whereNotIn('form_number', $existingFormNumbers)->delete();
        }
    }

    /**
     * Shared helper to retrieve and filter unregistrasi form data.
     */
    private function getUnregistrasiFormData(Request $request): array
    {
        $this->cleanupOrphanFormRecords();

        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? 'USER'));
        $isRestricted = !in_array($currentUserRole, ['MASTER', 'ADMIN']) && !str_contains($currentUserRole, 'WAREHOUSE');

        $activeFormNoParam = $request->query('form');

        if ($isRestricted && $activeFormNoParam) {
            $parts = explode('/', $activeFormNoParam);
            $formDept = (count($parts) >= 2 && !empty($parts[1])) ? strtoupper(trim($parts[1])) : '';
            if ($formDept && !$this->isDepartmentAllowed($currentUser, $formDept)) {
                $activeFormNoParam = null;
            }
        }

        if ($isRestricted) {
            $userDept = strtoupper(trim($currentUser->department ?? ''));
            $allowedDepts = [$userDept];

            if (
                (str_contains($userDept, 'PRODUCTION') && str_contains($userDept, 'DIES ASSY'))
                || (str_contains($currentUserRole, 'PRODUCTION') && str_contains($currentUserRole, 'DIES ASSY'))
                || $userDept === 'PRODUCTION / DIES ASSY'
                || $userDept === 'PRODUCTION/DIES ASSY'
            ) {
                $allowedDepts = ['PRODUCTION', 'DIES ASSY', 'DIESASSY', 'DIES-ASSY', 'PRODUCTION / DIES ASSY', 'PRODUCTION/DIES ASSY'];
            }

            $formItems = UnregistrasiItem::with('user')
                ->where(function ($q) use ($allowedDepts, $currentUser) {
                    $q->whereIn('created_by_dept', $allowedDepts)
                      ->orWhereHas('user', function ($uq) use ($allowedDepts) {
                          $uq->whereIn('department', $allowedDepts);
                      })
                      ->orWhere('user_id', $currentUser->id);

                    foreach ($allowedDepts as $dept) {
                        $q->orWhere('form_number', 'LIKE', "%/{$dept}/%");
                    }
                })
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            $formItems = UnregistrasiItem::with('user')->orderBy('created_at', 'asc')->get();
        }

        $allFormNumbers = $formItems->pluck('form_number')->filter()->unique()->values();

        $formApprovals = UnregistrasiApproval::whereIn('form_number', $allFormNumbers)->get();
        $formComments = UnregistrasiComment::whereIn('form_number', $allFormNumbers)->orderBy('created_at', 'asc')->get();

        $defaultDeptTag = strtoupper(trim($currentUser->department ?? 'PRODUCTION'));
        if (str_contains($defaultDeptTag, 'PRODUCTION') && str_contains($defaultDeptTag, 'DIES ASSY')) {
            $defaultDeptTag = 'PRODUCTION';
        }
        $defaultFormNo = '01/' . $defaultDeptTag . '/' . date('m-Y');

        $formsToCheck = $allFormNumbers->isEmpty() ? collect([$defaultFormNo]) : $allFormNumbers;

        foreach ($formsToCheck as $fNo) {
            if (!$formApprovals->contains('form_number', $fNo)) {
                $firstItem = $formItems->firstWhere('form_number', $fNo);
                $parts = explode('/', $fNo);
                $reqDept = (count($parts) >= 2 && !empty($parts[1]))
                    ? strtoupper(trim($parts[1]))
                    : ($firstItem?->created_by_dept ?? $currentUser->department ?? 'Production');

                $creator = $firstItem?->user;

                $newApproval = UnregistrasiApproval::create([
                    'form_number'      => $fNo,
                    'user_id'          => $firstItem?->user_id ?? $creator?->id ?? $currentUser->id,
                    'requestor_name'   => $firstItem?->created_by_name ?? $creator?->name ?? ($currentUser->name ?? 'User'),
                    'requestor_dept'   => $reqDept,
                    'form_date'        => $firstItem?->created_at ? $firstItem->created_at->format('d-m-Y') : date('d-m-Y'),
                    'status'           => 'Butuh Approval Staff / Section Head',
                    'user_signed_at'   => $firstItem?->created_at ?? now(),
                    'user_signer_name' => $firstItem?->created_by_name ?? ($currentUser->name ?? 'User'),
                    'user_comment'     => 'Formulir pengajuan unregistrasi diajukan.',
                ]);
                $formApprovals->push($newApproval);
            }
        }

        $users = User::orderBy('name')->get();
        $rawRegistered = FormItem::select('kode_barang', 'nama_barang', 'spesifikasi', 'kategori_penggunaan', 'form_number', 'created_by_dept')->whereNotNull('kode_barang')->get();
        $rawUnregistered = UnregistrasiItem::select('kode_barang', 'nama_barang', 'spesifikasi', 'kategori', 'keterangan', 'form_number', 'created_by_dept')->whereNotNull('kode_barang')->get();

        if (!$isRestricted) {
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
            'formApprovals',
            'formComments',
            'activeFormNoParam',
            'users',
            'allRegisteredCodes',
            'allUnregisteredCodes'
        );
    }

    /**
     * Show the main Unregistrasi Consumable page (Lembar Cetak / Sheet View).
     */
    public function formUnregistrasi(Request $request)
    {
        $data = $this->getUnregistrasiFormData($request);
        return view('saturnus.form_unregistrasi', $data);
    }

    /**
     * Show the dedicated Proses Approval Unregistrasi page.
     */
    public function prosesApproval(Request $request)
    {
        $data = $this->getUnregistrasiFormData($request);
        return view('saturnus.unregistrasi_approval', $data);
    }

    /**
     * Show the dedicated History & Data Explorer Unregistrasi page.
     */
    public function history(Request $request)
    {
        $data = $this->getUnregistrasiFormData($request);
        return view('saturnus.unregistrasi_history', $data);
    }

    /**
     * Store an item to an unregistrasi form.
     */
    public function storeFormItem(Request $request)
    {
        $this->abortIfGuest();

        $user = auth()->user();
        if (!$user || (!$user->isMaster() && !$user->hasRole('user') && !$user->hasPermission('saturnus.unregistrasi.create'))) {
            return redirect()->back()->with('error', 'Akses Ditolak: Hanya akun dengan role User yang memiliki hak akses untuk membuat formulir unregistrasi baru.');
        }

        $validated = $request->validate([
            'form_number' => 'nullable|string',
            'kode_barang' => 'required|string',
            'nama_barang' => 'required|string',
            'spesifikasi' => 'required|string',
            'kategori'    => 'required|string',
            'keterangan'  => 'required|string',
        ], [
            'kode_barang.required' => 'Kode barang wajib diisi.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'spesifikasi.required' => 'Spesifikasi wajib diisi.',
            'kategori.required'    => 'Kategori wajib dipilih.',
            'keterangan.required'  => 'Keterangan / alasan discontinue wajib diisi.',
        ]);

        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? 'USER'));
        $isMaster = in_array($currentUserRole, ['MASTER', 'ADMIN']) || str_contains($currentUserRole, 'WAREHOUSE');

        $existingUnreg = UnregistrasiItem::where('kode_barang', $validated['kode_barang'])->latest()->first();
        if ($existingUnreg) {
            $isAllowedDept = $isMaster || $this->isDepartmentAllowed($currentUser, $existingUnreg->created_by_dept ?? '');
            $formRef = $isAllowedDept ? "pada Form Unregistrasi {$existingUnreg->form_number}" : "pada sistem";
            $nameRef = ($isAllowedDept && !empty($existingUnreg->nama_barang)) ? " ({$existingUnreg->nama_barang})" : "";
            $msg = "Peringatan: Kode barang '{$validated['kode_barang']}'{$nameRef} telah di-discontinue sebelumnya {$formRef}!";
            return back()->withErrors(['kode_barang' => $msg])->withInput()->with('error', $msg);
        }
        $userTag = strtoupper($currentUser->department ?? $currentUser->name ?? 'PRODUCTION');
        if (str_contains($userTag, 'PRODUCTION') && str_contains($userTag, 'DIES ASSY')) {
            $userTag = 'PRODUCTION';
        }

        $defaultFormNo = '01/' . $userTag . '/' . date('m-Y');
        $targetFormNo = $validated['form_number'] ?: $defaultFormNo;

        if (!$isMaster) {
            $parts = explode('/', $targetFormNo);
            $formDept = (count($parts) >= 2 && !empty($parts[1])) ? strtoupper(trim($parts[1])) : '';
            if ($formDept && !$this->isDepartmentAllowed($currentUser, $formDept)) {
                return redirect()->route('saturnus.form_unregistrasi', ['form' => $targetFormNo])
                    ->with('error', 'Akses ditolak: Anda tidak dapat menambahkan barang ke formulir departemen lain.');
            }
        }

        // Formulir unregistrasi yang sudah berhasil dibuat tidak boleh ditambahkan item lagi
        if (UnregistrasiItem::where('form_number', $targetFormNo)->exists()) {
            return redirect()->route('saturnus.form_unregistrasi', ['form' => $targetFormNo])
                ->with('error', 'Formulir ' . $targetFormNo . ' sudah berhasil dibuat. Penambahan item ke formulir yang sudah ada tidak diperbolehkan. Silakan klik "+ Form Baru".');
        }

        $item = new UnregistrasiItem();
        $item->form_number     = $targetFormNo;
        $item->user_id         = $currentUser->id;
        $item->created_by_name = $currentUser->name ?? 'User';
        $item->created_by_dept = $currentUser->department ?? 'Production';
        $item->kode_barang     = $validated['kode_barang'] ?? null;
        $item->nama_barang     = $validated['nama_barang'];
        $item->spesifikasi     = $validated['spesifikasi'] ?? null;
        $item->kategori        = $validated['kategori'] ?? null;
        $item->keterangan      = $validated['keterangan'] ?? null;
        $item->save();

        UnregistrasiApproval::firstOrCreate(
            ['form_number' => $targetFormNo],
            [
                'user_id'          => $currentUser->id,
                'requestor_name'   => $currentUser->name ?? 'User',
                'requestor_dept'   => $currentUser->department ?? 'Production',
                'form_date'        => date('d-m-Y'),
                'status'           => 'Butuh Approval Staff / Section Head',
                'user_signed_at'   => now(),
                'user_signer_name' => $currentUser->name ?? 'User',
                'user_comment'     => 'Formulir pengajuan unregistrasi diajukan.',
            ]
        );

        // Send real-time notification to Staff approver(s)
        $itemsList = UnregistrasiItem::where('form_number', $targetFormNo)->get()->toArray();
        app(ApprovalEmailNotificationService::class)->notifyStaffOnFormCreated(
            formNumber: $targetFormNo,
            moduleName: 'Unregistrasi Consumable',
            requestorName: $currentUser->name ?? 'User',
            requestorDept: $currentUser->department ?? 'Production',
            formDate: date('d/m/Y'),
            items: $itemsList
        );

        return redirect()->route('saturnus.form_unregistrasi', ['form' => $targetFormNo])
            ->with('success', 'Formulir ' . $targetFormNo . ' untuk barang "' . $item->nama_barang . '" berhasil dibuat.');
    }

    /**
     * Handle approval actions for Unregistrasi.
     */
    public function approveForm(Request $request)
    {
        $this->abortIfGuest();

        $request->validate([
            'form_number' => 'required|string',
            'role'        => 'required|string|in:user,staff,warehouse',
            'name'        => 'required|string',
            'comment'     => 'nullable|string',
        ]);

        $formNo = $request->input('form_number');
        $role   = strtolower($request->input('role'));
        $name   = $request->input('name');
        $comment = $request->input('comment') ?? 'Disetujui.';

        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? ''));
        $isMaster = in_array($currentUserRole, ['MASTER', 'ADMIN']);

        $firstItem = UnregistrasiItem::where('form_number', $formNo)->orderBy('created_at', 'asc')->first();
        $parts = explode('/', $formNo);
        $reqDept = (count($parts) >= 2 && !empty($parts[1]))
            ? strtoupper(trim($parts[1]))
            : ($firstItem?->created_by_dept ?? $currentUser->department ?? 'Production');
        $reqName = $firstItem?->created_by_name ?? $currentUser->name ?? 'User';

        $approval = UnregistrasiApproval::firstOrCreate(
            ['form_number' => $formNo],
            [
                'user_id'          => $firstItem?->user_id ?? $currentUser->id,
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

            if (!$isMaster) {
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
            $approval->status = 'Butuh Verifikasi Warehouse Consumable';
            $approval->save();

            $msg = "Form $formNo berhasil disetujui oleh Staff ($name). Tahap selanjutnya: Verifikasi Warehouse Consumable.";

            // Send real-time notification to Warehouse Consumable team
            $itemsList = UnregistrasiItem::where('form_number', $formNo)->get()->toArray();
            app(ApprovalEmailNotificationService::class)->notifyWarehouseOnNextStage(
                formNumber: $formNo,
                moduleName: 'Unregistrasi Consumable',
                approvedByRole: 'Staff',
                approverName: $name,
                approverComment: $comment,
                requestorName: $approval->requestor_name ?: $reqName,
                requestorDept: $approval->requestor_dept ?: $reqDept,
                formDate: $approval->form_date ?: date('d/m/Y'),
                items: $itemsList
            );
        } elseif ($role === 'warehouse') {
            if (!$isMaster && !str_contains($currentUserRole, 'WAREHOUSE')) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Akses Ditolak: Hanya Role Warehouse Consumable atau Master yang dapat menyelesaikan unregistrasi / discontinue ini.'], 403);
                }
                return back()->with('error', 'Akses Ditolak: Hanya Role Warehouse Consumable atau Master yang dapat menyelesaikan unregistrasi / discontinue ini.');
            }

            if (!$approval->staff_signed_at && !$isMaster) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Akses Ditolak: Tahap Staff harus disetujui terlebih dahulu sebelum proses Warehouse.'], 422);
                }
                return back()->with('error', 'Akses Ditolak: Tahap Staff harus disetujui terlebih dahulu sebelum proses Warehouse.');
            }

            $approval->warehouse_signed_at = now();
            $approval->warehouse_signer_name = $name;
            $approval->warehouse_comment = $comment;
            $approval->status = 'Telah Discontinue oleh Warehouse Consumable';
            $approval->save();

            $msg = "Form $formNo telah berhasil diverifikasi dan discontinue oleh Warehouse Consumable ($name). Proses Selesai.";

            // Send confirmation email to Requestor
            $itemsList = UnregistrasiItem::where('form_number', $formNo)->get()->toArray();
            $reqUser = $approval->user_id ? User::find($approval->user_id) : null;
            $reqEmail = $reqUser?->email;

            app(ApprovalEmailNotificationService::class)->notifyRequestorOnFinalApproval(
                formNumber: $formNo,
                moduleName: 'Unregistrasi Consumable',
                warehouseSignerName: $name,
                warehouseComment: $comment,
                requestorEmail: $reqEmail,
                requestorName: $approval->requestor_name ?: $reqName,
                requestorDept: $approval->requestor_dept ?: $reqDept,
                formDate: $approval->form_date ?: date('d/m/Y'),
                items: $itemsList
            );
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => $msg,
                'approval' => $approval,
            ]);
        }

        $redirectTo = $request->input('redirect_to', 'form');
        if ($redirectTo === 'approval') {
            return redirect()->route('saturnus.unregistrasi_approval')->with('success', $msg);
        }

        return redirect()->route('saturnus.form_unregistrasi', ['form' => $formNo])->with('success', $msg);
    }

    /**
     * Delete an entire unregistrasi form.
     */
    public function deleteFormChecksheet(Request $request)
    {
        $this->abortIfGuest();

        $formNo = $request->input('form_number');
        if (!$formNo) {
            return redirect()->route('saturnus.form_unregistrasi')->with('error', 'Form number tidak valid.');
        }

        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? ''));
        $isMaster = in_array($currentUserRole, ['MASTER', 'ADMIN']);

        if (!$isMaster) {
            return redirect()->route('saturnus.form_unregistrasi')
                ->with('error', 'Akses ditolak: Hanya Role Master yang memiliki wewenang untuk menghapus form unregistrasi.');
        }

        UnregistrasiItem::where('form_number', $formNo)->forceDelete();
        UnregistrasiApproval::where('form_number', $formNo)->delete();
        UnregistrasiComment::where('form_number', $formNo)->delete();
        $this->cleanupOrphanFormRecords();

        return redirect()->route('saturnus.form_unregistrasi')
            ->with('success', 'Formulir Unregistrasi "' . $formNo . '" berhasil dihapus secara permanen.');
    }

    /**
     * Delete an individual unregistrasi form item.
     */
    public function deleteFormItem($id)
    {
        $this->abortIfGuest();

        $item = UnregistrasiItem::findOrFail($id);
        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? ''));
        $isMaster = in_array($currentUserRole, ['MASTER', 'ADMIN']) || (method_exists($currentUser, 'isMaster') && $currentUser->isMaster());

        if (!$isMaster) {
            return redirect()->route('saturnus.form_unregistrasi', ['form' => $item->form_number])
                ->with('error', 'Akses ditolak: Hanya role Admin yang memiliki hak akses untuk menghapus item barang.');
        }

        $name = $item->nama_barang;
        $targetForm = $item->form_number;
        $item->forceDelete();

        if (UnregistrasiItem::where('form_number', $targetForm)->count() === 0) {
            UnregistrasiApproval::where('form_number', $targetForm)->delete();
            UnregistrasiComment::where('form_number', $targetForm)->delete();
        }

        $this->cleanupOrphanFormRecords();

        $redirectParams = $targetForm ? ['form' => $targetForm] : [];

        return redirect()->route('saturnus.form_unregistrasi', $redirectParams)
            ->with('success', 'Data "' . $name . '" berhasil dihapus.');
    }

    /**
     * Store a comment for an unregistrasi form.
     */
    public function storeComment(Request $request)
    {
        $this->abortIfGuest();

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

        $comment = new UnregistrasiComment();
        $comment->form_number = $formNo;
        $comment->user_id     = $currentUser->id;
        $comment->user_name   = $currentUser->name ?? 'User';
        $comment->user_dept   = $currentUser->department ?? 'Production';
        $comment->user_role   = $currentUser->role ?? 'User';
        $comment->comment     = $commentText;
        $comment->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil ditambahkan.',
                'comment' => [
                    'id'         => $comment->id,
                    'user_name'  => $comment->user_name,
                    'user_dept'  => $comment->user_dept,
                    'user_role'  => $comment->user_role,
                    'comment'    => $comment->comment,
                    'created_at' => $comment->created_at->format('d M Y, H:i'),
                ]
            ]);
        }

        return redirect()->back()->with('success', 'Catatan berhasil ditambahkan ke form ' . $formNo . '.');
    }

    /**
     * Delete a comment on an unregistrasi form.
     */
    public function deleteComment($id)
    {
        $this->abortIfGuest();

        $comment = UnregistrasiComment::findOrFail($id);
        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? ''));
        $isMaster = in_array($currentUserRole, ['MASTER', 'ADMIN']);

        if (!$isMaster && $comment->user_id !== $currentUser->id) {
            return redirect()->back()->with('error', 'Akses ditolak: Anda hanya dapat menghapus komentar Anda sendiri.');
        }

        $formNo = $comment->form_number;
        $comment->delete();

        return redirect()->back()->with('success', 'Komentar berhasil dihapus.');
    }

    /**
     * Export unregistrasi history to Excel
     */
    public function exportExcel(Request $request)
    {
        $data = $this->getUnregistrasiFormData($request);
        $formItems = $data['formItems'];
        $formApprovals = $data['formApprovals'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('History Unregistrasi');

        $headers = [
            'No',
            'No. Form Unregistrasi',
            'Tanggal Pengajuan',
            'Departemen',
            'Pembuat Form',
            'Kode Barang',
            'Nama Barang',
            'Spesifikasi',
            'Kategori',
            'Alasan Discontinue / Keterangan',
            'Status Approval',
            'Staff Approver',
            'Tgl Staff Approval',
            'Warehouse Signer',
            'Tgl Warehouse Discontinue'
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ];

        $columnLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
        
        foreach ($headers as $index => $header) {
            $colLetter = $columnLetters[$index];
            $sheet->setCellValue($colLetter . '1', $header);
            $sheet->getStyle($colLetter . '1')->applyFromArray($headerStyle);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $rowNumber = 2;
        $no = 1;

        foreach ($formItems as $item) {
            $approval = $formApprovals->firstWhere('form_number', $item->form_number);

            $sheet->setCellValue('A' . $rowNumber, $no++);
            $sheet->setCellValue('B' . $rowNumber, $item->form_number ?? '-');
            $sheet->setCellValue('C' . $rowNumber, $item->created_at ? $item->created_at->format('d/m/Y') : '-');
            $sheet->setCellValue('D' . $rowNumber, $item->created_by_dept ?? '-');
            $sheet->setCellValue('E' . $rowNumber, $item->created_by_name ?? '-');
            $sheet->setCellValue('F' . $rowNumber, $item->kode_barang ?? '-');
            $sheet->setCellValue('G' . $rowNumber, $item->nama_barang ?? '-');
            $sheet->setCellValue('H' . $rowNumber, $item->spesifikasi ?? '-');
            $sheet->setCellValue('I' . $rowNumber, $item->kategori ?? '-');
            $sheet->setCellValue('J' . $rowNumber, $item->keterangan ?? '-');
            $sheet->setCellValue('K' . $rowNumber, $approval?->status ?? 'Butuh Approval');
            $sheet->setCellValue('L' . $rowNumber, $approval?->staff_signer_name ?? '-');
            $sheet->setCellValue('M' . $rowNumber, $approval?->staff_signed_at ? Carbon::parse($approval->staff_signed_at)->format('d/m/Y H:i') : '-');
            $sheet->setCellValue('N' . $rowNumber, $approval?->warehouse_signer_name ?? '-');
            $sheet->setCellValue('O' . $rowNumber, $approval?->warehouse_signed_at ? Carbon::parse($approval->warehouse_signed_at)->format('d/m/Y H:i') : '-');

            $rowNumber++;
        }

        $fileName = 'History_Unregistrasi_SATURNUS_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
