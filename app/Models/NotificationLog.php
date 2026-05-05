<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'student_id',
        'channel',
        'status',
        'sent_on',
        'message',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_on' => 'date',
            'read_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
