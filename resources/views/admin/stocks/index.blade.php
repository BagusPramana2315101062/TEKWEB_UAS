@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <h1 class="text-2xl font-semibold mb-4">Stock Movements</h1>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2 text-left">#</th>
                    <th class="px-4 py-2 text-left">Product</th>
                    <th class="px-4 py-2 text-left">Type</th>
                    <th class="px-4 py-2 text-left">Qty</th>
                    <th class="px-4 py-2 text-left">Notes</th>
                    <th class="px-4 py-2 text-left">When</th>
                </tr>
            </thead>
            <tbody>
            @forelse($movements as $m)
                <tr>
                    <td class="border px-4 py-2">{{ $m->id }}</td>
                    <td class="border px-4 py-2">{{ $m->product->name ?? '-' }}</td>
                    <td class="border px-4 py-2">{{ $m->type }}</td>
                    <td class="border px-4 py-2">{{ $m->qty }}</td>
                    <td class="border px-4 py-2">{{ $m->notes }}</td>
                    <td class="border px-4 py-2">{{ $m->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No stock movements found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $movements->links() }}
    </div>
</div>
@endsection
