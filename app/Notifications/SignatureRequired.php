<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureRequired extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $assignment;

    /**
     * Create a new notification instance.
     */
    public function __construct($assignment)
    {
        $this->assignment = $assignment;
    }

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
            'assignment_id' => $this->assignment->id,
            'document_id' => $this->assignment->document_id,
            'document_title' => $this->assignment->document->title ?? 'Dokumen Tanpa Judul',
            'message' => 'Anda diminta untuk menandatangani dokumen ini.',
            'url' => route('documents.show', $this->assignment->document_id), // Adjust route as needed
            'type' => 'signature_required'
        ];
    }
}
