<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FormCommentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailSubject;

    public function __construct(
        public string $recipientName,
        public string $recipientEmail,
        public string $moduleLabel,
        public string $formNumber,
        public string $requestorDept,
        public string $commenterName,
        public string $commenterRole,
        public string $commenterDept,
        public string $commentText,
        public string $actionUrl,
    ) {
        $this->mailSubject = "[KOMENTAR BARU] Form {$formNumber} ({$moduleLabel}) — {$commenterName}";
    }

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

    public function content(): Content
    {
        return new Content(
            view: 'emails.form_comment_notification',
            with: [
                'recipientName'  => $this->recipientName,
                'moduleLabel'    => $this->moduleLabel,
                'formNumber'     => $this->formNumber,
                'requestorDept'  => $this->requestorDept,
                'commenterName'  => $this->commenterName,
                'commenterRole'  => $this->commenterRole,
                'commenterDept'  => $this->commenterDept,
                'commentText'    => $this->commentText,
                'actionUrl'      => $this->actionUrl,
                'appName'        => config('app.name', 'MAI Warehouse Portal'),
                'currentDate'    => Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y H:i') . ' WIB',
            ],
        );
    }
}
