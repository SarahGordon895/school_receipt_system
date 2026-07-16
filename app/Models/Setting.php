<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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
        'sms_template_payment_received',
        'sms_template_fee_reminder',
        'sms_template_fee_reminder_14',
        'sms_template_overdue',
        'bank_nmb_account_name',
        'bank_nmb_account_number',
        'bank_crdb_account_name',
        'bank_crdb_account_number',
        'fee_installment_day',
        'fee_installment_months',
    ];

    protected function casts(): array
    {
        return [
            'sms_enabled' => 'boolean',
            'sms_simulate' => 'boolean',
            'fee_installment_day' => 'integer',
            'fee_installment_months' => 'array',
        ];
    }

    public function smsConfig(): array
    {
        return [
            'enabled' => (bool) $this->sms_enabled,
            'simulate' => (bool) $this->sms_simulate,
            'endpoint' => $this->sms_api_endpoint ?: config('services.sms.endpoint'),
            'token' => $this->sms_api_token ?: config('services.sms.token'),
            'sender_id' => strtoupper((string) ($this->sms_sender_id ?: config('services.sms.sender_id', 'SCHOOL'))),
        ];
    }

    public static function current(): ?self
    {
        try {
            return Cache::remember('app_setting', 3600, fn () => static::query()->first());
        } catch (\Throwable) {
            return static::query()->first();
        }
    }

    public static function forgetCache(): void
    {
        Cache::forget('app_setting');
    }
}
