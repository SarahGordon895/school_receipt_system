<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SmsSendResult;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulated_sms_is_reported_as_skipped(): void
    {
        Setting::query()->create([
            'school_name' => 'Test School',
            'sms_enabled' => true,
            'sms_simulate' => true,
        ]);

        $result = app(SmsService::class)->send('+255700000000', 'Test message');

        $this->assertFalse($result->succeeded());
        $this->assertSame('skipped', $result->status);
    }

    public function test_sms_skipped_when_disabled(): void
    {
        Setting::query()->create([
            'school_name' => 'Test School',
            'sms_enabled' => false,
            'sms_simulate' => true,
        ]);

        $result = app(SmsService::class)->send('+255700000000', 'Test message');

        $this->assertFalse($result->succeeded());
        $this->assertSame('skipped', $result->status);
    }

    public function test_imart_driver_sends_expected_payload(): void
    {
        config([
            'services.sms.driver' => 'imart',
            'services.sms.endpoint' => 'https://smsservice.imartgroup.co.tz/api/v3/sms/send',
            'services.sms.token' => 'test-token',
            'services.sms.sender_id' => 'College',
        ]);

        Setting::query()->create([
            'school_name' => 'Test School',
            'sms_enabled' => true,
            'sms_simulate' => false,
            'sms_api_endpoint' => 'https://smsservice.imartgroup.co.tz/api/v3/sms/send',
            'sms_api_token' => 'test-token',
            'sms_sender_id' => 'College',
        ]);

        Http::fake([
            'smsservice.imartgroup.co.tz/api/v3/sms/send' => Http::response([
                'status' => 'success',
                'data' => ['uid' => 'abc123', 'gateway_status' => 'submitted'],
            ], 200),
            'smsservice.imartgroup.co.tz/api/v3/sms/abc123' => Http::response([
                'data' => ['status' => 'Submitted', 'delivered_count' => 1, 'failed_count' => 0],
            ], 200),
        ]);

        $result = app(SmsService::class)->send('+255655139724', 'Hello parent');

        $this->assertTrue($result->succeeded());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://smsservice.imartgroup.co.tz/api/v3/sms/send'
                && $request['recipient'] === '255655139724'
                && $request['message'] === 'Hello parent'
                && $request['sender_id'] === 'COLLEGE'
                && $request['type'] === 'plain';
        });
    }

    public function test_imart_driver_treats_gateway_acceptance_as_sent_even_when_carrier_reports_failure(): void
    {
        config([
            'services.sms.driver' => 'imart',
            'services.sms.endpoint' => 'https://smsservice.imartgroup.co.tz/api/v3/sms/send',
            'services.sms.token' => 'test-token',
            'services.sms.sender_id' => 'College',
        ]);

        Setting::query()->create([
            'school_name' => 'Test School',
            'sms_enabled' => true,
            'sms_simulate' => false,
            'sms_api_endpoint' => 'https://smsservice.imartgroup.co.tz/api/v3/sms/send',
            'sms_api_token' => 'test-token',
            'sms_sender_id' => 'College',
        ]);

        Http::fake([
            'smsservice.imartgroup.co.tz/api/v3/sms/send' => Http::response([
                'status' => 'success',
                'data' => ['uid' => 'fail123', 'gateway_status' => 'submitted'],
            ], 200),
            'smsservice.imartgroup.co.tz/api/v3/sms/fail123' => Http::response([
                'data' => ['status' => 'Failed', 'delivered_count' => 0, 'failed_count' => 1],
            ], 200),
        ]);

        $result = app(SmsService::class)->send('+255655139724', 'Hello parent');

        $this->assertTrue($result->succeeded());
        $this->assertSame('sent', $result->status);
        $this->assertSame('fail123', $result->gatewayUid);
    }

    public function test_imart_insufficient_balance_is_reported_clearly(): void
    {
        config([
            'services.sms.driver' => 'imart',
            'services.sms.endpoint' => 'https://smsservice.imartgroup.co.tz/api/v3/sms/send',
            'services.sms.token' => 'test-token',
            'services.sms.sender_id' => 'College',
        ]);

        Setting::query()->create([
            'school_name' => 'Test School',
            'sms_enabled' => true,
            'sms_simulate' => false,
            'sms_api_endpoint' => 'https://smsservice.imartgroup.co.tz/api/v3/sms/send',
            'sms_api_token' => 'test-token',
            'sms_sender_id' => 'College',
        ]);

        Http::fake([
            'smsservice.imartgroup.co.tz/api/v3/sms/send' => Http::response([
                'status' => 'error',
                'message' => 'Insufficient SMS balance',
                'code' => 'INSUFFICIENT_SMS_BALANCE',
                'success' => false,
            ], 422),
        ]);

        $result = app(SmsService::class)->send('+255655139724', 'Hello parent');

        $this->assertFalse($result->succeeded());
        $this->assertSame('failed', $result->status);
        $this->assertStringContainsString('insufficient balance', strtolower($result->detail));
    }

    public function test_check_delivery_reports_carrier_status(): void
    {
        config([
            'services.sms.driver' => 'imart',
            'services.sms.endpoint' => 'https://smsservice.imartgroup.co.tz/api/v3/sms/send',
            'services.sms.token' => 'test-token',
        ]);

        Setting::query()->create([
            'school_name' => 'Test School',
            'sms_enabled' => true,
            'sms_simulate' => false,
            'sms_api_endpoint' => 'https://smsservice.imartgroup.co.tz/api/v3/sms/send',
            'sms_api_token' => 'test-token',
        ]);

        Http::fake([
            'smsservice.imartgroup.co.tz/api/v3/sms/uid-99' => Http::response([
                'data' => ['status' => 'Delivered', 'delivered_count' => 1, 'failed_count' => 0],
            ], 200),
        ]);

        $result = app(SmsService::class)->checkDelivery('uid-99');

        $this->assertNotNull($result);
        $this->assertSame(1, $result['delivered_count']);
        $this->assertStringContainsString('delivery', strtolower($result['detail']));
    }
}
