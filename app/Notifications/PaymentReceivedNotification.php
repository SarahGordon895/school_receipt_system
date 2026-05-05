<?php

namespace App\Notifications;

use App\Models\Receipt;
use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Receipt $receipt,
        public Student $student
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $balance = number_format($this->student->balance);
        $amount = number_format($this->receipt->amount);
        $date = $this->receipt->payment_date;

        $lines = (new MailMessage)
            ->subject('Payment received — ' . $this->receipt->receipt_no)
            ->greeting('Dear Parent/Guardian,')
            ->line('We have recorded a fee payment for ' . $this->student->name . '.')
            ->line('Receipt number: ' . $this->receipt->receipt_no)
            ->line('Amount paid: Tsh ' . $amount)
            ->line('Payment date: ' . $date)
            ->line('Current outstanding balance: Tsh ' . $balance);

        if ($this->receipt->paymentCategories->isNotEmpty()) {
            $breakdown = $this->receipt->paymentCategories
                ->map(fn ($c) => $c->name . ': Tsh ' . number_format($c->pivot->amount))
                ->implode('; ');
            $lines->line('Breakdown: ' . $breakdown);
        }

        return $lines->salutation('School Administration');
    }
}
