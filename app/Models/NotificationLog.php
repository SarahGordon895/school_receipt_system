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
        'gateway_uid',
        'delivery_status',
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

    public function isResolvableFailure(): bool
    {
        return in_array($this->status, ['failed', 'skipped'], true)
            && in_array($this->channel, ['sms', 'email'], true)
            && $this->student_id !== null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'sent' => 'Delivered',
            'failed' => 'Failed',
            'skipped' => 'Skipped',
            default => ucfirst((string) $this->status),
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'sent' => 'success',
            'failed' => 'danger',
            'skipped' => 'warning',
            default => 'secondary',
        };
    }
}
