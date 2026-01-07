<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransactionService;
use App\Exceptions\InsufficientStockException;

class CheckoutController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function buy(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        try {
            $trx = $this->transactionService->createTransaction($user->id, [
                [
                    'product_id' => (int) $data['product_id'],
                    'qty' => (int) $data['qty'],
                ]
            ], ['tax_rate' => 0, 'status' => 'PENDING']);

            return redirect()->route('transactions.show', $trx->id)->with('success', 'Transaction created successfully.');
        } catch (InsufficientStockException $e) {
            return back()->withErrors(['qty' => 'Insufficient stock for requested quantity.']);
        } catch (\Exception $e) {
            // generic failure
            return back()->withErrors(['error' => 'Failed to create transaction.']);
        }
    }
}
