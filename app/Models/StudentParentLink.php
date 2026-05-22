<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentParentLink extends Model
{
    public const RELATIONSHIPS = ['Father', 'Mother', 'Guardian', 'Other'];

    protected $fillable = [
        'student_id',
        'parent_user_id',
        'relationship',
        'is_primary',
        'parent_phone',
        'linked_by_user_id',
        'linked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'linked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by_user_id');
    }
}
