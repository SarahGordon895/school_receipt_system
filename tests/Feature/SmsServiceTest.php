<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulated_sms_succeeds_when_enabled(): void
    {
        Setting::query()->create([
            'school_name' => 'Test School',
            'sms_enabled' => true,
            'sms_simulate' => true,
        ]);

        $ok = app(SmsService::class)->send('+255700000000', 'Test message');

        $this->assertTrue($ok);
    }

    public function test_sms_skipped_when_disabled(): void
    {
        Setting::query()->create([
            'school_name' => 'Test School',
            'sms_enabled' => false,
            'sms_simulate' => true,
        ]);

        $ok = app(SmsService::class)->send('+255700000000', 'Test message');

        $this->assertFalse($ok);
    }
}
