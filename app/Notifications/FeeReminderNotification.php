<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeeReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Student $student)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $balance = number_format($this->student->balance);
        $dueDate = $this->student->fee_due_date?->format('Y-m-d') ?? 'as soon as possible';

        return (new MailMessage)
            ->subject('School Fee Reminder - ' . $this->student->name)
            ->greeting('Dear Parent/Guardian,')
            ->line('This is a reminder for student: ' . $this->student->name)
            ->line('Outstanding balance: Tsh ' . $balance)
            ->line('Due date: ' . $dueDate)
            ->line('Please make payment on time to avoid delays in school services.')
            ->salutation('School Administration');
    }
}
