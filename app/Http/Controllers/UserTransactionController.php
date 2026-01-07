<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class UserTransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $transactions = Transaction::with('items.product')
            ->where('user_id', $user->id)
            ->orderByDesc('transacted_at')
            ->paginate(15);

        return view('transactions.index', compact('transactions'));
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $transaction = Transaction::with('items.product')->where('user_id', $user->id)->findOrFail($id);

        return view('transactions.show', compact('transaction'));
    }
}
