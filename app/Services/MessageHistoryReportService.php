<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MessageHistoryReportService
{
    /**
     * SMS & email message history from notification logs.
     *
     * @return array{rows: Collection<int, NotificationLog>, summary: array<string, int>, setting: Setting}
     */
    public function build(Request $request): array
    {
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = NotificationLog::query()
            ->with(['student:id,name,admission_no,class_name,parent_name,parent_phone'])
            ->when($dateFrom, fn ($q) => $q->whereDate('sent_on', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('sent_on', '<=', $dateTo))
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->channel))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->student_id))
            ->when($request->filled('event_type'), fn ($q) => $q->where('event_type', $request->event_type))
            ->when(trim((string) $request->get('q', '')) !== '', function ($q) use ($request) {
                $term = '%'.trim($request->q).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('message', 'like', $term)
                        ->orWhereHas('student', fn ($sq) => $sq
                            ->where('name', 'like', $term)
                            ->orWhere('admission_no', 'like', $term));
                });
            });

        $rows = (clone $query)
            ->latest('sent_on')
            ->latest('id')
            ->get();

        $summary = [
            'total' => $rows->count(),
            'sent' => $rows->where('status', 'sent')->count(),
            'failed' => $rows->where('status', 'failed')->count(),
            'skipped' => $rows->where('status', 'skipped')->count(),
            'sms' => $rows->where('channel', 'sms')->count(),
            'email' => $rows->where('channel', 'email')->count(),
        ];

        return [
            'rows' => $rows,
            'summary' => $summary,
            'setting' => Setting::current() ?? new Setting(['school_name' => config('app.name')]),
            'students' => Student::query()
                ->select('id', 'name', 'admission_no')
                ->whereIn('id', NotificationLog::query()->distinct()->pluck('student_id'))
                ->orderBy('name')
                ->get(),
        ];
    }
}
