<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Models\Student;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
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

        $logs = NotificationLog::query()
            ->with(['student:id,name,admission_no'])
            ->when($dateFrom, fn($q) => $q->whereDate('sent_on', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('sent_on', '<=', $dateTo))
            ->when($request->filled('channel'), fn($q) => $q->where('channel', $request->channel))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('student_id'), fn($q) => $q->where('student_id', $request->student_id))
            ->when(trim((string) $request->get('q', '')) !== '', function ($q) use ($request) {
                $term = '%' . trim($request->q) . '%';
                $q->whereHas('student', fn($sq) => $sq
                    ->where('name', 'like', $term)
                    ->orWhere('admission_no', 'like', $term));
            })
            ->latest('sent_on')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $students = Student::orderBy('name')->get(['id', 'name', 'admission_no']);

        return view('notification-logs.index', [
            'logs' => $logs,
            'students' => $students,
            'filters' => $request->only(['date_from', 'date_to', 'channel', 'status', 'student_id', 'q']),
        ]);
    }
}
