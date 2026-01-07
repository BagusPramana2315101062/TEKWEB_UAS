<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\ReportService;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function transactionsMonthly(Request $request, ReportService $report): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'year' => ['sometimes','integer','min:2000','max:2100'],
        ])->validate();

        $year = $data['year'] ?? Carbon::now()->year;

        $result = $report->transactionsPerMonth((int) $year);

        return response()->json(array_merge($result, ['year' => (int) $year]));
    }

    public function categoryShare(Request $request, ReportService $report): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'start' => ['sometimes','date_format:Y-m-d'],
            'end' => ['sometimes','date_format:Y-m-d'],
            'limit' => ['sometimes','integer','min:1','max:100'],
        ])->validate();

        $end = isset($data['end']) ? Carbon::createFromFormat('Y-m-d', $data['end'])->endOfDay() : Carbon::now()->endOfDay();
        $start = isset($data['start']) ? Carbon::createFromFormat('Y-m-d', $data['start'])->startOfDay() : $end->copy()->subDays(30)->startOfDay();
        $limit = $data['limit'] ?? 10;

        $result = $report->categoryShare($start->toDateTimeString(), $end->toDateTimeString(), (int)$limit);

        return response()->json($result);
    }

    public function summary(): JsonResponse
    {
        // admin-only summary endpoint
        $totalTransactions = \App\Models\Transaction::count();
        $totalProducts = \App\Models\Product::count();
        $transactionsThisWeek = \App\Models\Transaction::whereIn('status', ['PAID','COMPLETE','COMPLETED'])->where('transacted_at', '>=', now()->startOfWeek())->count();

        $top = \Illuminate\Support\Facades\DB::table('transaction_items as ti')
            ->join('transactions as t','ti.transaction_id','t.id')
            ->join('products as p','ti.product_id','p.id')
            ->whereIn('t.status', ['PAID','COMPLETE','COMPLETED'])
            ->selectRaw('p.name, SUM(ti.line_total) as revenue')
            ->groupBy('p.id','p.name')
            ->orderByDesc('revenue')
            ->first();

        $topProduct = $top ? $top->name : null;

        return response()->json([
            'total_transactions' => (int) $totalTransactions,
            'total_products' => (int) $totalProducts,
            'transactions_this_week' => (int) $transactionsThisWeek,
            'top_product' => $topProduct,
        ]);
    }
}
