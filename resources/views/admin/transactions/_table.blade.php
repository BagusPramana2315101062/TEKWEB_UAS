<div class="bg-white shadow-sm rounded-lg overflow-hidden" id="transactions-table">
    @if(request('q'))
        <div class="px-4 py-2 text-sm text-gray-600">Showing results for: <strong>{{ request('q') }}</strong> &mdash; <a href="{{ route('admin.transactions.index') }}" class="underline">Clear</a></div>
    @endif
    <table class="min-w-full">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-left">#</th>
                <th class="px-4 py-2 text-left">Code</th>
                <th class="px-4 py-2 text-left">User</th>
                <th class="px-4 py-2 text-left">Amount</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2 text-left">Date</th>
                <th class="px-4 py-2 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td class="border px-4 py-2">{{ $t->id }}</td>
                    <td class="border px-4 py-2">{{ $t->code }}</td>
                    <td class="border px-4 py-2">{{ optional($t->user)->name ?? 'Guest' }}</td>
                    <td class="border px-4 py-2">{{ number_format($t->grand_total, 2) }}</td>
                    <td class="border px-4 py-2">{{ $t->status }}</td>
                    <td class="border px-4 py-2">{{ $t->transacted_at ? $t->transacted_at->format('Y-m-d H:i') : '-' }}</td>
                    <td class="border px-4 py-2">
                        <a href="{{ route('admin.transactions.show', $t->id) }}" class="text-blue-600 hover:text-blue-800 no-ajax">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination">
        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </div>
</div>