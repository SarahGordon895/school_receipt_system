<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Stream;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReceiptsReportExport;

class ReportController extends Controller
{
    public function index()
    {
        $classes = ClassRoom::orderBy('name')->get(['id', 'name']);
        $streams = Stream::orderBy('name')->get(['id', 'name']);
        $categories = PaymentCategory::orderBy('name')->get(['id', 'name']);
        
        return view('reports.index', compact('classes', 'streams', 'categories'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'date_range' => 'required|in:today,yesterday,this_week,last_week,this_month,last_month,this_year,last_year,custom',
            'start_date' => 'nullable|required_if:date_range,custom|date',
            'end_date' => 'nullable|required_if:date_range,custom|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:classes,id',
            'stream_id' => 'nullable|exists:streams,id',
            'payment_category_id' => 'nullable|exists:payment_categories,id',
            'payment_mode' => 'nullable|in:Cash,Bank,Mobile Money,Other',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ]);

        $query = Receipt::with(['classRoom', 'stream', 'paymentCategories', 'user']);

        // Apply date range filter
        $dateRange = $request->date_range;
        if ($dateRange === 'custom') {
            $query->whereDate('payment_date', '>=', $request->start_date)
                  ->whereDate('payment_date', '<=', $request->end_date);
        } else {
            $dateRangeArray = $this->getDateRange($dateRange);
            $query->whereDate('payment_date', '>=', $dateRangeArray[0])
                  ->whereDate('payment_date', '<=', $dateRangeArray[1]);
        }

        // Apply other filters
        $query->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
              ->when($request->stream_id, fn($q) => $q->where('stream_id', $request->stream_id))
              ->when($request->payment_category_id, fn($q) => $q->where('payment_category_id', $request->payment_category_id))
              ->when($request->payment_mode, fn($q) => $q->where('payment_mode', $request->payment_mode))
              ->when($request->min_amount, fn($q) => $q->where('amount', '>=', $request->min_amount))
              ->when($request->max_amount, fn($q) => $q->where('amount', '<=', $request->max_amount));

        $receipts = $query->orderBy('payment_date', 'desc')->get();

        // Calculate summary statistics
        $summary = [
            'total_receipts' => $receipts->count(),
            'total_amount' => $receipts->sum('amount'),
            'average_amount' => $receipts->avg('amount'),
            'payment_modes' => $receipts->groupBy('payment_mode')->map->count(),
            'categories' => $receipts->flatMap(function ($receipt) {
                return $receipt->paymentCategories->pluck('name');
            })->countBy()->all(),
        ];

        return view('reports.results', compact('receipts', 'summary', 'request'));
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'date_range' => 'required|in:today,yesterday,this_week,last_week,this_month,last_month,this_year,last_year,custom',
            'start_date' => 'nullable|required_if:date_range,custom|date',
            'end_date' => 'nullable|required_if:date_range,custom|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:classes,id',
            'stream_id' => 'nullable|exists:streams,id',
            'payment_category_id' => 'nullable|exists:payment_categories,id',
            'payment_mode' => 'nullable|in:Cash,Bank,Mobile Money,Other',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ]);

        $filename = 'receipts_report_' . now()->format('Y_m_d_His') . '.xlsx';
        
        return Excel::download(new ReceiptsReportExport($request->all()), $filename);
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'date_range' => 'required|in:today,yesterday,this_week,last_week,this_month,last_month,this_year,last_year,custom',
            'start_date' => 'nullable|required_if:date_range,custom|date',
            'end_date' => 'nullable|required_if:date_range,custom|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:classes,id',
            'stream_id' => 'nullable|exists:streams,id',
            'payment_category_id' => 'nullable|exists:payment_categories,id',
            'payment_mode' => 'nullable|in:Cash,Bank,Mobile Money,Other',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
        ]);

        $query = Receipt::with(['classRoom', 'stream', 'paymentCategories', 'user']);

        // Apply date range filter
        $dateRange = $request->date_range;
        if ($dateRange === 'custom') {
            $query->whereDate('payment_date', '>=', $request->start_date)
                  ->whereDate('payment_date', '<=', $request->end_date);
        } else {
            $dateRangeArray = $this->getDateRange($dateRange);
            $query->whereDate('payment_date', '>=', $dateRangeArray[0])
                  ->whereDate('payment_date', '<=', $dateRangeArray[1]);
        }

        // Apply other filters
        $query->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
              ->when($request->stream_id, fn($q) => $q->where('stream_id', $request->stream_id))
              ->when($request->payment_category_id, fn($q) => $q->where('payment_category_id', $request->payment_category_id))
              ->when($request->payment_mode, fn($q) => $q->where('payment_mode', $request->payment_mode))
              ->when($request->min_amount, fn($q) => $q->where('amount', '>=', $request->min_amount))
              ->when($request->max_amount, fn($q) => $q->where('amount', '<=', $request->max_amount));

        $receipts = $query->orderBy('payment_date', 'desc')->get();

        // Calculate summary statistics
        $summary = [
            'total_receipts' => $receipts->count(),
            'total_amount' => $receipts->sum('amount'),
            'average_amount' => $receipts->avg('amount'),
            'payment_modes' => $receipts->groupBy('payment_mode')->map->count(),
            'categories' => $receipts->flatMap(function ($receipt) {
                return $receipt->paymentCategories->pluck('name');
            })->countBy()->all(),
        ];

        $pdf = Pdf::loadView('reports.pdf', compact('receipts', 'summary', 'request'));
        $filename = 'receipts_report_' . now()->format('Y_m_d_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    private function getDateRange($range)
    {
        $now = now();
        
        return match($range) {
            'today' => [$now->toDateString(), $now->toDateString()],
            'yesterday' => [$now->copy()->subDay()->toDateString(), $now->copy()->subDay()->toDateString()],
            'this_week' => [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek()->toDateString(), $now->copy()->subWeek()->endOfWeek()->toDateString()],
            'this_month' => [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString()],
            'this_year' => [$now->copy()->startOfYear()->toDateString(), $now->copy()->endOfYear()->toDateString()],
            'last_year' => [$now->copy()->subYear()->startOfYear()->toDateString(), $now->copy()->subYear()->endOfYear()->toDateString()],
            default => [$now->toDateString(), $now->toDateString()],
        };
    }

    public function getStreamsByClass($classId)
    {
        $streams = Stream::where('class_id', $classId)->orderBy('name')->get(['id', 'name']);
        return response()->json($streams);
    }
}
