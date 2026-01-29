<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();
        $yearStart = now()->startOfYear();

        $metrics = [
            'total_collected' => number_format(\App\Models\Receipt::sum('amount')),
            'today_total' => number_format(\App\Models\Receipt::whereDate('payment_date', $today)->sum('amount')),
            'today_count' => \App\Models\Receipt::whereDate('payment_date', $today)->count(),
            'month_total' => number_format(\App\Models\Receipt::whereBetween('payment_date', [$monthStart, now()])->sum('amount')),
            'month_count' => \App\Models\Receipt::whereBetween('payment_date', [$monthStart, now()])->count(),
        ];

        $byMode = \App\Models\Receipt::select('payment_mode', DB::raw('COUNT(*) c'), DB::raw('SUM(amount) s'))
            ->whereBetween('payment_date', [$monthStart, now()])
            ->groupBy('payment_mode')->orderByDesc('s')->get();

        $recent = \App\Models\Receipt::with(['classRoom', 'stream'])
            ->latest()->take(8)->get();

        $topClasses = \App\Models\Receipt::select('class_id', DB::raw('SUM(amount) s'))
            ->whereBetween('payment_date', [$yearStart, now()])
            ->groupBy('class_id')
            ->with('classRoom:id,name')
            ->orderByDesc('s')->take(5)->get();

        // NEW: Totals by payment category (this month)
        $byCategory = DB::table('receipts')
            ->leftJoin('payment_categories', 'payment_categories.id', '=', 'receipts.payment_category_id')
            ->whereBetween('payment_date', [$monthStart, now()])
            ->select(
                DB::raw("COALESCE(payment_categories.name, 'Uncategorized') as name"),
                DB::raw('COUNT(*) as c'),
                DB::raw('SUM(receipts.amount) as s')
            )
            ->groupBy(DB::raw("COALESCE(payment_categories.name, 'Uncategorized')"))
            ->orderByDesc('s')
            ->get();

        return view('dashboard', compact('metrics', 'byMode', 'recent', 'topClasses', 'byCategory'));
    }
}
