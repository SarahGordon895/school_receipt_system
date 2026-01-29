<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    protected $table = 'classes';
    protected $fillable = ['name'];

    public function streams()
    {
        return $this->hasMany(Stream::class, 'class_id');
    }
}
