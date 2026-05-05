<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParentDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $students = $request->user()
            ->parentStudents()
            ->withSum('receipts', 'amount')
            ->orderBy('name')
            ->get();

        $portfolio = [
            'students_count' => $students->count(),
            'expected_total' => $students->sum(fn($s) => $s->expected_amount),
            'paid_total' => $students->sum(fn($s) => $s->paid_amount),
            'balance_total' => $students->sum(fn($s) => $s->balance),
            'due_soon_count' => $students->filter(function ($s) {
                return $s->balance > 0
                    && $s->fee_due_date
                    && $s->fee_due_date->isFuture()
                    && $s->fee_due_date->lte(now()->addDays(7));
            })->count(),
            'overdue_count' => $students->filter(fn($s) => $s->balance > 0 && $s->fee_due_date && $s->fee_due_date->isPast())->count(),
        ];

        return view('parents.dashboard', compact('students', 'portfolio'));
    }

    public function showStudent(Request $request, Student $student)
    {
        abort_unless(
            strcasecmp((string) $student->parent_email, (string) $request->user()->email) === 0,
            403,
            'You are not authorized to access this student record.'
        );

        $student->loadSum('receipts', 'amount');

        $receipts = $student->receipts()
            ->with(['paymentCategories'])
            ->latest('payment_date')
            ->paginate(15)
            ->withQueryString();

        return view('parents.student-history', compact('student', 'receipts'));
    }

    public function notifications(Request $request)
    {
        $studentQuery = $request->user()->parentStudents()->select(['id', 'name', 'admission_no'])->orderBy('name');
        $students = $studentQuery->get();
        $studentIds = $students->pluck('id');

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'channel' => ['nullable', 'in:email,sms'],
            'status' => ['nullable', 'in:sent,failed,skipped'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'student_id' => ['nullable', 'integer', Rule::in($studentIds->all())],
        ]);

        $logs = NotificationLog::query()
            ->with(['student:id,name,admission_no'])
            ->whereIn('student_id', $studentIds)
            ->when(!empty($validated['student_id']), fn($q) => $q->where('student_id', $validated['student_id']))
            ->when(!empty($validated['channel']), fn($q) => $q->where('channel', $validated['channel']))
            ->when(!empty($validated['status']), fn($q) => $q->where('status', $validated['status']))
            ->when(!empty($validated['from']), fn($q) => $q->whereDate('sent_on', '>=', $validated['from']))
            ->when(!empty($validated['to']), fn($q) => $q->whereDate('sent_on', '<=', $validated['to']))
            ->when(!empty($validated['q']), function ($q) use ($validated) {
                $term = '%' . trim((string) $validated['q']) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('message', 'like', $term)
                        ->orWhereHas('student', fn($sq) => $sq
                            ->where('name', 'like', $term)
                            ->orWhere('admission_no', 'like', $term));
                });
            })
            ->latest('sent_on')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $filters = $request->only(['q', 'channel', 'status', 'from', 'to', 'student_id']);

        $baseStatsQuery = NotificationLog::query()->whereIn('student_id', $studentIds);
        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'sent' => (clone $baseStatsQuery)->where('status', 'sent')->count(),
            'failed' => (clone $baseStatsQuery)->where('status', 'failed')->count(),
            'sms' => (clone $baseStatsQuery)->where('channel', 'sms')->count(),
            'email' => (clone $baseStatsQuery)->where('channel', 'email')->count(),
            'unread' => (clone $baseStatsQuery)->whereNull('read_at')->count(),
        ];

        return view('parents.notifications', compact('logs', 'filters', 'students', 'stats'));
    }

    public function markNotificationRead(Request $request, NotificationLog $log)
    {
        abort_unless(
            strcasecmp((string) $log->student?->parent_email, (string) $request->user()->email) === 0,
            403,
            'You are not authorized to access this notification record.'
        );

        if (!$log->read_at) {
            $log->update(['read_at' => now()]);
        }

        return back()->with('status', 'Notification marked as read.');
    }

    public function markAllNotificationsRead(Request $request)
    {
        $studentIds = $request->user()->parentStudents()->pluck('id');

        $updated = NotificationLog::query()
            ->whereIn('student_id', $studentIds)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', $updated > 0
            ? "Marked {$updated} notification(s) as read."
            : 'No unread notifications to mark.');
    }
}
