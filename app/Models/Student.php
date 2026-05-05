<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'admission_no',
        'name',
        'class_name',
        'parent_name',
        'parent_phone',
        'parent_email',
        'fee_due_date',
        'expected_total_fee',
    ];

    protected function casts(): array
    {
        return [
            'fee_due_date' => 'date',
        ];
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function feeStructures()
    {
        return $this->belongsToMany(FeeStructure::class, 'fee_structure_student');
    }

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function getPaidAmountAttribute(): int
    {
        if (array_key_exists('receipts_sum_amount', $this->attributes)) {
            return (int) ($this->attributes['receipts_sum_amount'] ?? 0);
        }

        return (int) $this->receipts()->sum('amount');
    }

    public function getExpectedAmountAttribute(): int
    {
        $fromStructures = (int) $this->feeStructures()->sum('amount');
        return $fromStructures > 0 ? $fromStructures : (int) $this->expected_total_fee;
    }

    public function getBalanceAttribute(): int
    {
        return max(0, $this->expected_amount - $this->paid_amount);
    }
}
