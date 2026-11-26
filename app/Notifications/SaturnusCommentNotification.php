<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class SaturnusCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $module,
        public readonly string $formNumber,
        public readonly int $commentId,
        public readonly int $commenterId,
        public readonly string $commenterName,
        public readonly string $commenterRole,
        public readonly string $commenterDept,
        public readonly string $commentText,
        public readonly string $url,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'saturnus_comment',
            'module'          => $this->module,
            'form_number'     => $this->formNumber,
            'comment_id'      => $this->commentId,
            'commenter_id'    => $this->commenterId,
            'commenter_name'  => $this->commenterName,
            'commenter_role'  => $this->commenterRole,
            'commenter_dept'  => $this->commenterDept,
            'comment_excerpt' => Str::limit($this->commentText, 120),
            'comment_full'    => $this->commentText,
            'url'             => $this->url,
            'created_at'      => now()->toIso8601String(),
        ];
    }
}
