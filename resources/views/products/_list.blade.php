<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach($products as $p)
        <div class="bg-white rounded shadow p-4 flex flex-col" aria-label="product-{{ $p->id }}">
            <div class="flex-1">
                <h3 class="font-semibold text-lg">{{ $p->name }}</h3>
                <div class="text-sm text-gray-600">{{ Str::limit($p->description, 120) }}</div>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <div class="text-lg font-bold">Rp {{ number_format($p->selling_price, 2) }}</div>
                <div class="text-sm text-gray-500">{{ optional($p->category)->name ?? 'Uncategorized' }}</div>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <a href="{{ route('products.show', $p->slug) }}" class="px-3 py-1 bg-blue-600 text-white rounded">View</a>
                <!-- Comments link removed -->
            </div>
        </div>
    @endforeach
</div>

<div class="mt-4">
    {{ $products->links() }}
</div>