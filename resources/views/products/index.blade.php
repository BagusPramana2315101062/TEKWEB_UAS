@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
        <h1 class="text-2xl font-semibold">Products</h1>
        <div class="flex gap-2 items-center">
            <input id="q" type="search" placeholder="Search products" value="{{ $q ?? '' }}" class="px-2 py-1 border rounded" aria-label="Search products">
            <select id="category" class="px-2 py-1 border rounded">
                <option value="">All categories</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ (int)($category ?? '') === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            <input id="min_price" type="number" step="0.01" placeholder="Min" value="{{ $min ?? '' }}" class="px-2 py-1 border rounded w-24" aria-label="Min price">
            <input id="max_price" type="number" step="0.01" placeholder="Max" value="{{ $max ?? '' }}" class="px-2 py-1 border rounded w-24" aria-label="Max price">
            <button id="clearFilters" class="px-3 py-1 border rounded">Clear</button>
        </div>
    </div>

    <div id="productsList">
        @include('products._list')
    </div>
</div>

<script>
    const listContainer = document.getElementById('productsList');
    const qInput = document.getElementById('q');
    const catInput = document.getElementById('category');
    const minInput = document.getElementById('min_price');
    const maxInput = document.getElementById('max_price');

    const fetchProducts = () => {
        const params = new URLSearchParams();
        if (qInput.value) params.set('q', qInput.value);
        if (catInput.value) params.set('category_id', catInput.value);
        if (minInput.value) params.set('min_price', minInput.value);
        if (maxInput.value) params.set('max_price', maxInput.value);

        fetch('/products?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => listContainer.innerHTML = html)
            .catch(err => console.error(err));
    }

    let timeout;
    [qInput, catInput, minInput, maxInput].forEach(el => el.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(fetchProducts, 300);
    }));

    document.getElementById('clearFilters').addEventListener('click', function () {
        qInput.value = '';
        catInput.value = '';
        minInput.value = '';
        maxInput.value = '';
        fetchProducts();
    });
</script>
@endsection