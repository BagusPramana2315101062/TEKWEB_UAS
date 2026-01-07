<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $movements = StockMovement::with('product')->latest()->paginate(25);
        return view('admin.stocks.index', compact('movements'));
    }

    public function product(Product $product)
    {
        $stock = $this->stockService->getAvailableStockForProduct($product->id);
        $movements = StockMovement::where('product_id', $product->id)->latest()->take(20)->get();

        return view('admin.products.stock', compact('product', 'stock', 'movements'));
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'type' => 'required|in:IN,OUT',
            'qty' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($data['type'] === 'OUT') {
            $available = $this->stockService->getAvailableStockForProduct($product->id);
            if ($available < $data['qty']) {
                return back()->withErrors(['qty' => 'Not enough stock available.']);
            }
        }

        $movement = $this->stockService->createStockMovement([
            'product_id' => $product->id,
            'type' => $data['type'],
            'qty' => $data['qty'],
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.products.stock', $product)->with('success', 'Stock updated.');
    }
}
