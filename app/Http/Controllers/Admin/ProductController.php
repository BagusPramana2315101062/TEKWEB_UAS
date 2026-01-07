<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->paginate(15);
        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.form', ['product' => new Product(), 'categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:products,slug',
            'description' => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'discount_type' => 'nullable|string|in:PERCENT',
            'discount_value' => 'nullable|numeric|min:0|max:100',
        ]);

        $data['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;

        // If admin provided a discount value but omitted the type, default to PERCENT
        if (array_key_exists('discount_value', $data) && ($data['discount_value'] !== null) && empty($data['discount_type'])) {
            $data['discount_type'] = 'PERCENT';
        }

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => "required|string|unique:products,slug,{$product->id}",
            'description' => 'nullable|string',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'discount_type' => 'nullable|string|in:PERCENT',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        $data['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;

        // If admin provided a discount value but omitted the type, default to PERCENT
        if (array_key_exists('discount_value', $data) && ($data['discount_value'] !== null) && empty($data['discount_type'])) {
            $data['discount_type'] = 'PERCENT';
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }
}
