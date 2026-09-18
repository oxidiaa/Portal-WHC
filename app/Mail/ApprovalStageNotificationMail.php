<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class ApprovalStageNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientName;
    public string $recipientEmail;
    public string $stageKey;
    public string $stageTitle;
    public string $moduleName;
    public string $formNumber;
    public string $requestorName;
    public string $requestorDept;
    public string $formDate;
    public array $items;
    public ?string $previousApprover;
    public ?string $previousComment;
    public string $actionUrl;
    public string $mailSubject;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $recipientName,
        string $recipientEmail,
        string $stageKey,
        string $stageTitle,
        string $moduleName,
        string $formNumber,
        string $requestorName,
        string $requestorDept,
        string $formDate,
        array $items = [],
        ?string $previousApprover = null,
        ?string $previousComment = null,
        ?string $actionUrl = null,
        ?string $customSubject = null
    ) {
        $this->recipientName = $recipientName;
        $this->recipientEmail = $recipientEmail;
        $this->stageKey = $stageKey;
        $this->stageTitle = $stageTitle;
        $this->moduleName = $moduleName;
        $this->formNumber = $formNumber;
        $this->requestorName = $requestorName;
        $this->requestorDept = $requestorDept;
        $this->formDate = $formDate;
        $this->items = $items;
        $this->previousApprover = $previousApprover;
        $this->previousComment = $previousComment;
        $this->actionUrl = $actionUrl ?: url('/saturnus');

        // Default Subject Generator
        if (!empty($customSubject)) {
            $this->mailSubject = $customSubject;
        } else {
            $this->mailSubject = match ($stageKey) {
                'staff_needed' => "[NOTIFIKASI APPROVAL] Formulir Baru {$formNumber} ({$requestorDept}) Menunggu Persetujuan Anda (Tahap 1 - Staff)",
                'accounting_needed' => "[NOTIFIKASI APPROVAL] Formulir {$formNumber} Disetujui Staff — Menunggu Persetujuan Accounting (Tahap 2)",
                'warehouse_needed' => $moduleName === 'Unregistrasi Consumable'
                    ? "[NOTIFIKASI APPROVAL] Formulir Unregistrasi {$formNumber} Disetujui Staff — Menunggu Verifikasi Akhir Warehouse"
                    : "[NOTIFIKASI APPROVAL] Formulir {$formNumber} Disetujui Accounting — Menunggu Persetujuan Akhir Warehouse",
                'completed' => "[SELESAI] Formulir {$formNumber} Telah Disetujui Sepenuhnya oleh Warehouse Consumable",
                default => "[NOTIFIKASI APPROVAL] Formulir {$formNumber} Menunggu Tindakan Anda",
            };
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            from: new Address(
                config('mail.from.address', 'noreply@metalart-astra.co.id'),
                config('mail.from.name', 'PT Metalart Astra Indonesia')
            ),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.approval_stage_notification',
            with: [
                'recipientName' => $this->recipientName,
                'recipientEmail' => $this->recipientEmail,
                'stageKey' => $this->stageKey,
                'stageTitle' => $this->stageTitle,
                'moduleName' => $this->moduleName,
                'formNumber' => $this->formNumber,
                'requestorName' => $this->requestorName,
                'requestorDept' => $this->requestorDept,
                'formDate' => $this->formDate,
                'items' => $this->items,
                'totalItems' => count($this->items),
                'previousApprover' => $this->previousApprover,
                'previousComment' => $this->previousComment,
                'actionUrl' => $this->actionUrl,
                'appName' => config('app.name', 'MAI Warehouse Portal'),
                'currentDate' => Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y H:i') . ' WIB',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
