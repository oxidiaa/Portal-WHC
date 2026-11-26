<?php

namespace App\Services;

use App\Mail\FormCommentNotificationMail;
use App\Models\FormApproval;
use App\Models\FormComment;
use App\Models\FormItem;
use App\Models\UnregistrasiApproval;
use App\Models\UnregistrasiComment;
use App\Models\UnregistrasiItem;
use App\Models\User;
use App\Notifications\SaturnusCommentNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SaturnusCommentNotificationService
{
    public function __construct(
        private readonly ApprovalEmailNotificationService $approvalEmailService
    ) {}

    /**
     * Dispatch in-app database notifications and email notifications when a new comment is added.
     *
     * @return array{notified_users: int, emails_sent: int}
     */
    public function notifyOnNewComment(
        string $module,
        string $formNumber,
        User $commenter,
        string $commentText,
        int $commentId
    ): array {
        $isUnregistrasi = strtolower($module) === 'unregistrasi';
        $moduleLabel = $isUnregistrasi ? 'Unregistrasi' : 'Registrasi';
        $actionUrl = $isUnregistrasi
            ? route('saturnus.form_unregistrasi', ['form' => $formNumber])
            : route('saturnus.form_registrasi', ['form' => $formNumber]);

        $formContext = $this->resolveFormContext($isUnregistrasi, $formNumber);
        $recipients = $this->resolveRecipients($isUnregistrasi, $formNumber, $formContext['dept'], $formContext['requestor_id'], $commenter->id);

        $notifiedUsers = 0;
        $emailsSent = 0;
        $hasNotificationsTable = Schema::hasTable('notifications');

        $notification = new SaturnusCommentNotification(
            module: $moduleLabel,
            formNumber: $formNumber,
            commentId: $commentId,
            commenterId: $commenter->id,
            commenterName: $commenter->name ?? 'User',
            commenterRole: $commenter->role ?? 'User',
            commenterDept: $commenter->department ?? 'Production',
            commentText: $commentText,
            url: $actionUrl,
        );

        foreach ($recipients as $recipient) {
            if ($hasNotificationsTable) {
                try {
                    $recipient->notify($notification);
                    $notifiedUsers++;
                } catch (\Throwable $e) {
                    Log::error("SaturnusCommentNotification DB Error (User #{$recipient->id}, Form {$formNumber}): " . $e->getMessage(), [
                        'exception' => $e,
                    ]);
                }
            }

            if (!empty(trim((string) ($recipient->email ?? '')))) {
                try {
                    Mail::to($recipient->email)->send(new FormCommentNotificationMail(
                        recipientName: $recipient->name ?? 'Rekan Kerja',
                        recipientEmail: $recipient->email,
                        moduleLabel: $moduleLabel,
                        formNumber: $formNumber,
                        requestorDept: $formContext['dept'],
                        commenterName: $commenter->name ?? 'User',
                        commenterRole: $commenter->role ?? 'User',
                        commenterDept: $commenter->department ?? 'Production',
                        commentText: $commentText,
                        actionUrl: $actionUrl,
                    ));
                    $emailsSent++;
                } catch (\Throwable $e) {
                    Log::error("SaturnusCommentNotification Mail Error ({$recipient->email}, Form {$formNumber}): " . $e->getMessage(), [
                        'exception' => $e,
                    ]);
                }
            }
        }

        return [
            'notified_users' => $notifiedUsers,
            'emails_sent'    => $emailsSent,
        ];
    }

    /**
     * Resolve form requestor ID and department.
     *
     * @return array{dept: string, requestor_id: int|null}
     */
    private function resolveFormContext(bool $isUnregistrasi, string $formNumber): array
    {
        if ($isUnregistrasi) {
            $approval = UnregistrasiApproval::where('form_number', $formNumber)->first();
            $firstItem = UnregistrasiItem::where('form_number', $formNumber)->first();
        } else {
            $approval = FormApproval::where('form_number', $formNumber)->first();
            $firstItem = FormItem::where('form_number', $formNumber)->first();
        }

        $dept = $approval?->requestor_dept ?? $firstItem?->created_by_dept ?? '';
        if (empty($dept) && str_contains($formNumber, '/')) {
            $parts = explode('/', $formNumber);
            $dept = $parts[1] ?? '';
        }

        return [
            'dept'         => trim((string) $dept) ?: 'Production',
            'requestor_id' => $approval?->user_id ?? $firstItem?->user_id,
        ];
    }

    /**
     * Collect all unique active recipients involved in this form, excluding the commenter.
     *
     * @return Collection<int, User>
     */
    private function resolveRecipients(
        bool $isUnregistrasi,
        string $formNumber,
        string $formDept,
        ?int $requestorId,
        int $commenterId
    ): Collection {
        $candidates = collect();

        // 1. Form Requestor
        if ($requestorId) {
            $requestor = User::find($requestorId);
            if ($requestor) {
                $candidates->push($requestor);
            }
        }

        // 2. Staff Approvers for the form's department (both with email via existing service and without email)
        $candidates = $candidates->merge($this->approvalEmailService->getStaffApproversForDepartment($formDept));
        $candidates = $candidates->merge($this->getStaffForDepartmentIncludingNoEmail($formDept));

        // 3. Accounting Approvers (Registrasi only)
        if (!$isUnregistrasi) {
            $candidates = $candidates->merge($this->approvalEmailService->getAccountingApprovers());
            $candidates = $candidates->merge(
                User::where(function ($q) {
                    $q->where('role', 'like', '%Accounting%')
                        ->orWhere('role', 'like', '%ACC%')
                        ->orWhere('department', 'like', '%Accounting%');
                })->get()
            );
        }

        // 4. Warehouse Consumable Approvers
        $candidates = $candidates->merge($this->approvalEmailService->getWarehouseApprovers());
        $candidates = $candidates->merge(
            User::where(function ($q) {
                $q->where('role', 'like', '%Warehouse%')
                    ->orWhere('role', 'like', '%WHC%')
                    ->orWhere('role', 'like', '%PPIC Warehouse%');
            })->where('role', 'not like', '%Staff%')->get()
        );

        // 5. Master / Admin users
        $candidates = $candidates->merge(
            User::where(function ($q) {
                $q->where('role', 'like', '%Master%')
                    ->orWhere('role', 'like', '%Admin%');
            })->get()
        );

        // 6. Previous commenters on the same form
        $commenterIds = $isUnregistrasi
            ? UnregistrasiComment::where('form_number', $formNumber)->pluck('user_id')->filter()->unique()->all()
            : FormComment::where('form_number', $formNumber)->pluck('user_id')->filter()->unique()->all();

        if (!empty($commenterIds)) {
            $candidates = $candidates->merge(User::whereIn('id', $commenterIds)->get());
        }

        return $candidates
            ->unique('id')
            ->reject(function (User $user) use ($commenterId) {
                if ((int) $user->id === $commenterId) {
                    return true;
                }
                $status = strtolower(trim((string) ($user->status ?? 'aktif')));
                return in_array($status, ['nonaktif', 'inactive', 'disabled'], true);
            })
            ->values();
    }

    /**
     * Resolve department staff regardless of whether an email address is configured.
     *
     * @return Collection<int, User>
     */
    private function getStaffForDepartmentIncludingNoEmail(string $dept): Collection
    {
        $deptUpper = strtoupper(trim($dept));
        $allStaff = User::where(function ($q) {
            $q->where('role', 'like', '%Staff%')
                ->orWhere('role', 'like', '%staff%');
        })->get();

        return $allStaff->filter(function (User $staff) use ($deptUpper) {
            $staffDept = strtoupper(trim((string) ($staff->department ?? '')));
            $staffRole = strtoupper(trim((string) ($staff->role ?? '')));

            if ((str_contains($deptUpper, 'PRODUCTION') || str_contains($deptUpper, 'DIES')) &&
                (str_contains($staffDept, 'PRODUCTION') || str_contains($staffDept, 'DIES') || str_contains($staffRole, 'PRODUCTION') || str_contains($staffRole, 'DIES'))) {
                return true;
            }

            if (str_contains($deptUpper, 'MAINTENANCE') && str_contains($staffDept, 'MAINTENANCE')) {
                return true;
            }

            if ((str_contains($deptUpper, 'PPIC') || str_contains($deptUpper, 'WAREHOUSE')) &&
                (str_contains($staffDept, 'PPIC') || str_contains($staffDept, 'WAREHOUSE'))) {
                return true;
            }

            if (str_contains($deptUpper, 'HRGA') && str_contains($staffDept, 'HRGA')) {
                return true;
            }

            if ($staffDept !== '' && $deptUpper !== '' && (str_contains($staffDept, $deptUpper) || str_contains($deptUpper, $staffDept))) {
                return true;
            }

            return false;
        })->values();
    }
}
