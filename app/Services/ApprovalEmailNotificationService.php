<?php

namespace App\Services;

use App\Models\User;
use App\Mail\ApprovalStageNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ApprovalEmailNotificationService
{
    /**
     * Trigger notification when a new form is submitted by a User -> Notify Staff.
     */
    public function notifyStaffOnFormCreated(
        string $formNumber,
        string $moduleName,
        string $requestorName,
        string $requestorDept,
        string $formDate,
        array $items = []
    ): int {
        try {
            $staffApprovers = $this->getStaffApproversForDepartment($requestorDept);

            if ($staffApprovers->isEmpty()) {
                Log::warning("ApprovalNotification: No staff approver found for department '{$requestorDept}' (Form: {$formNumber})");
                return 0;
            }

            $actionUrl = $moduleName === 'Unregistrasi Consumable'
                ? route('saturnus.unregistrasi_approval', ['form' => $formNumber])
                : route('saturnus.proses_approval', ['form' => $formNumber]);

            $stageTitle = "Tahap 1: Butuh Persetujuan Staff / Section Head ({$requestorDept})";
            $sentCount = 0;

            foreach ($staffApprovers as $staff) {
                if (empty($staff->email)) continue;

                $mailable = new ApprovalStageNotificationMail(
                    recipientName: $staff->name,
                    recipientEmail: $staff->email,
                    stageKey: 'staff_needed',
                    stageTitle: $stageTitle,
                    moduleName: $moduleName,
                    formNumber: $formNumber,
                    requestorName: $requestorName,
                    requestorDept: $requestorDept,
                    formDate: $formDate,
                    items: $items,
                    previousApprover: null,
                    previousComment: null,
                    actionUrl: $actionUrl
                );

                Mail::to($staff->email)->send($mailable);
                $sentCount++;
            }

            Log::info("ApprovalNotification: Sent {$sentCount} creation notification(s) to staff for Form {$formNumber}");
            return $sentCount;
        } catch (\Throwable $e) {
            Log::error("ApprovalNotification Error on notifyStaffOnFormCreated: " . $e->getMessage(), [
                'exception' => $e,
                'formNumber' => $formNumber,
            ]);
            return 0;
        }
    }

    /**
     * Trigger notification when Staff approves Registrasi -> Notify Accounting.
     */
    public function notifyAccountingOnStaffApproved(
        string $formNumber,
        string $staffSignerName,
        ?string $staffComment,
        string $requestorName,
        string $requestorDept,
        string $formDate,
        array $items = []
    ): int {
        try {
            $accountingUsers = $this->getAccountingApprovers();

            if ($accountingUsers->isEmpty()) {
                Log::warning("ApprovalNotification: No Accounting approvers found (Form: {$formNumber})");
                return 0;
            }

            $actionUrl = route('saturnus.proses_approval', ['form' => $formNumber]);
            $stageTitle = "Tahap 2: Butuh Persetujuan Accounting & Finance";
            $sentCount = 0;

            foreach ($accountingUsers as $acc) {
                if (empty($acc->email)) continue;

                $mailable = new ApprovalStageNotificationMail(
                    recipientName: $acc->name,
                    recipientEmail: $acc->email,
                    stageKey: 'accounting_needed',
                    stageTitle: $stageTitle,
                    moduleName: 'Registrasi Consumable',
                    formNumber: $formNumber,
                    requestorName: $requestorName,
                    requestorDept: $requestorDept,
                    formDate: $formDate,
                    items: $items,
                    previousApprover: "Staff ({$staffSignerName})",
                    previousComment: $staffComment,
                    actionUrl: $actionUrl
                );

                Mail::to($acc->email)->send($mailable);
                $sentCount++;
            }

            Log::info("ApprovalNotification: Sent {$sentCount} notification(s) to Accounting for Form {$formNumber}");
            return $sentCount;
        } catch (\Throwable $e) {
            Log::error("ApprovalNotification Error on notifyAccountingOnStaffApproved: " . $e->getMessage(), [
                'exception' => $e,
                'formNumber' => $formNumber,
            ]);
            return 0;
        }
    }

    /**
     * Trigger notification when Accounting approves (Registrasi) or Staff approves (Unregistrasi) -> Notify Warehouse Consumable.
     */
    public function notifyWarehouseOnNextStage(
        string $formNumber,
        string $moduleName,
        string $approvedByRole,
        string $approverName,
        ?string $approverComment,
        string $requestorName,
        string $requestorDept,
        string $formDate,
        array $items = []
    ): int {
        try {
            $warehouseUsers = $this->getWarehouseApprovers();

            if ($warehouseUsers->isEmpty()) {
                Log::warning("ApprovalNotification: No Warehouse Consumable approvers found (Form: {$formNumber})");
                return 0;
            }

            $actionUrl = $moduleName === 'Unregistrasi Consumable'
                ? route('saturnus.unregistrasi_approval', ['form' => $formNumber])
                : route('saturnus.proses_approval', ['form' => $formNumber]);

            $stageTitle = $moduleName === 'Unregistrasi Consumable'
                ? "Tahap Akhir: Butuh Verifikasi Warehouse Consumable"
                : "Tahap 3: Butuh Persetujuan Akhir Warehouse Consumable";

            $prevLabel = ucfirst($approvedByRole) . " ({$approverName})";
            $sentCount = 0;

            foreach ($warehouseUsers as $wh) {
                if (empty($wh->email)) continue;

                $mailable = new ApprovalStageNotificationMail(
                    recipientName: $wh->name,
                    recipientEmail: $wh->email,
                    stageKey: 'warehouse_needed',
                    stageTitle: $stageTitle,
                    moduleName: $moduleName,
                    formNumber: $formNumber,
                    requestorName: $requestorName,
                    requestorDept: $requestorDept,
                    formDate: $formDate,
                    items: $items,
                    previousApprover: $prevLabel,
                    previousComment: $approverComment,
                    actionUrl: $actionUrl
                );

                Mail::to($wh->email)->send($mailable);
                $sentCount++;
            }

            Log::info("ApprovalNotification: Sent {$sentCount} notification(s) to Warehouse for Form {$formNumber}");
            return $sentCount;
        } catch (\Throwable $e) {
            Log::error("ApprovalNotification Error on notifyWarehouseOnNextStage: " . $e->getMessage(), [
                'exception' => $e,
                'formNumber' => $formNumber,
            ]);
            return 0;
        }
    }

    /**
     * Trigger confirmation notification to Requestor when Warehouse completes the final approval.
     */
    public function notifyRequestorOnFinalApproval(
        string $formNumber,
        string $moduleName,
        string $warehouseSignerName,
        ?string $warehouseComment,
        ?string $requestorEmail,
        string $requestorName,
        string $requestorDept,
        string $formDate,
        array $items = []
    ): int {
        try {
            if (empty($requestorEmail)) {
                return 0;
            }

            $actionUrl = $moduleName === 'Unregistrasi Consumable'
                ? route('saturnus.unregistrasi_history', ['search' => $formNumber])
                : route('saturnus.data_view', ['search' => $formNumber]);

            $stageTitle = "Selesai: Formulir {$formNumber} Telah Disetujui Sepenuhnya";

            $mailable = new ApprovalStageNotificationMail(
                recipientName: $requestorName,
                recipientEmail: $requestorEmail,
                stageKey: 'completed',
                stageTitle: $stageTitle,
                moduleName: $moduleName,
                formNumber: $formNumber,
                requestorName: $requestorName,
                requestorDept: $requestorDept,
                formDate: $formDate,
                items: $items,
                previousApprover: "Warehouse Consumable ({$warehouseSignerName})",
                previousComment: $warehouseComment,
                actionUrl: $actionUrl
            );

            Mail::to($requestorEmail)->send($mailable);
            Log::info("ApprovalNotification: Sent completion notification to Requestor {$requestorEmail} for Form {$formNumber}");
            return 1;
        } catch (\Throwable $e) {
            Log::error("ApprovalNotification Error on notifyRequestorOnFinalApproval: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Resolve eligible Staff approvers for a given department.
     */
    public function getStaffApproversForDepartment(string $dept)
    {
        $deptUpper = strtoupper(trim($dept));
        $allStaff = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($q) {
                $q->where('role', 'like', '%Staff%')
                  ->orWhere('role', 'like', '%staff%');
            })
            ->get();

        return $allStaff->filter(function ($staff) use ($deptUpper) {
            $staffDept = strtoupper(trim($staff->department ?? ''));
            $staffRole = strtoupper(trim($staff->role ?? ''));

            // Production & Dies Assy combined coverage
            if ((str_contains($deptUpper, 'PRODUCTION') || str_contains($deptUpper, 'DIES')) &&
                (str_contains($staffDept, 'PRODUCTION') || str_contains($staffDept, 'DIES') || str_contains($staffRole, 'PRODUCTION') || str_contains($staffRole, 'DIES'))) {
                return true;
            }

            // Maintenance
            if (str_contains($deptUpper, 'MAINTENANCE') && str_contains($staffDept, 'MAINTENANCE')) {
                return true;
            }

            // PPIC / Warehouse
            if ((str_contains($deptUpper, 'PPIC') || str_contains($deptUpper, 'WAREHOUSE')) &&
                (str_contains($staffDept, 'PPIC') || str_contains($staffDept, 'WAREHOUSE'))) {
                return true;
            }

            // HRGA
            if (str_contains($deptUpper, 'HRGA') && str_contains($staffDept, 'HRGA')) {
                return true;
            }

            // General substring match
            if (!empty($staffDept) && !empty($deptUpper) && (str_contains($staffDept, $deptUpper) || str_contains($deptUpper, $staffDept))) {
                return true;
            }

            return false;
        });
    }

    /**
     * Resolve Accounting approvers.
     */
    public function getAccountingApprovers()
    {
        return User::whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($q) {
                $q->where('role', 'like', '%Accounting%')
                  ->orWhere('role', 'like', '%ACC%')
                  ->orWhere('department', 'like', '%Accounting%');
            })
            ->get();
    }

    /**
     * Resolve Warehouse Consumable approvers.
     */
    public function getWarehouseApprovers()
    {
        return User::whereNotNull('email')
            ->where('email', '!=', '')
            ->where(function ($q) {
                $q->where('role', 'like', '%Warehouse%')
                  ->orWhere('role', 'like', '%WHC%')
                  ->orWhere('role', 'like', '%PPIC Warehouse%');
            })
            ->where('role', 'not like', '%Staff%')
            ->get();
    }
}
