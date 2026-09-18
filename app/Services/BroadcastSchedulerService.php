<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\FormApproval;
use App\Models\FormItem;
use App\Models\UnregistrasiApproval;
use App\Models\UnregistrasiItem;
use App\Mail\PendingApprovalReminderMail;

class BroadcastSchedulerService
{
    protected string $settingsPath;
    protected string $logsPath;

    public function __construct()
    {
        $this->settingsPath = storage_path('app/broadcast_schedule_settings.json');
        $this->logsPath = storage_path('app/broadcast_schedule_logs.json');
    }

    /**
     * Get default schedule configuration.
     */
    public function getDefaultSettings(): array
    {
        return [
            'is_enabled' => true,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'interval_hours' => 3,
            'active_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            'priority' => 'Urgent',
            'custom_message' => 'Pengingat otomatis sistem SATURNUS: Mohon kesediaannya untuk segera meninjau dan menyetujui formulir pending terlampir yang sedang menunggu persetujuan Anda.',
            'include_registrasi' => true,
            'include_unregistrasi' => true,
            'last_run_at' => null,
            'next_run_at' => null,
            'total_broadcasts_sent' => 0,
            'updated_by' => 'System Default',
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get current schedule settings.
     */
    public function getSettings(): array
    {
        if (!File::exists($this->settingsPath)) {
            $defaults = $this->getDefaultSettings();
            $defaults['next_run_at'] = $this->calculateNextRunTime($defaults)?->toDateTimeString();
            $this->saveRawSettings($defaults);
            return $defaults;
        }

        try {
            $json = File::get($this->settingsPath);
            $data = json_decode($json, true);
            if (!is_array($data)) {
                return $this->getDefaultSettings();
            }

            // Merge with defaults in case of missing keys
            $merged = array_merge($this->getDefaultSettings(), $data);

            // Dynamically recalculate next_run_at if missing or in past
            if (empty($merged['next_run_at']) || Carbon::parse($merged['next_run_at'])->isPast()) {
                $next = $this->calculateNextRunTime($merged);
                $merged['next_run_at'] = $next ? $next->toDateTimeString() : null;
            }

            return $merged;
        } catch (\Throwable $e) {
            Log::error("Failed to read broadcast settings: " . $e->getMessage());
            return $this->getDefaultSettings();
        }
    }

    /**
     * Save updated schedule settings.
     */
    public function saveSettings(array $input, ?string $updatedBy = null): array
    {
        $current = $this->getSettings();

        $activeDays = isset($input['active_days']) && is_array($input['active_days'])
            ? $this->normalizeActiveDays($input['active_days'])
            : ($current['active_days'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']);

        $settings = [
            'is_enabled' => isset($input['is_enabled']) ? (bool)$input['is_enabled'] : $current['is_enabled'],
            'start_time' => $input['start_time'] ?? $current['start_time'],
            'end_time' => $input['end_time'] ?? $current['end_time'],
            'interval_hours' => max(1, (int)($input['interval_hours'] ?? $current['interval_hours'])),
            'active_days' => $activeDays,
            'priority' => $input['priority'] ?? $current['priority'],
            'custom_message' => $input['custom_message'] ?? $current['custom_message'],
            'include_registrasi' => isset($input['include_registrasi']) ? (bool)$input['include_registrasi'] : $current['include_registrasi'],
            'include_unregistrasi' => isset($input['include_unregistrasi']) ? (bool)$input['include_unregistrasi'] : $current['include_unregistrasi'],
            'last_run_at' => $current['last_run_at'],
            'total_broadcasts_sent' => $current['total_broadcasts_sent'],
            'updated_by' => $updatedBy ?: 'Administrator',
            'updated_at' => now()->toDateTimeString(),
        ];

        // Calculate next run time
        $next = $this->calculateNextRunTime($settings);
        $settings['next_run_at'] = $next ? $next->toDateTimeString() : null;

        $this->saveRawSettings($settings);

        return $settings;
    }

    /**
     * Normalize active days to 3-letter English day names.
     */
    public function normalizeActiveDays(array $days): array
    {
        $map = [
            '1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu', '5' => 'Fri', '6' => 'Sat', '7' => 'Sun', '0' => 'Sun',
            1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun', 0 => 'Sun',
            'mon' => 'Mon', 'monday' => 'Mon', 'senin' => 'Mon',
            'tue' => 'Tue', 'tuesday' => 'Tue', 'selasa' => 'Tue',
            'wed' => 'Wed', 'wednesday' => 'Wed', 'rabu' => 'Wed',
            'thu' => 'Thu', 'thursday' => 'Thu', 'kamis' => 'Thu',
            'fri' => 'Fri', 'friday' => 'Fri', 'jumat' => 'Fri',
            'sat' => 'Sat', 'saturday' => 'Sat', 'sabtu' => 'Sat',
            'sun' => 'Sun', 'sunday' => 'Sun', 'minggu' => 'Sun',
        ];

        $normalized = [];
        foreach ($days as $d) {
            $key = is_string($d) ? strtolower(trim($d)) : $d;
            if (isset($map[$key])) {
                $normalized[] = $map[$key];
            } elseif (in_array($d, ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'])) {
                $normalized[] = $d;
            }
        }

        return array_values(array_unique($normalized)) ?: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
    }

    /**
     * Calculate the next scheduled run timestamp.
     */
    public function calculateNextRunTime(array $settings, ?Carbon $fromTime = null): ?Carbon
    {
        if (empty($settings['is_enabled'])) {
            return null;
        }

        $now = $fromTime ? $fromTime->copy() : now();
        $startTimeStr = $settings['start_time'] ?? '08:00';
        $endTimeStr = $settings['end_time'] ?? '16:00';
        $intervalHours = max(1, (int)($settings['interval_hours'] ?? 3));
        $activeDays = $settings['active_days'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

        // Loop through the next 14 days to find the next valid execution slot
        $checkDate = $now->copy();

        for ($i = 0; $i < 14; $i++) {
            $dayName = $checkDate->format('D'); // e.g. Mon, Tue, Wed

            if (in_array($dayName, $activeDays)) {
                // Parse start and end time for this check date
                [$startH, $startM] = explode(':', $startTimeStr);
                [$endH, $endM] = explode(':', $endTimeStr);

                $dayStart = $checkDate->copy()->setTime((int)$startH, (int)$startM, 0);
                $dayEnd = $checkDate->copy()->setTime((int)$endH, (int)$endM, 0);

                if ($dayEnd->lessThanOrEqualTo($dayStart)) {
                    $dayEnd->addDay();
                }

                // Generate slots: dayStart, dayStart + interval, dayStart + 2*interval, ... up to dayEnd
                $slot = $dayStart->copy();
                while ($slot->lessThanOrEqualTo($dayEnd)) {
                    if ($slot->greaterThan($now)) {
                        return $slot;
                    }
                    $slot->addHours($intervalHours);
                }
            }

            // Move to next day start
            $checkDate->addDay()->startOfDay();
        }

        return null;
    }

    /**
     * Check if the broadcast should execute right now.
     */
    public function shouldRunNow(): bool
    {
        $settings = $this->getSettings();

        if (empty($settings['is_enabled'])) {
            return false;
        }

        $now = now();
        $todayDay = $now->format('D');
        $activeDays = $settings['active_days'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

        // 1. Check if today is an active day
        if (!in_array($todayDay, $activeDays)) {
            return false;
        }

        // 2. Check if current time is within [start_time, end_time]
        [$startH, $startM] = explode(':', $settings['start_time'] ?? '08:00');
        [$endH, $endM] = explode(':', $settings['end_time'] ?? '16:00');

        $startToday = $now->copy()->setTime((int)$startH, (int)$startM, 0);
        $endToday = $now->copy()->setTime((int)$endH, (int)$endM, 0);

        if ($now->lessThan($startToday) || $now->greaterThan($endToday)) {
            return false;
        }

        // 3. Check if enough time has passed since last_run_at
        if (!empty($settings['last_run_at'])) {
            $lastRun = Carbon::parse($settings['last_run_at']);
            $intervalHours = max(1, (int)($settings['interval_hours'] ?? 3));
            $diffMinutes = $lastRun->diffInMinutes($now);

            // Require at least (intervalHours * 60 - 2) minutes to prevent double trigger
            if ($diffMinutes < ($intervalHours * 60 - 2)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute the broadcast reminder process.
     */
    public function executeBroadcast(bool $isManual = false, ?string $triggeredBy = null): array
    {
        $settings = $this->getSettings();
        $now = now();

        $senderName = $triggeredBy ?: 'Sistem Otomatis SATURNUS';
        $senderDept = 'Warehouse Consumable Portal';
        $priority = $settings['priority'] ?? 'Urgent';
        $customMessage = $settings['custom_message'] ?? 'Pengingat berkala otomatis: Mohon segera menyetujui formulir consumable terlampir.';

        // 1. Gather all pending forms
        $pendingForms = $this->collectPendingForms($settings);

        if (empty($pendingForms)) {
            $res = [
                'success' => true,
                'sent_count' => 0,
                'failed_count' => 0,
                'total_pending' => 0,
                'message' => 'Tidak ada formulir pending yang membutuhkan persetujuan saat ini.',
                'details' => [],
            ];
            $this->logRun($res, $isManual, $senderName);
            return $res;
        }

        // 2. Get all eligible approvers
        $approvers = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->whereIn('role', [
                'Staff', 'Staff Approver', 'Staff (Production / Dies Assy)',
                'Accounting', 'Warehouse Consumable', 'Admin', 'MASTER'
            ])
            ->get();

        $sentCount = 0;
        $failedCount = 0;
        $details = [];

        foreach ($approvers as $approver) {
            $userForms = $this->filterFormsForApprover($approver, $pendingForms);
            if (empty($userForms)) {
                continue;
            }

            try {
                Mail::to($approver->email)->send(new PendingApprovalReminderMail(
                    $approver->name,
                    $approver->email,
                    $senderName,
                    $senderDept,
                    $customMessage,
                    $priority,
                    $userForms
                ));

                $sentCount++;
                $details[] = [
                    'recipient_name' => $approver->name,
                    'recipient_email' => $approver->email,
                    'department' => $approver->department,
                    'role' => $approver->role,
                    'form_count' => count($userForms),
                    'status' => 'Success',
                ];
            } catch (\Throwable $e) {
                $failedCount++;
                $details[] = [
                    'recipient_name' => $approver->name,
                    'recipient_email' => $approver->email,
                    'department' => $approver->department,
                    'role' => $approver->role,
                    'form_count' => count($userForms),
                    'status' => 'Failed: ' . $e->getMessage(),
                ];
                Log::error("Scheduled broadcast failed for {$approver->email}: " . $e->getMessage());
            }
        }

        // 3. Update execution timestamp & stats
        $settings['last_run_at'] = $now->toDateTimeString();
        $settings['total_broadcasts_sent'] = ($settings['total_broadcasts_sent'] ?? 0) + $sentCount;

        $next = $this->calculateNextRunTime($settings, $now);
        $settings['next_run_at'] = $next ? $next->toDateTimeString() : null;

        $this->saveRawSettings($settings);

        $result = [
            'success' => $sentCount > 0 || $failedCount === 0,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'total_pending' => count($pendingForms),
            'next_run_at' => $settings['next_run_at'],
            'message' => $sentCount > 0
                ? "Broadcast otomatis berhasil dikirimkan ke {$sentCount} approver (" . count($pendingForms) . " form pending)."
                : "Tidak ada email yang terkirim (approver tidak memiliki pending form aktif).",
            'details' => $details,
        ];

        // 4. Log the execution
        $this->logRun($result, $isManual, $senderName);

        return $result;
    }

    /**
     * Get automated execution history logs.
     */
    public function getLogs(): array
    {
        if (!File::exists($this->logsPath)) {
            return [];
        }

        try {
            $json = File::get($this->logsPath);
            $data = json_decode($json, true);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Log an execution event.
     */
    public function logRun(array $result, bool $isManual = false, ?string $triggeredBy = null): void
    {
        $logs = $this->getLogs();

        $entry = [
            'executed_at' => now()->format('d/m/Y H:i:s'),
            'type' => $isManual ? 'Manual Trigger' : 'Scheduled Timing Auto',
            'triggered_by' => $triggeredBy ?: 'System Scheduler',
            'sent_count' => $result['sent_count'] ?? 0,
            'failed_count' => $result['failed_count'] ?? 0,
            'total_pending' => $result['total_pending'] ?? 0,
            'status' => ($result['sent_count'] ?? 0) > 0 ? 'Sukses Terkirim' : 'Selesai (0 Pending)',
            'message' => $result['message'] ?? '',
            'details' => $result['details'] ?? [],
        ];

        array_unshift($logs, $entry);
        $logs = array_slice($logs, 0, 50); // Keep last 50 entries

        try {
            if (!File::isDirectory(storage_path('app'))) {
                File::makeDirectory(storage_path('app'), 0755, true);
            }
            File::put($this->logsPath, json_encode($logs, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            Log::error("Failed to write broadcast logs: " . $e->getMessage());
        }
    }

    /**
     * Helper to write raw settings file.
     */
    protected function saveRawSettings(array $settings): void
    {
        if (!File::isDirectory(storage_path('app'))) {
            File::makeDirectory(storage_path('app'), 0755, true);
        }
        File::put($this->settingsPath, json_encode($settings, JSON_PRETTY_PRINT));
    }

    /**
     * Collect pending forms based on schedule filter settings.
     */
    protected function collectPendingForms(array $settings): array
    {
        $pending = [];

        // Registrasi Consumable
        if (!empty($settings['include_registrasi']) && class_exists(FormApproval::class)) {
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
                    'status_label' => $stage,
                    'stage_key' => $stageKey,
                    'item_count' => $itemCount,
                    'action_url' => route('saturnus.proses_approval'),
                ];
            }
        }

        // Unregistrasi Consumable
        if (!empty($settings['include_unregistrasi']) && class_exists(UnregistrasiApproval::class)) {
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
                    'status_label' => $stage,
                    'stage_key' => $stageKey,
                    'item_count' => $itemCount,
                    'action_url' => route('saturnus.unregistrasi_approval'),
                ];
            }
        }

        return $pending;
    }

    /**
     * Filter pending forms list for a specific approver based on role & dept.
     */
    protected function filterFormsForApprover(User $user, array $formsPool): array
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
}
