<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeeReminderMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Student $student)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'School Fee Reminder - '.$this->student->name,
        );
    }

    public function content(): Content
    {
        $balance = number_format($this->student->balance);
        $dueDate = $this->student->fee_due_date?->format('Y-m-d') ?? 'as soon as possible';

        return new Content(
            htmlString: view('emails.fee-reminder', [
                'student' => $this->student,
                'balance' => $balance,
                'dueDate' => $dueDate,
            ])->render(),
        );
    }
}
