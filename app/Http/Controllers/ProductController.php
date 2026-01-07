<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Services\StockService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $category = $request->query('category_id');
        $min = $request->query('min_price');
        $max = $request->query('max_price');

        $query = Product::with('category')->where('is_active', true);

        if ($q) {
            $query->where('name', 'like', "%{$q}%");
        }
        if ($category) {
            $query->where('category_id', $category);
        }
        if ($min !== null) {
            $query->where('selling_price', '>=', (float)$min);
        }
        if ($max !== null && $max !== '') {
            $query->where('selling_price', '<=', (float)$max);
        }

        $products = $query->latest()->paginate(12)->withQueryString();

        if ($request->ajax()) {
            return view('products._list', compact('products'));
        }

        $categories = Category::orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'q', 'category', 'min', 'max'));
    }

    public function show(Product $product, StockService $stockService)
    {
        $available = $stockService->getAvailableStockForProduct($product->id);

        if (request()->wantsJson() || request()->ajax()) {
            $data = $product->toArray();
            $discountedPrice = (float) $product->selling_price;
            if ($product->discount_type === 'PERCENT') {
                $pct = (float) $product->discount_value;
                if ($pct < 0) $pct = 0.0;
                if ($pct > 100) $pct = 100.0;
                $discountedPrice = round($discountedPrice * (1 - $pct / 100), 2);
            } else if ($product->discount_type === 'NOMINAL') {
                $discountedPrice = round(max(0, $discountedPrice - (float)$product->discount_value), 2);
            }

            $data['discounted_price'] = $discountedPrice;
            $data['available'] = $available;

            return response()->json($data);
        }

        return view('products.show', compact('product', 'available'));
    }
}
