<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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

    public function parentStudents()
    {
        return $this->hasMany(Student::class, 'parent_email', 'email');
    }
}
