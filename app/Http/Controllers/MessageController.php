<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Student;
use App\Services\NotificationTemplateService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $templateService = app(NotificationTemplateService::class);
        $setting = Setting::current();
        $defaults = $templateService->defaultTemplates();

        $templates = [];
        foreach (NotificationTemplateService::manualSendEventTypes() as $type) {
            $templates[$type] = [
                'label' => $templateService->eventLabel($type),
                'body' => $this->resolveTemplatePreview($type, $defaults, $setting),
            ];
        }

        $studentsWithBalance = Student::query()
            ->with(['feeStructures'])
            ->withSum('receipts', 'amount')
            ->get()
            ->filter(fn (Student $s) => $s->balance > 0 && $s->hasParentContact());

        $stats = [
            'unpaid_with_contact' => $studentsWithBalance->count(),
            'sms_sent_month' => NotificationLog::query()->where('channel', 'sms')->where('status', 'sent')->whereMonth('sent_on', now()->month)->count(),
            'email_sent_month' => NotificationLog::query()->where('channel', 'email')->where('status', 'sent')->whereMonth('sent_on', now()->month)->count(),
            'failed_month' => NotificationLog::query()->where('status', 'failed')->whereMonth('sent_on', now()->month)->count(),
        ];

        $automatedEvents = [
            ['event' => 'Payment recorded', 'when' => 'Immediately when bursar saves a receipt', 'channels' => 'SMS + Email', 'template' => 'payment_received'],
            ['event' => 'Student admitted', 'when' => 'When a new student is registered', 'channels' => 'SMS + Email', 'template' => 'fee_reminder_14'],
            ['event' => '14 days before due', 'when' => 'Daily at 06:00 (exact date match)', 'channels' => 'SMS + Email', 'template' => 'fee_reminder_14'],
            ['event' => '7 days before due', 'when' => 'Daily at 06:00', 'channels' => 'SMS + Email', 'template' => 'fee_reminder_7'],
            ['event' => '3 days before due', 'when' => 'Daily at 06:00', 'channels' => 'SMS + Email', 'template' => 'fee_reminder_3'],
            ['event' => 'Due date (today)', 'when' => 'Daily at 06:00', 'channels' => 'SMS + Email', 'template' => 'fee_reminder_due'],
            ['event' => 'Overdue balance', 'when' => 'Daily at 06:00 after due date passed', 'channels' => 'SMS + Email', 'template' => 'overdue'],
        ];

        return view('messages.index', compact('templates', 'stats', 'automatedEvents'));
    }

    /** @param array<string, string> $defaults */
    private function resolveTemplatePreview(string $eventType, array $defaults, ?Setting $setting): string
    {
        return match ($eventType) {
            NotificationTemplateService::PAYMENT_RECEIVED => $setting?->sms_template_payment_received ?: $defaults[NotificationTemplateService::PAYMENT_RECEIVED],
            NotificationTemplateService::FEE_REMINDER_14 => $setting?->sms_template_fee_reminder_14 ?: $defaults[NotificationTemplateService::FEE_REMINDER_14],
            NotificationTemplateService::OVERDUE => $setting?->sms_template_overdue ?: $defaults[NotificationTemplateService::OVERDUE],
            default => $setting?->sms_template_fee_reminder ?: ($defaults[$eventType] ?? $defaults[NotificationTemplateService::FEE_REMINDER]),
        };
    }
}
