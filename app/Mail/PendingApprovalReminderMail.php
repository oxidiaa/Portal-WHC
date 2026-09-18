<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class PendingApprovalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientName;
    public string $recipientEmail;
    public string $senderName;
    public string $senderDept;
    public ?string $customMessage;
    public string $priority;
    public array $pendingForms;
    public string $mailSubject;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $recipientName,
        string $recipientEmail,
        string $senderName,
        string $senderDept,
        ?string $customMessage = null,
        string $priority = 'Normal',
        array $pendingForms = [],
        ?string $subject = null
    ) {
        $this->recipientName = $recipientName;
        $this->recipientEmail = $recipientEmail;
        $this->senderName = $senderName;
        $this->senderDept = $senderDept;
        $this->customMessage = $customMessage;
        $this->priority = $priority;
        $this->pendingForms = $pendingForms;

        $formCount = count($pendingForms);
        $prefix = match(strtolower($priority)) {
            'urgent' => '[URGENT] ',
            'final', 'final reminder' => '[FINAL NOTICE] ',
            default => '[PENGINGAT] '
        };

        $this->mailSubject = $subject ?: ($prefix . "Pemberitahuan Formulir Menunggu Persetujuan Anda ({$formCount} Form) — SATURNUS MAI");
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
            view: 'emails.pending_approval_reminder',
            with: [
                'recipientName' => $this->recipientName,
                'recipientEmail' => $this->recipientEmail,
                'senderName' => $this->senderName,
                'senderDept' => $this->senderDept,
                'customMessage' => $this->customMessage,
                'priority' => $this->priority,
                'pendingForms' => $this->pendingForms,
                'totalForms' => count($this->pendingForms),
                'appName' => config('app.name', 'MAI Warehouse Portal'),
                'currentDate' => \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y H:i') . ' WIB',
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
