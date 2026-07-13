<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankPaymentSubmission extends Model
{
    public const STATUSES = ['pending', 'verified', 'review', 'rejected'];

    protected $fillable = [
        'parent_user_id',
        'student_id',
        'original_filename',
        'file_path',
        'bank',
        'extracted_amount',
        'extracted_reference',
        'extracted_payment_date',
        'extracted_account_number',
        'extracted_raw_text',
        'status',
        'verification_message',
        'receipt_id',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'extracted_payment_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'verified' => 'Verified',
            'review' => 'Needs review',
            'rejected' => 'Rejected',
            default => 'Pending',
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'verified' => 'success',
            'review' => 'warning',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function bankLabel(): string
    {
        return match ($this->bank) {
            'nmb' => 'NMB Bank',
            'crdb' => 'CRDB Bank',
            default => 'Unknown',
        };
    }
}
