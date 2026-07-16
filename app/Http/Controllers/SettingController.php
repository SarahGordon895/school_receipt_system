<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $setting = Setting::firstOrCreate([]);
        return view('settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'reg_number' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'receipt_footer' => ['nullable', 'string', 'max:1000'],
            'sms_enabled' => ['nullable', 'boolean'],
            'sms_simulate' => ['nullable', 'boolean'],
            'sms_api_endpoint' => ['nullable', 'string', 'max:500'],
            'sms_api_token' => ['nullable', 'string', 'max:500'],
            'sms_sender_id' => ['nullable', 'string', 'max:32'],
            'sms_template_payment_received' => ['nullable', 'string', 'max:500'],
            'sms_template_fee_reminder' => ['nullable', 'string', 'max:500'],
            'sms_template_fee_reminder_14' => ['nullable', 'string', 'max:500'],
            'sms_template_overdue' => ['nullable', 'string', 'max:500'],
            'sms_test_phone' => ['nullable', 'string', 'max:50'],
            'bank_nmb_account_name' => ['nullable', 'string', 'max:255'],
            'bank_nmb_account_number' => ['nullable', 'string', 'max:32'],
            'bank_crdb_account_name' => ['nullable', 'string', 'max:255'],
            'bank_crdb_account_number' => ['nullable', 'string', 'max:32'],
            'fee_installment_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'fee_installment_months' => ['nullable', 'array', 'min:1'],
            'fee_installment_months.*' => ['integer', 'min:1', 'max:12'],
        ]);

        $setting = Setting::firstOrCreate([]);

        $data['sms_enabled'] = $request->boolean('sms_enabled');
        $data['sms_simulate'] = $request->boolean('sms_simulate');
        $data['fee_installment_day'] = (int) ($data['fee_installment_day'] ?? 15);
        $months = collect($data['fee_installment_months'] ?? [1, 6, 9])
            ->map(fn ($m) => (int) $m)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $data['fee_installment_months'] = $months !== [] ? $months : [1, 6, 9];
        if (! empty($data['sms_sender_id'])) {
            $data['sms_sender_id'] = strtoupper($data['sms_sender_id']);
        }
        if (empty($data['sms_api_token'])) {
            unset($data['sms_api_token']);
        }

        if ($request->boolean('remove_logo')) {
            if ($setting->logo_path && \Storage::disk('public')->exists($setting->logo_path)) {
                \Storage::disk('public')->delete($setting->logo_path);
            }
            $data['logo_path'] = null;
        } elseif ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_path'] = $path;
        }

        $setting->update($data);
        Setting::forgetCache();

        if ($request->filled('sms_test_phone')) {
            $result = app(SmsService::class)->send(
                $request->input('sms_test_phone'),
                'FTRS test SMS from '.($setting->school_name ?? 'School').'. Configuration is working.'
            );

            return back()->with(
                'status',
                $result->succeeded()
                    ? 'Settings saved. Test SMS was sent successfully.'
                    : ($result->status === 'skipped'
                        ? 'Settings saved. '.$result->detail
                        : 'Settings saved, but test SMS failed: '.$result->detail)
            );
        }

        return back()->with('status', 'Settings updated.');
    }
}
