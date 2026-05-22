<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->normalized_role, $roles, true);
    }

    public function isParent(): bool
    {
        return $this->normalized_role === 'parent';
    }

    public function getNormalizedRoleAttribute(): string
    {
        return $this->role ?: 'school_admin';
    }

    public function getHomeRouteAttribute(): string
    {
        return $this->isParent() ? 'parent.dashboard' : 'dashboard';
    }

    /**
     * Students officially admitted under this parent/guardian (via student_parent_links).
     */
    public function admittedStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_parent_links', 'parent_user_id', 'student_id')
            ->withPivot(['relationship', 'is_primary', 'parent_phone', 'linked_by_user_id', 'linked_at'])
            ->withTimestamps()
            ->select('students.*');
    }

    /** @deprecated Use admittedStudents() — kept for backward compatibility in codebase */
    public function parentStudents(): BelongsToMany
    {
        return $this->admittedStudents();
    }
}
