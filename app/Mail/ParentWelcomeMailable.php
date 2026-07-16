<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParentWelcomeMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $parent,
        public Student $student,
        public string $temporaryPassword,
    ) {
    }

    public function envelope(): Envelope
    {
        $schoolName = Setting::current()?->school_name ?: config('app.name');

        return new Envelope(
            subject: 'Your '.$schoolName.' parent portal account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.parent-welcome',
            with: [
                'schoolName' => Setting::current()?->school_name ?: config('app.name'),
                'loginUrl' => route('login'),
                'profileUrl' => route('profile.edit'),
            ],
        );
    }
}
