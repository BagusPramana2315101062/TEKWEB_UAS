<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;

class AdminDashboardController extends Controller
{
    /**
     * Show the admin dashboard with an inline Chart.js dataset.
     */
    public function index(ReportService $report)
    {
        $data = $report->transactionsPerMonth(now()->year);
        $labels = $data['labels'];
        $values = $data['values'];

        // summary widgets
        $totalTransactions = \App\Models\Transaction::count();
        $totalProducts = \App\Models\Product::count();
        $transactionsThisWeek = \App\Models\Transaction::whereIn('status', ['PAID','COMPLETE','COMPLETED'])->where('transacted_at', '>=', now()->startOfWeek())->count();

        // top product by revenue
        $top = \Illuminate\Support\Facades\DB::table('transaction_items as ti')
            ->join('transactions as t','ti.transaction_id','t.id')
            ->join('products as p','ti.product_id','p.id')
            ->whereIn('t.status', ['PAID','COMPLETE','COMPLETED'])
            ->selectRaw('p.id, p.name, SUM(ti.line_total) as revenue, SUM(ti.qty) as qty_sold')
            ->groupBy('p.id','p.name')
            ->orderByDesc('revenue')
            ->first();
        $topProduct = $top ? $top->name : null;

        // Top 5 products by revenue
        $topProducts = \Illuminate\Support\Facades\DB::table('transaction_items as ti')
            ->join('transactions as t','ti.transaction_id','t.id')
            ->join('products as p','ti.product_id','p.id')
            ->whereIn('t.status', ['PAID','COMPLETE','COMPLETED'])
            ->selectRaw('p.id, p.name, SUM(ti.line_total) as revenue, SUM(ti.qty) as qty_sold')
            ->groupBy('p.id','p.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('labels', 'values', 'totalTransactions', 'totalProducts', 'transactionsThisWeek', 'topProduct', 'topProducts'));
    }
}
