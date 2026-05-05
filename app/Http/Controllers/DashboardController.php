<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $recent = \App\Models\Receipt::query()
            ->latest()->take(8)->get();

        if (Schema::hasColumn('receipts', 'class_name')) {
            $topClasses = \App\Models\Receipt::select('class_name', DB::raw('SUM(amount) s'))
                ->whereBetween('payment_date', [$yearStart, now()])
                ->groupBy('class_name')
                ->orderByDesc('s')
                ->take(5)
                ->get();
        } else {
            // Backward compatibility for older databases before class_name migration.
            $topClasses = collect();
        }

        // NEW: Totals by payment category (this month)
        $byCategory = DB::table('receipt_payment_category')
            ->join('receipts', 'receipts.id', '=', 'receipt_payment_category.receipt_id')
            ->join('payment_categories', 'payment_categories.id', '=', 'receipt_payment_category.payment_category_id')
            ->whereBetween('payment_date', [$monthStart, now()])
            ->select(
                DB::raw('payment_categories.name as name'),
                DB::raw('COUNT(receipt_payment_category.id) as c'),
                DB::raw('SUM(receipt_payment_category.amount) as s')
            )
            ->groupBy('payment_categories.name')
            ->orderByDesc('s')
            ->get();

        return view('dashboard', compact('metrics', 'byMode', 'recent', 'topClasses', 'byCategory'));
    }
}
