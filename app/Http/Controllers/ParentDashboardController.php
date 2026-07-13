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
        $parent = $request->user();

        $students = $parent->parentStudents()
            ->with(['feeStructures'])
            ->withSum('receipts', 'amount')
            ->orderBy('name')
            ->get();

        $portfolio = [
            'students_count' => $students->count(),
            'expected_total' => $students->sum(fn ($s) => $s->expected_amount),
            'paid_total' => $students->sum(fn ($s) => $s->paid_amount),
            'balance_total' => $students->sum(fn ($s) => $s->balance),
            'due_soon_count' => $students->filter(function ($s) {
                return $s->balance > 0
                    && $s->fee_due_date
                    && $s->fee_due_date->isFuture()
                    && $s->fee_due_date->lte(now()->addDays(7));
            })->count(),
            'overdue_count' => $students->filter(
                fn ($s) => $s->balance > 0 && $s->fee_due_date && $s->fee_due_date->isPast()
            )->count(),
        ];

        return view('parents.dashboard', compact('students', 'portfolio', 'parent'));
    }

    public function showStudent(Request $request, Student $student)
    {
        $student->load(['feeStructures', 'parentUser', 'primaryParentLink']);
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
        $parent = $request->user();
        $students = $parent->parentStudents()
            ->select(['students.id', 'students.name', 'students.admission_no', 'students.class_name'])
            ->orderBy('students.name')
            ->get();
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
            ->with(['student:id,name,admission_no,class_name'])
            ->whereIn('student_id', $studentIds)
            ->when(! empty($validated['student_id']), fn ($q) => $q->where('student_id', $validated['student_id']))
            ->when(! empty($validated['channel']), fn ($q) => $q->where('channel', $validated['channel']))
            ->when(! empty($validated['status']), fn ($q) => $q->where('status', $validated['status']))
            ->when(! empty($validated['from']), fn ($q) => $q->whereDate('sent_on', '>=', $validated['from']))
            ->when(! empty($validated['to']), fn ($q) => $q->whereDate('sent_on', '<=', $validated['to']))
            ->when(! empty($validated['q']), function ($q) use ($validated) {
                $term = '%' . trim((string) $validated['q']) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('message', 'like', $term)
                        ->orWhereHas('student', fn ($sq) => $sq
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
        $statsRow = (clone $baseStatsQuery)->selectRaw(
            'COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed,
            SUM(CASE WHEN channel = ? THEN 1 ELSE 0 END) as sms,
            SUM(CASE WHEN channel = ? THEN 1 ELSE 0 END) as email,
            SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread',
            ['sent', 'failed', 'sms', 'email']
        )->first();

        $stats = [
            'total' => (int) ($statsRow->total ?? 0),
            'sent' => (int) ($statsRow->sent ?? 0),
            'failed' => (int) ($statsRow->failed ?? 0),
            'sms' => (int) ($statsRow->sms ?? 0),
            'email' => (int) ($statsRow->email ?? 0),
            'unread' => (int) ($statsRow->unread ?? 0),
        ];

        return view('parents.notifications', compact('logs', 'filters', 'students', 'stats', 'parent'));
    }

    public function markNotificationRead(Request $request, NotificationLog $log)
    {
        abort_unless(
            $log->student && $request->user()->parentStudents()->whereKey($log->student_id)->exists(),
            403,
            'You are not authorized to access this notification record.'
        );

        if (! $log->read_at) {
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
