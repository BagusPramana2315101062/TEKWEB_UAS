<?php

namespace App\Services;

use App\Models\Transaction;

use Illuminate\Support\Facades\Cache;

class ReportService
{
    /**
     * Return labels and values for transactions per month for a given year.
     * Cached to avoid expensive aggregations on high volume tables.
     *
     * @param int $year
     * @return array{labels: array, values: array}
     */
    public function transactionsPerMonth(int $year): array
    {
        $cacheKey = "reports:transactions_per_month:{$year}";

        // Prefer tag-based caching when available (e.g., Redis), otherwise fall back to plain keys.
        try {
            return Cache::tags(['reports','transactions_per_month'])->remember($cacheKey, now()->addMinutes(5), function () use ($year) {
                return $this->buildMonthlyData($year);
            });
        } catch (\BadMethodCallException $e) {
            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($year) {
                return $this->buildMonthlyData($year);
            });
        }
    }

    /**
     * Helper to build the transactions per month payload (kept for easier testing).
     */
    protected function buildMonthlyData(int $year): array
    {
        // Fixed 12 month labels
        $labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $values = array_fill(0, 12, 0);

        // Use DB-specific month extraction so the query works on sqlite, mysql, and pgsql
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        switch ($driver) {
            case 'sqlite':
                $raw = "CAST(strftime('%m', transacted_at) AS INTEGER) as month, COUNT(*) as cnt";
                break;
            case 'pgsql':
            case 'postgres':
                // date_part returns numeric; cast to integer for consistency
                $raw = "CAST(date_part('month', transacted_at) AS INTEGER) as month, COUNT(*) as cnt";
                break;
            case 'mysql':
            default:
                $raw = "MONTH(transacted_at) as month, COUNT(*) as cnt";
                break;
        }

        $rows = Transaction::selectRaw($raw)
            ->whereYear('transacted_at', $year)
            ->whereIn('status', ['PAID', 'COMPLETE', 'COMPLETED'])
            ->groupBy('month')
            ->pluck('cnt', 'month')
            ->toArray();

        foreach ($rows as $month => $cnt) {
            $index = (int)$month - 1;
            if ($index >= 0 && $index < 12) {
                $values[$index] = (int)$cnt;
            }
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Return category revenue share between dates.
     *
     * @param string $start Datetime string
     * @param string $end Datetime string
     * @param int $limit
     * @return array{total_sum: float, totals: array, labels: array, values: array}
     */
    public function categoryShare(string $start, string $end, int $limit = 10): array
    {
        // Query aggregated totals per category for PAID transactions
        $rows = \Illuminate\Support\Facades\DB::table('transaction_items as ti')
            ->join('transactions as t', 'ti.transaction_id', 't.id')
            ->join('products as p', 'ti.product_id', 'p.id')
            ->join('categories as c', 'p.category_id', 'c.id')
            ->whereIn('t.status', ['PAID', 'COMPLETE', 'COMPLETED'])
            ->whereBetween('t.transacted_at', [$start, $end])
            ->selectRaw('c.name as category, SUM(ti.line_total) as total')
            ->groupBy('c.id', 'c.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $labels = [];
        $values = [];
        $totals = [];
        $totalSum = 0.0;

        foreach ($rows as $row) {
            $total = (float) $row->total; // ensure numeric for SQLite
            $labels[] = $row->category;
            $values[] = $total;
            $totals[] = ['category' => $row->category, 'total' => $total];
            $totalSum += $total;
        }

        // compute percentages
        foreach ($totals as &$t) {
            $t['percentage'] = $totalSum > 0 ? (float) round(($t['total'] / $totalSum) * 100, 2) : 0.0;
        }
        unset($t);

        $output = [
            'total_sum' => (float) $totalSum,
            'totals' => $totals,
            'labels' => $labels,
            'values' => $values,
        ];

        // cache the result for the given range/limit to speed up repeated calls
        $cacheKey = "reports:category_share:{$start}:{$end}:{$limit}";

        try {
            Cache::tags(['reports','category_share'])->put($cacheKey, $output, now()->addMinutes(5));
        } catch (\BadMethodCallException $e) {
            Cache::put($cacheKey, $output, now()->addMinutes(5));
        }

        return $output;
    }
}
