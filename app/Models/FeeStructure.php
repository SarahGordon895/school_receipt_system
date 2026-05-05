<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    protected $fillable = [
        'name',
        'class_name',
        'amount',
        'due_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'fee_structure_student');
    }
}
