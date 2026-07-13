<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [
        'admission_no',
        'name',
        'class_name',
        'parent_user_id',
        'parent_name',
        'parent_phone',
        'parent_email',
        'fee_due_date',
        'expected_total_fee',
        'admitted_at',
        'registered_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'fee_due_date' => 'date',
            'admitted_at' => 'datetime',
        ];
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function parentLinks(): HasMany
    {
        return $this->hasMany(StudentParentLink::class);
    }

    public function primaryParentLink(): HasOne
    {
        return $this->hasOne(StudentParentLink::class)->where('is_primary', true);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_parent_links', 'student_id', 'parent_user_id')
            ->withPivot(['relationship', 'is_primary', 'parent_phone', 'linked_by_user_id', 'linked_at'])
            ->withTimestamps();
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function feeStructures(): BelongsToMany
    {
        return $this->belongsToMany(FeeStructure::class, 'fee_structure_student');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function scopeForParent(Builder $query, User $parent): Builder
    {
        return $query->whereHas('parentLinks', fn (Builder $q) => $q->where('parent_user_id', $parent->id));
    }

    public function belongsToParent(User $parent): bool
    {
        return \App\Support\ParentStudentAdmission::parentOwnsStudent($parent, $this);
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
        if ($this->relationLoaded('feeStructures')) {
            $fromStructures = (int) $this->feeStructures->sum('amount');
        } else {
            $fromStructures = (int) $this->feeStructures()->sum('amount');
        }

        return $fromStructures > 0 ? $fromStructures : (int) $this->expected_total_fee;
    }

    public function getBalanceAttribute(): int
    {
        return max(0, $this->expected_amount - $this->paid_amount);
    }

    public function isFullyPaid(): bool
    {
        return $this->expected_amount > 0 && $this->balance <= 0;
    }

    public function hasOutstandingBalance(): bool
    {
        return $this->balance > 0;
    }

    public function paymentStatusLabel(): string
    {
        if ($this->expected_amount <= 0) {
            return 'No fees assigned';
        }

        if ($this->isFullyPaid()) {
            return 'Fully paid';
        }

        if ($this->fee_due_date && $this->fee_due_date->isPast()) {
            return 'Overdue';
        }

        return 'Outstanding';
    }

    public function paymentStatusBadge(): string
    {
        return match ($this->paymentStatusLabel()) {
            'Fully paid' => 'success',
            'Overdue' => 'danger',
            'No fees assigned' => 'secondary',
            default => 'warning',
        };
    }

    public function resolveParentPhone(): ?string
    {
        foreach ($this->parentPhoneCandidates() as $phone) {
            if (filled($phone)) {
                return User::normalizePhone($phone);
            }
        }

        return null;
    }

    public function resolveParentEmail(): ?string
    {
        if (filled($this->parent_email)) {
            return $this->parent_email;
        }

        $parent = $this->relationLoaded('parentUser')
            ? $this->parentUser
            : ($this->parent_user_id ? $this->parentUser()->first() : null);

        return $parent?->email;
    }

    public function hasParentContact(): bool
    {
        return $this->resolveParentPhone() !== null || $this->resolveParentEmail() !== null;
    }

    /** @return list<?string> */
    private function parentPhoneCandidates(): array
    {
        $parent = $this->relationLoaded('parentUser')
            ? $this->parentUser
            : ($this->parent_user_id ? $this->parentUser()->first() : null);

        $link = $this->relationLoaded('primaryParentLink')
            ? $this->primaryParentLink
            : $this->primaryParentLink()->first();

        return [
            $this->parent_phone,
            $link?->parent_phone,
            $parent?->phone,
        ];
    }
}
