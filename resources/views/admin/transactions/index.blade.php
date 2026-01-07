@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold">Transactions</h1>
    </div>

    @if(session('success')) <div class="mb-4 text-green-700">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="mb-4 text-red-700">{{ session('error') }}</div> @endif

    <div class="mb-4 bg-white p-4 rounded-lg shadow-sm">
        <form id="filtersForm" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm text-gray-600">Search (code, user name, email)</label>
                <input type="search" name="q" placeholder="Search by code, user name or email" value="{{ $filters['q'] ?? request('q') }}" class="mt-1 block w-full border rounded px-2 py-1" aria-label="Search transactions">
            </div>
            <div>
                <label class="block text-sm text-gray-600">Status</label>
                <select name="status" class="mt-1 block w-full border rounded px-2 py-1">
                    <option value="">All</option>
                    <option value="PAID" {{ (request('status')==='PAID')?'selected':'' }}>PAID</option>
                    <option value="PENDING" {{ (request('status')==='PENDING')?'selected':'' }}>PENDING</option>
                    <option value="CANCELLED" {{ (request('status')==='CANCELLED')?'selected':'' }}>CANCELLED</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600">Date range</label>
                <div class="flex gap-2">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? request('date_from') }}" class="mt-1 block w-full border rounded px-2 py-1">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? request('date_to') }}" class="mt-1 block w-full border rounded px-2 py-1">
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-600">Min Amount</label>
                <input type="number" step="0.01" name="min_amount" value="{{ $filters['min_amount'] ?? request('min_amount') }}" class="mt-1 block w-full border rounded px-2 py-1">
            </div>
            <div>
                <label class="block text-sm text-gray-600">Max Amount</label>
                <input type="number" step="0.01" name="max_amount" value="{{ $filters['max_amount'] ?? request('max_amount') }}" class="mt-1 block w-full border rounded px-2 py-1">
            </div>

            <div>
                <label class="block text-sm text-gray-600">Sort</label>
                <div class="flex gap-2">
                    <select name="sort_by" class="mt-1 block w-full border rounded px-2 py-1">
                        <option value="transacted_at" {{ (request('sort_by')=='transacted_at')?'selected':'' }}>Date</option>
                        <option value="grand_total" {{ (request('sort_by')=='grand_total')?'selected':'' }}>Amount</option>
                        <option value="id" {{ (request('sort_by')=='id')?'selected':'' }}>ID</option>
                    </select>
                    <select name="sort_dir" class="mt-1 block w-full border rounded px-2 py-1">
                        <option value="desc" {{ (request('sort_dir')=='desc')?'selected':'' }}>Desc</option>
                        <option value="asc" {{ (request('sort_dir')=='asc')?'selected':'' }}>Asc</option>
                    </select>
                </div>
            </div>

            <div class="md:col-span-3 flex gap-2 justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Apply</button>
                <a href="{{ route('admin.transactions.index') }}" class="px-4 py-2 border rounded">Reset</a>
                <a href="{{ route('admin.transactions.export', request()->query()) }}" target="_blank" class="px-4 py-2 bg-green-600 text-white rounded">Export CSV</a>
            </div>
        </form>
    </div>

    @include('admin.transactions._table')
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('filtersForm');
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const params = new URLSearchParams(new FormData(form)).toString();
        const url = `${location.pathname}?${params}`;
        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true; btn.textContent = 'Loading...';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                const container = document.querySelector('#transactions-table').parentNode;
                container.innerHTML = html;
            })
            .catch(err => { console.error(err); alert('Failed to load'); })
            .finally(() => { btn.disabled = false; btn.textContent = 'Apply'; });
    });

    // delegate link clicks for pagination inside the transactions table to use AJAX
    document.addEventListener('click', function (e) {
        const a = e.target.closest('a');
        if (!a) return;

        // Do not AJAXify links explicitly marked to avoid AJAX replacement
        if (a.classList.contains('no-ajax')) return;

        if (a.closest('#transactions-table') && a.getAttribute('href')) {
            const href = a.getAttribute('href');

            // Only AJAXify pagination and internal table links (links that include ?page= or are same-path queries)
            if (href.includes('?page=') || href.includes('?')) {
                e.preventDefault();
                fetch(href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(html => {
                        const container = document.querySelector('#transactions-table').parentNode;
                        container.innerHTML = html;
                    })
                    .catch(err => { console.error(err); alert('Failed to load'); });
            }
        }
    });
});
</script>

@endsection