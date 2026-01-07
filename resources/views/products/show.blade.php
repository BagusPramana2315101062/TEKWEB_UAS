@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <div class="flex flex-col md:flex-row gap-6">
        <div class="md:w-2/3 bg-white rounded shadow p-4">
            <h1 class="text-2xl font-semibold">{{ $product->name }}</h1>
            <div class="text-sm text-gray-600">Category: {{ optional($product->category)->name ?? 'Uncategorized' }}</div>
            <div class="mt-3 text-gray-700">{!! nl2br(e($product->description)) !!}</div>
            <div class="mt-4 flex items-center gap-4">
                <div class="text-xl font-bold">Rp {{ number_format($product->selling_price, 2) }}</div>
                @if($product->discount_type)
                    @php
                        $discType = $product->discount_type;
                        $discVal = (float) $product->discount_value;
                        if ($discType === 'PERCENT') {
                            $discountLabel = $discVal . '% off';
                            $discounted = number_format(round($product->selling_price * (1 - $discVal/100), 2), 2);
                        } else {
                            $discountLabel = 'Rp ' . number_format($discVal) . ' off per unit';
                            $discounted = number_format(round($product->selling_price - $discVal, 2), 2);
                        }
                    @endphp
                    <div class="text-sm text-green-600">{{ $discountLabel }} — Now Rp {{ $discounted }}</div>
                @endif
                <div class="text-sm text-gray-500">Stock: {{ $available }}</div>
            </div>

            <div class="mt-4">
                @auth
                    @if($available > 0)
                        <form action="{{ route('checkout.buy') }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <label for="qty" class="text-sm text-gray-600">Qty</label>
                            <input id="qty" name="qty" type="number" min="1" max="{{ $available }}" value="1" class="w-20 border rounded p-1" />
                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded">Buy Now</button>
                        </form>
                    @else
                        <div class="text-sm text-red-600">Product is out of stock.</div>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="px-3 py-1 bg-blue-600 text-white rounded">Login to buy</a>
                @endauth
            </div>

            <!-- Comments UI removed -->
        </div>

        <aside class="md:w-1/3 bg-white rounded shadow p-4">
            <div class="text-sm text-gray-500">Seller</div>
            <div class="mt-2">{{ $product->created_at->diffForHumans() }}</div>
        </aside>
    </div>
</div>

@endsection