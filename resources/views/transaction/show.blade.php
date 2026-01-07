@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <a href="{{ route('transactions.index') }}" class="text-sm text-gray-600 mb-4 inline-block">&larr; Back to My Transactions</a>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-4">Transaction {{ $transaction->code }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-lg font-semibold mb-4 pb-2 border-b">Transaction Information</h3>
                <div class="space-y-2">
                    <div><strong>Status:</strong> {{ $transaction->status }}</div>
                    <div><strong>Date:</strong> {{ $transaction->transacted_at ? $transaction->transacted_at->format('d M Y, H:i') : '-' }}</div>
                    <div><strong>Processed By:</strong> {{ optional($transaction->processedBy)->name ?? '-' }}</div>
                    <div><strong>Processed At:</strong> {{ $transaction->processed_at ? $transaction->processed_at->format('d M Y, H:i') : '-' }}</div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4 pb-2 border-b">Financial Summary</h3>
                <div class="space-y-2">
                    <div class="flex justify-between"><span><strong>Subtotal:</strong></span> <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span><strong>Tax:</strong></span> <span>Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between"><span><strong>Discount:</strong></span> <span>Rp {{ number_format($transaction->discount_value ?? 0, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between border-t pt-2"><span><strong>Grand Total:</strong></span> <span class="text-lg font-bold text-green-600">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span></div>
                </div>
            </div>
        </div>

        @if(auth()->check() && auth()->user()->role === 'admin')
            <div class="mb-6 border-t pt-6">
                <h3 class="text-lg font-semibold mb-4">Admin Actions</h3>
                <div id="admin-actions" class="flex flex-wrap gap-2"></div>
                <div id="loading-message" class="text-sm text-gray-500">Loading actions...</div>
                <div id="error-message" class="text-sm text-red-500 hidden"></div>
            </div>
        @endif

        <h3 class="text-lg font-semibold mt-6 mb-4 pb-2 border-b">Order Items</h3>
        <div class="overflow-x-auto">
            <table class="w-full mb-4">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Product</th>
                        <th class="px-4 py-2 text-left">Quantity</th>
                        <th class="px-4 py-2 text-right">Price</th>
                        <th class="px-4 py-2 text-right">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction->items as $item)
                        <tr class="border-b">
                            <td class="px-4 py-3">{{ optional($item->product)->name ?? 'Product #' . $item->product_id }}</td>
                            <td class="px-4 py-3 text-center">{{ $item->qty }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(auth()->check() && auth()->user()->role === 'admin')
<script>
    // Set CSRF header for axios
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.addEventListener('DOMContentLoaded', function() {
        const transactionId = {{ $transaction->id }};
        const status = '{{ $transaction->status }}';
        const adminActionsDiv = document.getElementById('admin-actions');
        const loadingMsg = document.getElementById('loading-message');
        const errorMsg = document.getElementById('error-message');

        try {
            let actions = '';
            if (status === 'PENDING') {
                actions += `<button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded" onclick="updateTransactionStatus(${transactionId}, 'SHIPPED')">Ship</button>`;
                actions += `<button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded" onclick="updateTransactionStatus(${transactionId}, 'CANCELLED')">Cancel</button>`;
            }
            if (status === 'PENDING' || status === 'SHIPPED') {
                actions += `<button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded" onclick="updateTransactionStatus(${transactionId}, 'COMPLETED')">Complete</button>`;
            }
            if (status === 'COMPLETED' || status === 'CANCELLED') {
                actions = '<div class="text-sm text-gray-500">No actions available for this transaction status.</div>';
            }

            adminActionsDiv.innerHTML = actions;
            loadingMsg.style.display = 'none';
        } catch (e) {
            console.error('Failed to render admin actions', e);
            errorMsg.textContent = 'Failed to load admin actions';
            errorMsg.classList.remove('hidden');
            loadingMsg.style.display = 'none';
        }
    });

    async function updateTransactionStatus(transactionId, newStatus) {
        if (!confirm(`Mark transaction as ${newStatus}?`)) return;

        try {
            const resp = await axios.patch(`/api/transactions/${transactionId}/status`, { status: newStatus });
            if (resp.status === 200 || resp.status === 201) {
                alert('Transaction status updated.');
                location.reload();
            }
        } catch (err) {
            console.error('Error updating status', err);
            alert('Error updating status: ' + (err.response?.data?.message || err.message));
        }
    }
</script>
@endif
@endsection