@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <a href="{{ route('admin.transactions.index') }}" class="text-sm text-gray-600 mb-4 inline-block">&larr; Back</a>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-2 flex items-start justify-between">
            <div>
                Transaction <span class="font-semibold">{{ $transaction->code }}</span>
                <div class="text-sm text-gray-500">ID: #{{ $transaction->id }}</div>
            </div>

            <div class="flex items-center space-x-3">
                @php
                    $status = $transaction->status;
                    $statusClasses = match($status) {
                        'PENDING' => 'bg-amber-100 text-amber-800',
                        'SHIPPED' => 'bg-blue-100 text-blue-800',
                        'COMPLETED' => 'bg-green-100 text-green-800',
                        'CANCELLED' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusClasses }}">{{ $status }}</span>

                @if(auth()->user()?->role === 'admin')
                    <div class="flex items-center space-x-2">
                        @if($transaction->status === 'PENDING')
                            <button class="action-button bg-blue-600 hover:bg-blue-700 text-white px-3 py-2" data-action="{{ route('admin.transactions.updateStatus', $transaction->id) }}" data-status="SHIPPED" data-label="Mark as shipped">Ship</button>
                            <button class="action-button bg-blue-600 hover:bg-blue-700 text-white px-3 py-2" data-action="{{ route('admin.transactions.updateStatus', $transaction->id) }}" data-status="CANCELLED" data-label="Cancel transaction">Cancel</button>
                        @endif

                        @if(in_array($transaction->status, ['PENDING','SHIPPED']))
                            <button class="action-button bg-blue-600 hover:bg-blue-700 text-white px-3 py-2" data-action="{{ route('admin.transactions.updateStatus', $transaction->id) }}" data-status="COMPLETED" data-label="Mark as completed">Complete</button>
                        @endif

                        <a href="{{ route('admin.transactions.export', array_merge(request()->all(), ['ids' => $transaction->id])) }}" class="px-3 py-2 bg-white border rounded text-sm">Export CSV</a>
                    </div>
                @endif
            </div>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="space-y-2">
                <div><strong>User:</strong> {{ optional($transaction->user)->name ?? 'Guest' }}</div>
                <div><strong>Email:</strong> <span class="text-sm text-gray-700">{{ optional($transaction->user)->email ?? '-' }}</span></div>
                <div><strong>Date:</strong> {{ $transaction->transacted_at ? $transaction->transacted_at->format('Y-m-d H:i') : '-' }}</div>
                <div><strong>Processed By:</strong> {{ optional($transaction->processedBy)->name ?? '-' }}</div>
                <div><strong>Processed At:</strong> {{ $transaction->processed_at ? $transaction->processed_at->format('Y-m-d H:i') : '-' }}</div>
            </div>

            <div>
                @php
                    $discountNominal = $transaction->discount_nominal ?? 0.00;
                    $discountType = $transaction->discount_type ?? null;
                    $taxRate = $transaction->tax_rate ?? null;
                    $taxableBase = max(0, $transaction->subtotal - $discountNominal);
                @endphp

                <div class="bg-gray-50 p-4 rounded">
                    <h4 class="font-medium mb-2">Financial Summary</h4>
                    <div class="text-sm text-gray-700 grid grid-cols-2 gap-2">
                        <div>Subtotal:</div><div class="text-right">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</div>
                        <div>Discount @if($discountType) ({{ $discountType }}) @endif :</div><div class="text-right">Rp {{ number_format($discountNominal, 0, ',', '.') }}</div>
                        <div>Taxable base:</div><div class="text-right">Rp {{ number_format($taxableBase, 0, ',', '.') }}</div>
                        <div>Tax rate:</div><div class="text-right">{{ $taxRate ? $taxRate . '%' : '-' }}</div>
                        <div>Tax amount:</div><div class="text-right">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</div>
                        <div class="font-semibold">Grand total:</div><div class="text-right font-semibold text-green-600">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm modal (hidden) -->
        <div id="confirm-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
            <div class="bg-white rounded shadow-lg w-80 h-80 sm:w-96 sm:h-96 p-6 flex flex-col justify-between overflow-hidden">
                <div>
                    <h3 id="confirm-title" class="text-lg font-semibold">Confirm action</h3>
                    <p id="confirm-message" class="text-sm text-gray-600 mt-2">Are you sure you want to perform this action?</p>
                </div>

                <form id="confirm-form" method="POST" class="mt-4">
                    @csrf
                    <input type="hidden" name="status" id="confirm-status" value="">
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="confirm-cancel" class="px-4 py-2 bg-white border rounded">Cancel</button>
                        <button type="submit" id="confirm-submit" class="px-4 py-2 bg-red-600 text-white rounded">Confirm</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('confirm-modal');
                const form = document.getElementById('confirm-form');
                const title = document.getElementById('confirm-title');
                const message = document.getElementById('confirm-message');
                const statusInput = document.getElementById('confirm-status');
                const cancelBtn = document.getElementById('confirm-cancel');

                function openModal() {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.getElementById('confirm-submit').focus();
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                document.querySelectorAll('.action-button').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const action = btn.getAttribute('data-action');
                        const status = btn.getAttribute('data-status');
                        const label = btn.getAttribute('data-label');

                        form.action = action;
                        statusInput.value = status;
                        title.textContent = label;
                        message.textContent = 'Are you sure you want to ' + label.toLowerCase() + '?';
                        openModal();
                    });
                });

                cancelBtn.addEventListener('click', closeModal);

                // close when clicking outside the dialog (overlay)
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });

                // close on Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });
            });
        </script>


        <h3 class="text-lg font-medium mt-6 mb-2">Items</h3>
        <table class="min-w-full mb-4">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Product</th>
                    <th class="px-4 py-2 text-left">Qty</th>
                    <th class="px-4 py-2 text-left">Price</th>
                    <th class="px-4 py-2 text-left">Discount</th>
                    <th class="px-4 py-2 text-left">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->items as $item)
                    <tr>
                        <td class="border px-4 py-2">{{ optional($item->product)->name ?? 'Product #' . $item->product_id }}</td>
                        <td class="border px-4 py-2">{{ $item->qty }}</td>
                        <td class="border px-4 py-2">{{ number_format($item->price, 2) }}</td>
                        <td class="border px-4 py-2">
                            @if($item->discount_nominal && $item->discount_nominal > 0)
                                @if($item->discount_type === 'PERCENT')
                                    {{ $item->discount_value }}% (Rp {{ number_format($item->discount_nominal, 2) }})
                                @elseif($item->discount_type === 'NOMINAL')
                                    Rp {{ number_format($item->discount_value, 2) }} / unit (Rp {{ number_format($item->discount_nominal, 2) }})
                                @else
                                    Rp {{ number_format($item->discount_nominal, 2) }}
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="border px-4 py-2">{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection