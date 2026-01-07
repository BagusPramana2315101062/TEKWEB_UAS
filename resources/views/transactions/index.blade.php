@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <h1 class="text-2xl font-semibold mb-4">My Transactions</h1>

    <div class="bg-white rounded shadow p-4">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Code</th>
                    <th class="px-4 py-2 text-left">Amount</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                    <tr>
                        <td class="border px-4 py-2">{{ $t->code }}</td>
                        <td class="border px-4 py-2">{{ number_format($t->grand_total, 2) }}</td>
                        <td class="border px-4 py-2">{{ $t->status }}</td>
                        <td class="border px-4 py-2">{{ $t->transacted_at? $t->transacted_at->format('Y-m-d') : '-' }}</td>
                        <td class="border px-4 py-2"><a href="{{ route('transactions.show', $t->id) }}" class="text-blue-600">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-4 text-gray-500">No transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection