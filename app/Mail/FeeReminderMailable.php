<?php

namespace App\Mail;

use App\Models\Student;
use App\Services\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeeReminderMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public string $eventType = NotificationTemplateService::FEE_REMINDER
    ) {
    }

    public function envelope(): Envelope
    {
        $label = app(NotificationTemplateService::class)->eventLabel($this->eventType);

        return new Envelope(
            subject: $label.' - '.$this->student->name,
        );
    }

    public function content(): Content
    {
        $templates = app(NotificationTemplateService::class);
        $message = $templates->render($this->eventType, $this->student);

        return new Content(
            htmlString: view('emails.fee-reminder', [
                'student' => $this->student,
                'balance' => format_tzs($this->student->balance),
                'dueDate' => $this->student->fee_due_date?->format('d/m/Y') ?? 'N/A',
                'message' => $message,
                'eventLabel' => $templates->eventLabel($this->eventType),
            ])->render(),
        );
    }
}
