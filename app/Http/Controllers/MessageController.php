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
        $templates = $templateService->manualTemplateCatalog(Setting::current());

        $parentsUnpaid = \App\Models\User::query()
            ->where('role', 'parent')
            ->whereHas('admittedStudents')
            ->with(['admittedStudents' => fn ($q) => $q->with(['feeStructures'])->withSum('receipts', 'amount')])
            ->get()
            ->filter(function ($parent) {
                return $parent->admittedStudents->contains(
                    fn (Student $s) => $s->balance > 0 && (filled($parent->phone) || filled($parent->email) || $s->hasParentContact())
                );
            });

        $stats = [
            'unpaid_with_contact' => $parentsUnpaid->count(),
            'sms_sent_month' => NotificationLog::query()->where('channel', 'sms')->where('status', 'sent')->whereMonth('sent_on', now()->month)->count(),
            'email_sent_month' => NotificationLog::query()->where('channel', 'email')->where('status', 'sent')->whereMonth('sent_on', now()->month)->count(),
            'failed_month' => NotificationLog::query()->where('status', 'failed')->whereMonth('sent_on', now()->month)->count(),
        ];

        $automatedEvents = [
            ['event' => 'Payment recorded', 'when' => 'Immediately when bursar saves a receipt', 'channels' => 'SMS + Email', 'template' => 'payment_received'],
            ['event' => 'New parent registered', 'when' => 'When admin creates a parent during student admission', 'channels' => 'Email', 'template' => 'parent_welcome'],
            ['event' => 'Student admitted', 'when' => 'When a new student is registered', 'channels' => 'SMS + Email', 'template' => 'fee status'],
            ['event' => '14 days before due', 'when' => 'Daily at 06:00 (school installment schedule)', 'channels' => 'SMS + Email', 'template' => 'fee_reminder_14'],
            ['event' => '7 days before due', 'when' => 'Daily at 06:00', 'channels' => 'SMS + Email', 'template' => 'fee_reminder_7'],
            ['event' => '3 days before due', 'when' => 'Daily at 06:00', 'channels' => 'SMS + Email', 'template' => 'fee_reminder_3'],
            ['event' => 'Due date (today)', 'when' => 'Daily at 06:00', 'channels' => 'SMS + Email', 'template' => 'fee_reminder_due'],
            ['event' => 'Overdue balance', 'when' => 'Daily at 06:00 when installment amount is unpaid', 'channels' => 'SMS + Email', 'template' => 'overdue'],
        ];

        return view('messages.index', compact('templates', 'stats', 'automatedEvents'));
    }
}
