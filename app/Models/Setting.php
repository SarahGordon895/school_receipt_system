<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'school_name',
        'contact_phone',
        'contact_email',
        'address',
        'reg_number',
        'logo_path',
        'receipt_footer',
    ];

}
