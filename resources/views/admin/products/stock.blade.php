@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold">Manage Stock: {{ $product->name }}</h1>
        <a href="{{ route('admin.products.index') }}" class="text-gray-600">Back to products</a>
    </div>

    @if(session('success'))
        <div class="mb-4 text-green-700">{{ session('success') }}</div>
    @endif

    <div class="bg-white shadow-sm rounded-lg p-6 mb-4">
        <div class="mb-2">Current Stock:</div>
        <div class="text-3xl font-bold">{{ $stock }}</div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="font-semibold mb-4">Adjust Stock</h2>
            <form action="{{ route('admin.products.stock.store', $product) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block mb-1">Type</label>
                    <select name="type" class="border rounded px-3 py-2 w-full">
                        <option value="IN">IN (add)</option>
                        <option value="OUT">OUT (remove)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block mb-1">Qty</label>
                    <input type="number" name="qty" min="1" value="1" class="border rounded px-3 py-2 w-full">
                </div>
                <div class="mb-4">
                    <label class="block mb-1">Notes (optional)</label>
                    <input type="text" name="notes" maxlength="255" class="border rounded px-3 py-2 w-full">
                </div>
                <div>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded">Apply</button>
                </div>
            </form>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h2 class="font-semibold mb-4">Recent Movements</h2>
            @forelse($movements as $m)
                <div class="border-b py-2">
                    <div class="text-sm text-gray-600">{{ $m->type }} &middot; {{ $m->qty }} &middot; {{ $m->created_at->diffForHumans() }}</div>
                    <div class="text-sm">{{ $m->notes }}</div>
                </div>
            @empty
                <div class="text-gray-500">No recent movements.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
