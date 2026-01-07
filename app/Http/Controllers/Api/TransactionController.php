<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TransactionService;
use App\Exceptions\InsufficientStockException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function store(Request $request): JsonResponse
    {
        // Use Validator so we can apply conditional checks (percent <= 100)
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|string|in:PERCENT',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:PERCENT,NOMINAL',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        $validator->after(function ($v) use ($request) {
            if ($request->filled('discount_type') && strtoupper($request->input('discount_type')) === 'PERCENT') {
                $val = (float) $request->input('discount_value', 0);
                if ($val < 0 || $val > 100) {
                    $v->errors()->add('discount_value', 'When discount_type is PERCENT, discount_value must be between 0 and 100.');
                }
            }

            // Validate per-item discount values when provided
            foreach ($request->input('items', []) as $idx => $it) {
                if (!empty($it['discount_type']) && strtoupper($it['discount_type']) === 'PERCENT') {
                    $val = (float) ($it['discount_value'] ?? 0);
                    if ($val < 0 || $val > 100) {
                        $v->errors()->add("items.{$idx}.discount_value", 'When item discount_type is PERCENT, discount_value must be between 0 and 100.');
                    }
                }
                if (!empty($it['discount_type']) && strtoupper($it['discount_type']) !== 'PERCENT') {
                    $v->errors()->add("items.{$idx}.discount_type", 'Only PERCENT item discounts are supported.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $items = $data['items'];
        $meta = [
            'discount_type' => $data['discount_type'] ?? null,
            'discount_value' => $data['discount_value'] ?? 0.00,
            // tax is determined by server config, not client
            'status' => 'PAID',
        ];

        try {
            $userId = $request->user()->id; // middleware auth:sanctum ensures user is present
            $trx = $this->transactionService->createTransaction($userId, $items, $meta);

            return response()->json([
                'transaction_id' => $trx->id,
                'code' => $trx->code,
                'subtotal' => (float) $trx->subtotal,
                'discount_type' => $trx->discount_type,
                'discount_value' => (float) $trx->discount_value,
                'discount_nominal' => (float) $trx->discount_nominal,
                'tax_rate' => (float) $trx->tax_rate,
                'tax_amount' => (float) $trx->tax_amount,
                'grand_total' => (float) $trx->grand_total,
                'status' => $trx->status,
                'transacted_at' => $trx->transacted_at,
                'items' => $trx->items->map(function ($i) {
                    return [
                        'product_id' => $i->product_id,
                        'qty' => $i->qty,
                        'price' => (float) $i->price,
                        'line_total' => (float) $i->line_total,
                    ];
                })->values(),
            ], 201);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'items' => $e->getItems(),
            ], 422);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create transaction.'], 500);
        }
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:PENDING,SHIPPED,COMPLETED,CANCELLED',
        ]);

        $transaction = $this->transactionService->getTransactionById($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        $transaction->status = $data['status'];
        $transaction->processed_by = $request->user()?->id ?? null;
        $transaction->processed_at = now();
        $transaction->save();

        return response()->json([
            'message' => 'Transaction status updated successfully.',
            'transaction' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'processed_by' => optional($transaction->processedBy)->name,
                'processed_at' => $transaction->processed_at,
            ],
        ]);
    }
}

