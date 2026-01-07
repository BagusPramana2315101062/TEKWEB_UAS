@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold" style="margin-left: 30px; margin-top: 10px;">Admin Dashboard</h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6" >
    <div class="bg-white shadow rounded p-4 hover:shadow-md transition" style="margin-left: 30px;">
        <div class="text-sm text-gray-500">Total Transactions</div>
        <div class="text-2xl font-bold">{{ $totalTransactions ?? 0 }}</div>
    </div>
    <div class="bg-white shadow rounded p-4 hover:shadow-md transition">
        <div class="text-sm text-gray-500">Total Products</div>
        <div class="text-2xl font-bold">{{ $totalProducts ?? 0 }}</div>
    </div>
    <div class="bg-white shadow rounded p-4 hover:shadow-md transition flex items-center justify-between" style="margin-right: 30px;">
        <div>
            <div class="text-sm text-gray-500">Transactions This Week</div>
            <div class="text-2xl font-bold">{{ $transactionsThisWeek ?? 0 }}</div>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500">Top Product</div>
            <div class="text-sm">{{ $topProduct ?? 'N/A' }}</div>
        </div>
    </div>
</div>

<div class="bg-white shadow rounded p-4 dark:bg-gray-800 dark:text-white" style="margin-left: 30px; margin-right: 30px; margin-bottom: 30px;">
    <div class="relative">
        <canvas id="transactionsChart" width="800" height="300" role="img" aria-label="Grafik transaksi per bulan"></canvas>
        <div id="chartLoader" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-50" style="display:none;">
            <div class="animate-pulse px-4 py-2 rounded bg-gray-100 dark:bg-gray-700">Memuat data…</div>
        </div>
    </div>

    <div class="mt-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <input id="productSearch" type="search" placeholder="Cari produk terlaris" class="px-2 py-1 border rounded" aria-label="Cari produk terlaris">
            <button id="exportCsv" class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700">Export CSV</button>
        </div>
        <!-- Refresh button removed as requested -->
    </div> 

    <div class="mt-4">
        <h2 class="text-lg font-semibold">Top Products</h2>
        @if(($topProducts ?? collect())->isEmpty())
            <div class="mt-2 text-sm text-gray-500">Tidak ada produk terjual untuk periode ini.</div>
        @else
            <div class="mt-2 overflow-x-auto">
                <table class="min-w-full text-sm" id="topProductsTable">
                    <thead class="text-left text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-2 py-1">#</th>
                            <th class="px-2 py-1">Produk</th>
                            <th class="px-2 py-1">Qty Terjual</th>
                            <th class="px-2 py-1">Pendapatan</th>
                            <th class="px-2 py-1">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topProducts as $i => $p)
                            <tr class="border-t dark:border-gray-700">
                                <td class="px-2 py-2">{{ $i + 1 }}</td>
                                <td class="px-2 py-2">{{ $p->name }}</td>
                                <td class="px-2 py-2">{{ number_format($p->qty_sold) }}</td>
                                <td class="px-2 py-2">{{ number_format($p->revenue, 2) }}</td>
                                <td class="px-2 py-2">
                                    @if(\Illuminate\Support\Facades\Route::has('admin.products.edit'))
                                        <a href="{{ route('admin.products.edit', $p->id) }}" class="text-blue-600 hover:underline">Lihat</a>
                                    @else
                                        <span class="text-gray-600">Lihat</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const labels = @json($labels);
    const values = @json($values);

    let chart;
    (function () {
        const ctx = document.getElementById('transactionsChart').getContext('2d');
        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Transactions',
                    data: values,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                interaction: { mode: 'nearest' },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });

        const chartLoader = document.getElementById('chartLoader');
        // Refresh button removed — no automatic API refresh on the client to avoid auth issues



        // product search filter (client-side)
        document.getElementById('productSearch').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#topProductsTable tbody tr').forEach(tr => {
                const name = tr.children[1].textContent.toLowerCase();
                tr.style.display = name.includes(q) ? '' : 'none';
            });
        });

        // export CSV
        document.getElementById('exportCsv').addEventListener('click', function () {
            const rows = [['Rank','Product','Qty Sold','Revenue']];
            document.querySelectorAll('#topProductsTable tbody tr').forEach(tr => {
                const cols = Array.from(tr.children).slice(0,4).map(td => td.textContent.trim());
                rows.push(cols);
            });
            const csv = rows.map(r => r.join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'top-products.csv'; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
        });
    })();
</script>

@endsection
