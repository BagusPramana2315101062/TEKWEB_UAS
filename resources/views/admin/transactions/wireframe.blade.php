@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="py-6">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-semibold text-gray-900">Transactions (Prototype)</h1>
            <div class="flex items-center space-x-2">
                <a href="#" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded shadow">Export</a>
                <a href="{{ route('admin.transactions.index') }}" class="inline-flex items-center px-3 py-2 bg-white border rounded">Back to live</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Filters panel -->
            <aside class="col-span-1 bg-white p-4 rounded shadow">
                <form method="GET" action="{{ route('admin.transactions.index') }}">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Search</label>
                        <input name="q" value="{{ request('q') }}" class="mt-1 block w-full rounded border-gray-300 shadow-sm" placeholder="search by code, user name or email" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Date range</label>
                        <div class="flex space-x-2 mt-1">
                            <input type="date" name="from" value="{{ request('from') }}" class="rounded border-gray-300 w-1/2" />
                            <input type="date" name="to" value="{{ request('to') }}" class="rounded border-gray-300 w-1/2" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 block w-full rounded border-gray-300">
                            <option value="">Any</option>
                            <option value="PAID" @if(request('status')=='PAID') selected @endif>Paid</option>
                            <option value="CANCELLED" @if(request('status')=='CANCELLED') selected @endif>Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Amount (min / max)</label>
                        <div class="flex space-x-2 mt-1">
                            <input name="min_total" value="{{ request('min_total') }}" placeholder="0" class="rounded border-gray-300 w-1/2" />
                            <input name="max_total" value="{{ request('max_total') }}" placeholder="1000" class="rounded border-gray-300 w-1/2" />
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded">Apply</button>
                        <a href="{{ route('admin.transactions.index') }}" class="px-3 py-2 bg-white border rounded">Clear</a>
                    </div>
                </form>

                <hr class="my-4" />

                <div class="text-sm text-gray-600">
                    <strong>Tips:</strong>
                    <ul class="list-disc ml-5 mt-2">
                        <li>Use search for user name, email or transaction code</li>
                        <li>Use date range to limit results before exporting</li>
                        <li>Try min/max total to find high value transactions</li>
                    </ul>
                </div>
            </aside>

            <!-- Main table -->
            <section class="col-span-3 bg-white p-4 rounded shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm text-gray-600">Showing <strong>0</strong> results (prototype)</div>
                    <div class="flex items-center space-x-2">
                        <button class="px-3 py-2 bg-gray-100 rounded">Bulk actions</button>
                        <div class="relative inline-block text-left">
                            <button class="px-3 py-2 bg-green-600 text-white rounded">Export CSV</button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">#</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Code</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Customer</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Subtotal</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Discount</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Tax</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Total</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500">Status</th>
                                <th class="px-4 py-2 text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            {{-- Example static rows for prototype --}}
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">1</td>
                                <td class="px-4 py-3 text-sm text-gray-700">TRX-001</td>
                                <td class="px-4 py-3 text-sm text-gray-700">Alice — alice@example.com</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">100.00</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">10.00</td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700">18.00</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">108.00</td>
                                <td class="px-4 py-3 text-center"><span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">PAID</span></td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="#" class="text-blue-600">View</a>
                                    <span class="mx-1">|</span>
                                    <a href="#" class="text-red-600">Delete</a>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-gray-600">Selected: <strong>0</strong></div>
                    <div class="text-sm text-gray-600">Pagination (prototype)</div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
