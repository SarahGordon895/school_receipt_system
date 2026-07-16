<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchParentReminderRequest;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationTemplateService;
use App\Services\ParentReminderService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationLogController extends Controller
{
    public function __construct(
        private ParentReminderService $parentReminderService,
        private SmsService $smsService
    ) {
    }

    public function index(Request $request)
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'channel' => ['nullable', 'in:email,sms'],
            'status' => ['nullable', 'in:sent,failed,skipped'],
            'student_id' => ['nullable', 'exists:students,id'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $baseQuery = NotificationLog::query()
            ->when($dateFrom, fn ($q) => $q->whereDate('sent_on', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('sent_on', '<=', $dateTo))
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->channel))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
            ->when(trim((string) $request->get('q', '')) !== '', function ($q) use ($request) {
                $term = '%'.trim($request->q).'%';
                $q->whereHas('student', fn ($sq) => $sq
                    ->where('name', 'like', $term)
                    ->orWhere('admission_no', 'like', $term));
            });

        $stats = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $logs = (clone $baseQuery)
            ->with(['student:id,name,admission_no'])
            ->latest('sent_on')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $studentIds = NotificationLog::query()->distinct()->pluck('student_id');
        $students = Student::query()
            ->select('id', 'name', 'admission_no')
            ->whereIn('id', $studentIds)
            ->orderBy('name')
            ->get();

        return view('notification-logs.index', [
            'logs' => $logs,
            'students' => $students,
            'stats' => $stats,
            'filters' => $request->only(['date_from', 'date_to', 'channel', 'status', 'student_id', 'q']),
        ]);
    }

    public function create()
    {
        return view('notification-logs.create', [
            'students' => Student::orderBy('name')->get(['id', 'name', 'admission_no']),
            'log' => new NotificationLog([
                'sent_on' => now()->toDateString(),
                'channel' => 'sms',
                'status' => 'sent',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedLogData($request);
        NotificationLog::create($data);

        return redirect()
            ->route('notification-logs.index')
            ->with('status', 'Reminder log recorded.');
    }

    public function show(NotificationLog $notification_log)
    {
        $notification_log->load('student:id,name,admission_no,class_name,parent_name,parent_email,parent_phone');

        return view('notification-logs.show', ['log' => $notification_log]);
    }

    public function edit(NotificationLog $notification_log)
    {
        return view('notification-logs.edit', [
            'log' => $notification_log,
            'students' => Student::orderBy('name')->get(['id', 'name', 'admission_no']),
        ]);
    }

    public function update(Request $request, NotificationLog $notification_log)
    {
        $notification_log->update($this->validatedLogData($request));

        return redirect()
            ->route('notification-logs.show', $notification_log)
            ->with('status', 'Reminder log updated.');
    }

    public function destroy(NotificationLog $notification_log)
    {
        $notification_log->delete();

        return back()->with('status', 'Reminder log deleted.');
    }

    public function resend(NotificationLog $notification_log)
    {
        if (! $notification_log->isResolvableFailure()) {
            return back()->withErrors([
                'resend' => 'Only failed or skipped reminders can be resent.',
            ]);
        }

        $results = $this->parentReminderService->resendLog($notification_log->fresh());

        $sendSms = $notification_log->channel === 'sms';
        $channel = $sendSms ? 'SMS' : 'Email';
        $succeeded = $sendSms ? $results['sms'] === true : $results['email'] === true;
        $detail = $sendSms ? ($results['sms_detail'] ?? null) : ($results['email_detail'] ?? null);

        if ($succeeded) {
            return back()->with('status', "Resent {$channel} successfully. Status updated to delivered. {$detail}");
        }

        $failure = $detail ?? "Resend {$channel} failed. Check parent contact details and Settings.";

        return back()->withErrors(['resend' => $failure]);
    }

    public function refreshStatus(NotificationLog $notification_log)
    {
        if ($notification_log->channel !== 'sms' || ! $notification_log->gateway_uid) {
            return back()->withErrors([
                'refresh' => 'Delivery status refresh is only available for SMS logs with a gateway reference.',
            ]);
        }

        $delivery = $this->smsService->checkDelivery($notification_log->gateway_uid);

        if ($delivery === null) {
            return back()->withErrors([
                'refresh' => 'Could not reach the SMS gateway. Try again shortly.',
            ]);
        }

        $delivered = $delivery['delivered_count'] > 0
            || in_array($delivery['status'], ['delivered', 'success', 'sent', 'submitted'], true);

        $notification_log->update([
            'delivery_status' => $delivery['status'],
            'status' => $delivered ? 'sent' : $notification_log->status,
            'message' => trim(($notification_log->message ?? '').' | Gateway check: '.$delivery['detail'].' ('.now()->format('Y-m-d H:i').')'),
        ]);

        if ($delivered) {
            return back()->with('status', 'Delivery confirmed by gateway. Log status updated to delivered.');
        }

        return back()->with('status', 'Gateway status refreshed: '.$delivery['detail']);
    }

    public function markDelivered(NotificationLog $notification_log)
    {
        if (! in_array($notification_log->status, ['failed', 'skipped'], true)) {
            return back()->withErrors([
                'mark' => 'Only failed or skipped reminders can be marked as delivered.',
            ]);
        }

        $notification_log->update([
            'status' => 'sent',
            'delivery_status' => 'confirmed_by_admin',
            'message' => trim(($notification_log->message ?? '').' | Marked delivered by admin on '.now()->format('Y-m-d H:i').'.'),
        ]);

        return back()->with('status', 'Reminder marked as delivered. Parent confirmed receipt.');
    }

    public function sendCreate(Request $request)
    {
        $templateService = app(NotificationTemplateService::class);
        $setting = Setting::current();
        $catalog = $templateService->manualTemplateCatalog($setting);

        $parents = User::query()
            ->where('role', 'parent')
            ->whereHas('admittedStudents')
            ->with(['admittedStudents' => function ($query) {
                $query->with(['feeStructures', 'parentUser', 'primaryParentLink'])
                    ->withSum('receipts', 'amount')
                    ->orderBy('name');
            }])
            ->orderBy('name')
            ->get()
            ->map(function (User $parent) use ($templateService) {
                $students = $parent->admittedStudents;
                $focus = $students
                    ->sortBy(fn (Student $student) => $templateService->statusPriority(
                        $templateService->suggestManualEventTypeForStudent($student)
                    ))
                    ->first();

                $suggestedType = $focus
                    ? $templateService->suggestManualEventTypeForStudent($focus)
                    : NotificationTemplateService::FEE_REMINDER;

                $hasContact = filled($parent->phone) || filled($parent->email)
                    || ($focus && $focus->hasParentContact());

                return [
                    'parent' => $parent,
                    'students' => $students,
                    'focus_student' => $focus,
                    'suggested_type' => $suggestedType,
                    'suggested_label' => $templateService->eventLabel($suggestedType),
                    'priority' => $templateService->statusPriority($suggestedType),
                    'balance' => (int) ($focus?->balance ?? 0),
                    'due_date' => $focus?->resolveFeeDueDate()->format('d/m/Y'),
                    'days_until' => $focus
                        ? now()->startOfDay()->diffInDays($focus->resolveFeeDueDate()->startOfDay(), false)
                        : null,
                    'has_contact' => $hasContact,
                ];
            })
            ->filter(fn (array $row) => $row['has_contact'])
            ->sortBy([
                ['priority', 'asc'],
                ['balance', 'desc'],
            ])
            ->values();

        $selectedParentId = $request->integer('parent_user_id') ?: null;
        if (! $selectedParentId && $request->integer('student_id')) {
            $selectedParentId = Student::query()->find($request->integer('student_id'))?->parent_user_id;
        }

        $seedSuggested = null;
        if ($selectedParentId) {
            $seedSuggested = $parents->firstWhere(fn ($row) => (int) $row['parent']->id === $selectedParentId)['suggested_type'] ?? null;
        }
        if (! $seedSuggested && $parents->isNotEmpty()) {
            $seedSuggested = $parents->first()['suggested_type'];
        }

        $requestedType = $request->string('message_type')->toString();
        $selectedMessageType = $requestedType !== ''
            ? $requestedType
            : ($seedSuggested ?: 'auto');

        $eventTypes = array_merge(
            ['auto'],
            $templateService->eventTypesOrderedForSuggestion(
                $selectedMessageType === 'auto' ? $seedSuggested : $selectedMessageType
            )
        );

        $templates = collect($catalog)
            ->mapWithKeys(fn ($item, $type) => [$type => $item['body']])
            ->all();

        return view('notification-logs.send', [
            'parents' => $parents,
            'selectedParentId' => $selectedParentId,
            'selectedMessageType' => $selectedMessageType,
            'templates' => $templates,
            'templateCatalog' => $catalog,
            'eventTypes' => $eventTypes,
            'eventLabels' => collect($eventTypes)
                ->mapWithKeys(fn ($t) => [$t => $templateService->eventLabel($t)])
                ->all(),
            'maxBatchParents' => (int) config('notifications.max_batch_parents', 5),
            'minBatchParents' => (int) config('notifications.min_batch_parents', 1),
        ]);
    }

    public function sendStore(BatchParentReminderRequest $request)
    {
        $data = $request->validated();

        $parents = User::query()
            ->where('role', 'parent')
            ->whereIn('id', $data['parent_user_ids'])
            ->get();

        $messages = $this->parentReminderService->sendBatchToParents(
            $parents,
            $request->sendSms(),
            $request->sendEmail(),
            $data['message_type'] ?? 'auto'
        );

        return redirect()
            ->route('notification-logs.index')
            ->with('status', 'Bulk send complete. '.implode(' | ', $messages));
    }

    public function sendToStudent(Request $request, Student $student)
    {
        $request->validate([
            'send_sms' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $student->loadMissing(['parentUser', 'primaryParentLink'])->loadSum('receipts', 'amount');

        $sendSms = $request->boolean('send_sms', true);
        $sendEmail = $request->boolean('send_email', true);

        $results = $this->parentReminderService->sendFeeReminder(
            $student,
            $sendSms,
            $sendEmail,
            null,
            true,
            app(NotificationTemplateService::class)->resolveEventTypeForStudent($student)
        );

        return back()->with('status', $this->buildSendStatusMessage($results));
    }

    /** @param array{sms: ?bool, email: ?bool, errors: array<string, string>, sms_to?: ?string, email_to?: ?string, sms_detail?: ?string, email_detail?: ?string} $results */
    private function buildSendStatusMessage(array $results): string
    {
        $parts = [];

        if ($results['sms'] === true) {
            $parts[] = $results['sms_detail'] ?? ('SMS sent to '.($results['sms_to'] ?? 'parent phone'));
        } elseif ($results['sms'] === false) {
            $parts[] = $results['sms_detail'] ?? ('SMS failed for '.($results['sms_to'] ?? 'parent phone'));
        } elseif (isset($results['errors']['sms'])) {
            $parts[] = 'SMS skipped ('.$results['errors']['sms'].')';
        }

        if ($results['email'] === true) {
            $parts[] = $results['email_detail'] ?? ('Email sent to '.($results['email_to'] ?? 'parent'));
        } elseif ($results['email'] === false) {
            $parts[] = $results['email_detail'] ?? ('Email failed for '.($results['email_to'] ?? 'parent'));
        } elseif (isset($results['errors']['email'])) {
            $parts[] = 'Email skipped ('.$results['errors']['email'].')';
        }

        if ($parts === []) {
            return 'No reminder was sent.';
        }

        return 'Reminder result: '.implode(' | ', $parts);
    }

    /** @return array<string, mixed> */
    private function validatedLogData(Request $request): array
    {
        return $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')],
            'channel' => ['required', Rule::in(['email', 'sms'])],
            'status' => ['required', Rule::in(['sent', 'failed', 'skipped'])],
            'sent_on' => ['required', 'date'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
