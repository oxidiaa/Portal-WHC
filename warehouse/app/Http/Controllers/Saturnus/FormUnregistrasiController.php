<?php

namespace App\Http\Controllers\Saturnus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UnregistrasiItem;
use App\Models\UnregistrasiApproval;
use App\Models\UnregistrasiComment;
use App\Models\FormItem;
use App\Models\User;

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
     * Show the main Unregistrasi Consumable page.
     */
    public function formUnregistrasi(Request $request)
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

        return view('saturnus.form_unregistrasi', compact('formItems', 'formApprovals', 'formComments', 'activeFormNoParam', 'users', 'allRegisteredCodes', 'allUnregisteredCodes'));
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
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => $msg,
                'approval' => $approval,
            ]);
        }

        return redirect()->route('saturnus.form_unregistrasi', ['tab' => 'proses-approval', 'form' => $formNo])
            ->with('success', $msg);
    }

    /**
     * Delete an entire unregistrasi form.
     */
    public function deleteFormChecksheet(Request $request)
    {
        $this->abortIfGuest();

        $formNo = $request->input('form_number');
        if (!$formNo) {
            return redirect()->route('saturnus.form_unregistrasi', ['tab' => 'data-view'])->with('error', 'Form number tidak valid.');
        }

        $currentUser = auth()->user();
        $currentUserRole = strtoupper(trim($currentUser->role ?? ''));
        $isMaster = in_array($currentUserRole, ['MASTER', 'ADMIN']);

        if (!$isMaster) {
            return redirect()->route('saturnus.form_unregistrasi', ['tab' => 'data-view'])
                ->with('error', 'Akses ditolak: Hanya Role Master yang memiliki wewenang untuk menghapus form unregistrasi.');
        }

        UnregistrasiItem::where('form_number', $formNo)->forceDelete();
        UnregistrasiApproval::where('form_number', $formNo)->delete();
        UnregistrasiComment::where('form_number', $formNo)->delete();
        $this->cleanupOrphanFormRecords();

        return redirect()->route('saturnus.form_unregistrasi', ['tab' => 'data-view'])
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
        $isMaster = in_array($currentUserRole, ['MASTER', 'ADMIN']);

        if (!$isMaster) {
            $itemDept = strtoupper(trim($item->created_by_dept ?? $item->user?->department ?? ''));
            if (!$itemDept && str_contains($item->form_number, '/')) {
                $parts = explode('/', $item->form_number);
                $itemDept = isset($parts[1]) ? strtoupper(trim($parts[1])) : '';
            }
            if ($itemDept && !$this->isDepartmentAllowed($currentUser, $itemDept)) {
                return redirect()->route('saturnus.form_unregistrasi')
                    ->with('error', 'Akses ditolak: Anda tidak memiliki wewenang menghapus barang dari departemen lain.');
            }
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

        $comment = UnregistrasiComment::create([
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
                    'created_at'     => $comment->created_at?->toISOString() ?? now()->toISOString(),
                    'can_delete'     => in_array(strtoupper(trim($currentUser->role ?? '')), ['MASTER', 'ADMIN']),
                ],
            ]);
        }

        return redirect()->route('saturnus.form_unregistrasi', ['form' => $formNo])
            ->with('success', 'Komentar berhasil ditambahkan.');
    }

    /**
     * Delete an unregistrasi comment.
     */
    public function deleteComment(Request $request, $id)
    {
        $this->abortIfGuest();

        $comment = UnregistrasiComment::findOrFail($id);
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

        return redirect()->route('saturnus.form_unregistrasi', ['form' => $formNo])
            ->with('success', 'Komentar berhasil dihapus.');
    }
}
