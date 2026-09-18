<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\FormApproval;
use App\Models\FormItem;
use App\Models\UnregistrasiApproval;
use App\Models\UnregistrasiItem;
use App\Mail\PendingApprovalReminderMail;

class EmailReminderController extends Controller
{
    /**
     * Display the Email Reminder compose & management page.
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();

        // 1. Get all eligible users with emails
        $users = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        // 2. Fetch all pending Registrasi forms
        $pendingRegistrasi = [];
        if (class_exists(FormApproval::class)) {
            $regApprovals = FormApproval::all();
            foreach ($regApprovals as $appr) {
                // Check if completed
                if ($appr->warehouse_signed_at) {
                    continue; // Already finished
                }

                // Determine pending stage
                $stage = 'Staff / Section Head (Tahap 1)';
                $stageKey = 'staff';
                if ($appr->staff_signed_at && !$appr->accounting_signed_at) {
                    $stage = 'Accounting & Finance (Tahap 2)';
                    $stageKey = 'accounting';
                } elseif ($appr->staff_signed_at && $appr->accounting_signed_at && !$appr->warehouse_signed_at) {
                    $stage = 'Warehouse Consumable (Tahap 3)';
                    $stageKey = 'warehouse';
                }

                // Get items count
                $itemCount = FormItem::where('form_number', $appr->form_number)->count();
                $itemsSample = FormItem::where('form_number', $appr->form_number)->limit(3)->pluck('nama_barang')->toArray();

                // Extract department
                $dept = $appr->requestor_dept;
                if (!$dept && $appr->form_number) {
                    $parts = explode('/', $appr->form_number);
                    $dept = count($parts) >= 2 ? $parts[1] : 'Production';
                }

                $pendingRegistrasi[] = [
                    'form_number' => $appr->form_number,
                    'module' => 'Registrasi Consumable',
                    'module_key' => 'registrasi',
                    'department' => $dept ?: 'Production',
                    'requestor' => $appr->requestor_name ?: ($appr->user_signer_name ?: 'User'),
                    'date' => $appr->created_at ? $appr->created_at->format('d/m/Y') : ($appr->form_date ?: date('d/m/Y')),
                    'created_at_raw' => $appr->created_at ? $appr->created_at->timestamp : 0,
                    'status_label' => $stage,
                    'stage_key' => $stageKey,
                    'item_count' => $itemCount,
                    'items_summary' => implode(', ', $itemsSample),
                    'action_url' => route('saturnus.proses_approval'),
                ];
            }
        }

        // 3. Fetch all pending Unregistrasi forms
        $pendingUnregistrasi = [];
        if (class_exists(UnregistrasiApproval::class)) {
            $unregApprovals = UnregistrasiApproval::all();
            foreach ($unregApprovals as $appr) {
                // Check if completed
                if ($appr->warehouse_signed_at) {
                    continue; // Finished
                }

                $stage = 'Staff / Section Head (Tahap 1)';
                $stageKey = 'staff';
                if ($appr->staff_signed_at && !$appr->warehouse_signed_at) {
                    $stage = 'Warehouse Consumable (Tahap 2)';
                    $stageKey = 'warehouse';
                }

                $itemCount = UnregistrasiItem::where('form_number', $appr->form_number)->count();
                $itemsSample = UnregistrasiItem::where('form_number', $appr->form_number)->limit(3)->pluck('nama_barang')->toArray();

                $dept = $appr->requestor_dept;
                if (!$dept && $appr->form_number) {
                    $parts = explode('/', $appr->form_number);
                    $dept = count($parts) >= 2 ? $parts[1] : 'Production';
                }

                $pendingUnregistrasi[] = [
                    'form_number' => $appr->form_number,
                    'module' => 'Unregistrasi Consumable',
                    'module_key' => 'unregistrasi',
                    'department' => $dept ?: 'Production',
                    'requestor' => $appr->requestor_name ?: ($appr->user_signer_name ?: 'User'),
                    'date' => $appr->created_at ? $appr->created_at->format('d/m/Y') : ($appr->form_date ?: date('d/m/Y')),
                    'created_at_raw' => $appr->created_at ? $appr->created_at->timestamp : 0,
                    'status_label' => $stage,
                    'stage_key' => $stageKey,
                    'item_count' => $itemCount,
                    'items_summary' => implode(', ', $itemsSample),
                    'action_url' => route('saturnus.unregistrasi_approval'),
                ];
            }
        }

        // Combine all pending forms
        $allPendingForms = array_merge($pendingRegistrasi, $pendingUnregistrasi);

        // Calculate statistics
        $stats = [
            'total_pending' => count($allPendingForms),
            'pending_reg' => count($pendingRegistrasi),
            'pending_unreg' => count($pendingUnregistrasi),
            'pending_staff' => count(array_filter($allPendingForms, fn($f) => $f['stage_key'] === 'staff')),
            'pending_accounting' => count(array_filter($allPendingForms, fn($f) => $f['stage_key'] === 'accounting')),
            'pending_warehouse' => count(array_filter($allPendingForms, fn($f) => $f['stage_key'] === 'warehouse')),
        ];

        // Retrieve recent email logs from session
        $recentLogs = session('email_reminder_logs', []);

        return view('saturnus.email_reminder', compact(
            'users',
            'allPendingForms',
            'stats',
            'recentLogs',
            'currentUser'
        ));
    }

    /**
     * Get pending forms specifically applicable to a selected user's role & department.
     */
    public function getPendingForUser($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan', 'forms' => []], 404);
        }

        $userRole = strtoupper(trim($user->role ?? 'USER'));
        $userDept = strtoupper(trim($user->department ?? 'PRODUCTION'));
        $isMasterAdmin = in_array($userRole, ['MASTER', 'ADMIN']) || (method_exists($user, 'isMaster') && $user->isMaster());

        // Get all pending forms
        $allPending = $this->collectAllPendingForms();
        $filtered = [];

        foreach ($allPending as $form) {
            $fDept = strtoupper(trim($form['department']));

            if ($isMasterAdmin) {
                // Master/Admin sees all pending
                $filtered[] = $form;
            } elseif (str_contains($userRole, 'STAFF')) {
                // Staff only sees stage 1 of their department
                if ($form['stage_key'] === 'staff') {
                    if (str_contains($userDept, 'PRODUCTION') && (str_contains($fDept, 'PRODUCTION') || str_contains($fDept, 'DIES ASSY'))) {
                        $filtered[] = $form;
                    } elseif (str_contains($fDept, $userDept) || str_contains($userDept, $fDept)) {
                        $filtered[] = $form;
                    }
                }
            } elseif (str_contains($userRole, 'ACC') || str_contains($userRole, 'ACCOUNTING')) {
                // Accounting only sees stage 2
                if ($form['stage_key'] === 'accounting') {
                    $filtered[] = $form;
                }
            } elseif (str_contains($userRole, 'WAREHOUSE')) {
                // Warehouse sees stage 3 (reg) & stage 2 (unreg)
                if ($form['stage_key'] === 'warehouse') {
                    $filtered[] = $form;
                }
            } else {
                // Regular user
                if (str_contains($fDept, $userDept)) {
                    $filtered[] = $form;
                }
            }
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department,
                'role' => $user->role,
            ],
            'count' => count($filtered),
            'forms' => array_values($filtered)
        ]);
    }

    /**
     * Preview HTML Email in real-time.
     */
    public function preview(Request $request)
    {
        $recipientName = $request->input('recipient_name', 'Nama Penerima');
        $recipientEmail = $request->input('recipient_email', 'penerima@metalart-astra.co.id');
        $senderName = Auth::user()->name ?? 'User MAI';
        $senderDept = Auth::user()->department ?? 'Warehouse Consumable';
        $customMessage = $request->input('custom_message');
        $priority = $request->input('priority', 'Normal');
        $subject = $request->input('subject');

        $selectedFormNumbers = (array) $request->input('selected_forms', []);
        $allPending = $this->collectAllPendingForms();

        $pendingForms = [];
        if (!empty($selectedFormNumbers)) {
            $pendingForms = array_filter($allPending, function($f) use ($selectedFormNumbers) {
                return in_array($f['form_number'], $selectedFormNumbers);
            });
        } else {
            // If none explicitly selected, take first 3 as sample
            $pendingForms = array_slice($allPending, 0, 3);
        }

        $mailable = new PendingApprovalReminderMail(
            $recipientName,
            $recipientEmail,
            $senderName,
            $senderDept,
            $customMessage,
            $priority,
            array_values($pendingForms),
            $subject
        );

        return $mailable->render();
    }

    /**
     * Send email reminder via SMTP.
     */
    public function send(Request $request)
    {
        $request->validate([
            'recipient_email' => 'required_without:broadcast_mode|email',
            'priority' => 'nullable|string',
            'custom_message' => 'nullable|string|max:2000',
            'subject' => 'nullable|string|max:255',
        ]);

        $sender = Auth::user();
        $senderName = $sender->name ?? 'User MAI';
        $senderDept = $sender->department ?? 'Warehouse Consumable';
        $priority = $request->input('priority', 'Normal');
        $customMessage = $request->input('custom_message');
        $customSubject = $request->input('subject');
        $selectedFormNumbers = (array) $request->input('selected_forms', []);

        $allPending = $this->collectAllPendingForms();
        $selectedPendingForms = [];

        if (!empty($selectedFormNumbers)) {
            $selectedPendingForms = array_values(array_filter($allPending, function($f) use ($selectedFormNumbers) {
                return in_array($f['form_number'], $selectedFormNumbers);
            }));
        } else {
            $selectedPendingForms = $allPending;
        }

        $sentCount = 0;
        $failedCount = 0;
        $errors = [];

        // Check if broadcast mode (Send to all approvers with pending tasks)
        if ($request->boolean('broadcast_mode')) {
            $approvers = User::whereNotNull('email')
                ->where('email', '!=', '')
                ->whereIn('role', ['Staff', 'Staff Approver', 'Staff (Production / Dies Assy)', 'Accounting', 'Warehouse Consumable', 'Admin', 'MASTER'])
                ->get();

            foreach ($approvers as $approver) {
                // Filter pending forms for this approver
                $userForms = $this->getPendingFormsForUserInternal($approver, $selectedPendingForms);
                if (empty($userForms)) {
                    continue; // No pending forms for this approver
                }

                try {
                    Mail::to($approver->email)->send(new PendingApprovalReminderMail(
                        $approver->name,
                        $approver->email,
                        $senderName,
                        $senderDept,
                        $customMessage,
                        $priority,
                        $userForms,
                        $customSubject
                    ));

                    $this->logEmailSent($approver->name, $approver->email, count($userForms), $priority, 'Sukses (Broadcast)');
                    $sentCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    $errors[] = "Gagal ke {$approver->email}: " . $e->getMessage();
                    Log::error("Email reminder broadcast failed for {$approver->email}: " . $e->getMessage());
                }
            }

            if ($sentCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Pengingat broadcast berhasil dikirim ke {$sentCount} approver!",
                    'sent_count' => $sentCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada approver yang memiliki formulir pending untuk dikirimi broadcast.',
                    'errors' => $errors,
                ], 422);
            }
        }

        // Single recipient mode
        $recipientEmail = $request->input('recipient_email');
        $recipientName = $request->input('recipient_name') ?: $recipientEmail;

        if ($request->filled('user_id')) {
            $u = User::find($request->input('user_id'));
            if ($u) {
                $recipientName = $u->name;
                $recipientEmail = $u->email;
            }
        }

        try {
            Mail::to($recipientEmail)->send(new PendingApprovalReminderMail(
                $recipientName,
                $recipientEmail,
                $senderName,
                $senderDept,
                $customMessage,
                $priority,
                $selectedPendingForms,
                $customSubject
            ));

            $this->logEmailSent($recipientName, $recipientEmail, count($selectedPendingForms), $priority, 'Sukses');

            return response()->json([
                'success' => true,
                'message' => "Email pengingat approval berhasil dikirimkan ke {$recipientEmail}!",
                'recipient' => $recipientEmail,
                'forms_count' => count($selectedPendingForms),
            ]);
        } catch (\Throwable $e) {
            Log::error("Email reminder failed for {$recipientEmail}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Gagal mengirim email: " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper to collect all pending forms.
     */
    private function collectAllPendingForms(): array
    {
        $pending = [];

        // Registrasi
        if (class_exists(FormApproval::class)) {
            $regApprovals = FormApproval::all();
            foreach ($regApprovals as $appr) {
                if ($appr->warehouse_signed_at) continue;

                $stage = 'Staff / Section Head (Tahap 1)';
                $stageKey = 'staff';
                if ($appr->staff_signed_at && !$appr->accounting_signed_at) {
                    $stage = 'Accounting & Finance (Tahap 2)';
                    $stageKey = 'accounting';
                } elseif ($appr->staff_signed_at && $appr->accounting_signed_at && !$appr->warehouse_signed_at) {
                    $stage = 'Warehouse Consumable (Tahap 3)';
                    $stageKey = 'warehouse';
                }

                $itemCount = FormItem::where('form_number', $appr->form_number)->count();
                $itemsSample = FormItem::where('form_number', $appr->form_number)->limit(3)->pluck('nama_barang')->toArray();

                $dept = $appr->requestor_dept;
                if (!$dept && $appr->form_number) {
                    $parts = explode('/', $appr->form_number);
                    $dept = count($parts) >= 2 ? $parts[1] : 'Production';
                }

                $pending[] = [
                    'form_number' => $appr->form_number,
                    'module' => 'Registrasi Consumable',
                    'module_key' => 'registrasi',
                    'department' => $dept ?: 'Production',
                    'requestor' => $appr->requestor_name ?: ($appr->user_signer_name ?: 'User'),
                    'date' => $appr->created_at ? $appr->created_at->format('d/m/Y') : ($appr->form_date ?: date('d/m/Y')),
                    'created_at_raw' => $appr->created_at ? $appr->created_at->timestamp : 0,
                    'status_label' => $stage,
                    'stage_key' => $stageKey,
                    'item_count' => $itemCount,
                    'items_summary' => implode(', ', $itemsSample),
                    'action_url' => route('saturnus.proses_approval'),
                ];
            }
        }

        // Unregistrasi
        if (class_exists(UnregistrasiApproval::class)) {
            $unregApprovals = UnregistrasiApproval::all();
            foreach ($unregApprovals as $appr) {
                if ($appr->warehouse_signed_at) continue;

                $stage = 'Staff / Section Head (Tahap 1)';
                $stageKey = 'staff';
                if ($appr->staff_signed_at && !$appr->warehouse_signed_at) {
                    $stage = 'Warehouse Consumable (Tahap 2)';
                    $stageKey = 'warehouse';
                }

                $itemCount = UnregistrasiItem::where('form_number', $appr->form_number)->count();
                $itemsSample = UnregistrasiItem::where('form_number', $appr->form_number)->limit(3)->pluck('nama_barang')->toArray();

                $dept = $appr->requestor_dept;
                if (!$dept && $appr->form_number) {
                    $parts = explode('/', $appr->form_number);
                    $dept = count($parts) >= 2 ? $parts[1] : 'Production';
                }

                $pending[] = [
                    'form_number' => $appr->form_number,
                    'module' => 'Unregistrasi Consumable',
                    'module_key' => 'unregistrasi',
                    'department' => $dept ?: 'Production',
                    'requestor' => $appr->requestor_name ?: ($appr->user_signer_name ?: 'User'),
                    'date' => $appr->created_at ? $appr->created_at->format('d/m/Y') : ($appr->form_date ?: date('d/m/Y')),
                    'created_at_raw' => $appr->created_at ? $appr->created_at->timestamp : 0,
                    'status_label' => $stage,
                    'stage_key' => $stageKey,
                    'item_count' => $itemCount,
                    'items_summary' => implode(', ', $itemsSample),
                    'action_url' => route('saturnus.unregistrasi_approval'),
                ];
            }
        }

        return $pending;
    }

    /**
     * Filter pending forms for a user instance.
     */
    private function getPendingFormsForUserInternal(User $user, array $formsPool): array
    {
        $userRole = strtoupper(trim($user->role ?? 'USER'));
        $userDept = strtoupper(trim($user->department ?? 'PRODUCTION'));
        $isMasterAdmin = in_array($userRole, ['MASTER', 'ADMIN']) || (method_exists($user, 'isMaster') && $user->isMaster());

        $filtered = [];
        foreach ($formsPool as $form) {
            $fDept = strtoupper(trim($form['department']));

            if ($isMasterAdmin) {
                $filtered[] = $form;
            } elseif (str_contains($userRole, 'STAFF')) {
                if ($form['stage_key'] === 'staff') {
                    if (str_contains($userDept, 'PRODUCTION') && (str_contains($fDept, 'PRODUCTION') || str_contains($fDept, 'DIES ASSY'))) {
                        $filtered[] = $form;
                    } elseif (str_contains($fDept, $userDept) || str_contains($userDept, $fDept)) {
                        $filtered[] = $form;
                    }
                }
            } elseif (str_contains($userRole, 'ACC') || str_contains($userRole, 'ACCOUNTING')) {
                if ($form['stage_key'] === 'accounting') {
                    $filtered[] = $form;
                }
            } elseif (str_contains($userRole, 'WAREHOUSE')) {
                if ($form['stage_key'] === 'warehouse') {
                    $filtered[] = $form;
                }
            }
        }

        return $filtered;
    }

    /**
     * Store recent email log into session.
     */
    private function logEmailSent(string $recipientName, string $recipientEmail, int $formCount, string $priority, string $status): void
    {
        $logs = session('email_reminder_logs', []);
        array_unshift($logs, [
            'recipient_name' => $recipientName,
            'recipient_email' => $recipientEmail,
            'form_count' => $formCount,
            'priority' => $priority,
            'sender' => Auth::user()->name ?? 'User',
            'sent_at' => now()->format('d/m/Y H:i:s'),
            'status' => $status,
        ]);

        // Keep last 30 logs
        $logs = array_slice($logs, 0, 30);
        session(['email_reminder_logs' => $logs]);
    }
}
