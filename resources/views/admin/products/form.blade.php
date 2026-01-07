@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6">
    <h1 class="text-2xl font-semibold mb-4">{{ $product->exists ? 'Edit Product' : 'Create Product' }}</h1>

    @if ($errors->any())
        <div class="mb-4 text-red-700">
            <ul>
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST">
        @csrf
        @if($product->exists) @method('PUT') @endif

        <div class="mb-4">
            <label class="block mb-1">Kategori</label>
            <select name="category_id" class="w-full border px-2 py-1" required>
                <option value="">-- Pilih Kategori --</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ old('category_id', $product->category_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" class="w-full border px-2 py-1" required>
        </div>

        <div class="mb-4">
            <label class="block mb-1">Slug</label>
            <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="w-full border px-2 py-1" required>
        </div>

        <div class="mb-4">
            <label class="block mb-1">Description</label>
            <textarea name="description" class="w-full border px-2 py-1">{{ old('description', $product->description) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block mb-1">Purchase Price</label>
                <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" class="w-full border px-2 py-1" required>
            </div>
            <div>
                <label class="block mb-1">Selling Price</label>
                <input type="number" step="0.01" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" class="w-full border px-2 py-1" required>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block mb-1">Discount Type</label>
                <select name="discount_type" class="w-full border px-2 py-1">
                    <option value="" {{ old('discount_type', $product->discount_type) == '' ? 'selected' : '' }}>None</option>
                    <option value="PERCENT" {{ old('discount_type', $product->discount_type) == 'PERCENT' ? 'selected' : '' }}>Percent (%)</option>
                </select>
            </div>
            <div>
                <label class="block mb-1">Discount Value</label>
                <input type="number" step="0.01" min="0" name="discount_value" value="{{ old('discount_value', $product->discount_value) }}" class="w-full border px-2 py-1">
                <div class="text-xs text-gray-500 mt-1">Discount must be a percent (0-100). If you provide a value but leave the type empty, it will default to Percent (0-100).</div>

        <div class="flex gap-2">
            <button class="bg-blue-600 text-white px-4 py-2 rounded">{{ $product->exists ? 'Update' : 'Create' }}</button>
            <a href="{{ route('admin.products.index') }}" class="inline-block px-4 py-2 border rounded">Cancel</a>
        </div>
    </form>
</div>
@endsection
