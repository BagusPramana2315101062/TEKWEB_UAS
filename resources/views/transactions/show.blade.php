@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <h1 class="text-2xl font-semibold mb-4">Transaction {{ $transaction->code }}</h1>

    <div class="bg-white rounded shadow p-4">
        <div class="mb-2">Status: <strong>{{ $transaction->status }}</strong></div>
        <div class="mb-2">Date: {{ $transaction->transacted_at? $transaction->transacted_at->format('Y-m-d H:i') : '-' }}</div>
        <div class="mb-4">Total: Rp {{ number_format($transaction->grand_total, 2) }}</div>

        <h2 class="text-lg font-semibold">Items</h2>
        <table class="min-w-full text-sm mt-2">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Product</th>
                    <th class="px-4 py-2 text-left">Qty</th>
                    <th class="px-4 py-2 text-left">Price</th>
                    <th class="px-4 py-2 text-left">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->items as $it)
                    <tr>
                        <td class="border px-4 py-2">{{ optional($it->product)->name ?? 'Deleted' }}</td>
                        <td class="border px-4 py-2">{{ $it->qty }}</td>
                        <td class="border px-4 py-2">{{ number_format($it->price, 2) }}</td>
                        <td class="border px-4 py-2">{{ number_format($it->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection