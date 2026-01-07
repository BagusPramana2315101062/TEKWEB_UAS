@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold">Products</h1>
        <a href="{{ route('admin.products.create') }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded">Create Product</a>
    </div>

    @if(session('success'))
        <div class="mb-4 text-green-700">{{ session('success') }}</div>
    @endif

    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2 text-left">#</th>
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">Category</th>
                    <th class="px-4 py-2 text-left">Price</th>
                    <th class="px-4 py-2 text-left">Discount</th>
                    <th class="px-4 py-2 text-left">Stock</th>
                    <th class="px-4 py-2 text-left">Active</th>
                    <th class="px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($products as $p)
                <tr>
                    <td class="border px-4 py-2">{{ $p->id }}</td>
                    <td class="border px-4 py-2">{{ $p->name }}</td>
                    <td class="border px-4 py-2">{{ $p->category->name ?? '-' }}</td>
                    <td class="border px-4 py-2">{{ number_format($p->selling_price, 2) }}</td>
                    <td class="border px-4 py-2">
                        @if($p->discount_type)
                            @if($p->discount_type === 'PERCENT')
                                {{ (float)$p->discount_value }}% off
                            @else
                                Rp {{ number_format($p->discount_value, 0, ',', '.') }} off/unit
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="border px-4 py-2">{{ app(\App\Services\StockService::class)->getAvailableStockForProduct($p->id) }}</td>
                    <td class="border px-4 py-2">{{ $p->is_active ? 'Yes' : 'No' }}</td>
                    <td class="border px-4 py-2">
                        <a href="{{ route('admin.products.edit', $p) }}" class="text-blue-600 mr-2">Edit</a>
                        <a href="{{ route('admin.products.stock', $p) }}" class="text-indigo-600 mr-2">Adjust</a>
                        <form action="{{ route('admin.products.destroy', $p) }}" method="POST" class="inline-block" onsubmit="return confirm('Delete product?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No products found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
@endsection
