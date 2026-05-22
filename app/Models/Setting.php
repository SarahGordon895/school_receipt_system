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
        'sms_enabled',
        'sms_simulate',
        'sms_api_endpoint',
        'sms_api_token',
        'sms_sender_id',
    ];

    protected function casts(): array
    {
        return [
            'sms_enabled' => 'boolean',
            'sms_simulate' => 'boolean',
        ];
    }

    public function smsConfig(): array
    {
        return [
            'enabled' => (bool) $this->sms_enabled,
            'simulate' => (bool) $this->sms_simulate,
            'endpoint' => $this->sms_api_endpoint ?: config('services.sms.endpoint'),
            'token' => $this->sms_api_token ?: config('services.sms.token'),
            'sender_id' => $this->sms_sender_id ?: config('services.sms.sender_id', 'SCHOOL'),
        ];
    }
}
