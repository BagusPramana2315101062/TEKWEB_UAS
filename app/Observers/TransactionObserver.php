<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TransactionObserver
{
    protected AuditService $audit;

    protected array $whitelist = ['status', 'grand_total', 'tax_amount', 'discount_value'];

    public function __construct()
    {
        $this->audit = app(AuditService::class);
    }

    public function created(Transaction $transaction)
    {
        $this->audit->log(optional(Auth::user())->id, 'CREATE', get_class($transaction), $transaction->id, "Created transaction {$transaction->code}", $transaction->toArray());

        // Invalidate report caches that depend on transactions
        if ($transaction->transacted_at) {
            try {
                $year = (int) $transaction->transacted_at->format('Y');
                // try to invalidate tag-based caches
                try {
                    Cache::tags(['reports','transactions_per_month'])->flush();
                } catch (\BadMethodCallException $e) {
                    // fall back to per-year key removal
                    Cache::forget("reports:transactions_per_month:{$year}");
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // attempt to flush category share reports only (tagged), otherwise fall back to plain cache put removal is not possible, so skip
        try {
            Cache::tags(['reports','category_share'])->flush();
        } catch (\BadMethodCallException $e) {
            // cannot flush non-taggable store; nothing more we can do safely without knowing keys
        }
    }

    public function updated(Transaction $transaction)
    {
        $before = $transaction->getOriginal();
        $after = $transaction->getAttributes();
        $changes = $this->audit->computeDiff($before, $after, $this->whitelist);

        if (!empty($changes)) {
            $this->audit->log(optional(Auth::user())->id, 'UPDATE', get_class($transaction), $transaction->id, "Updated transaction {$transaction->code}", $changes);
        }
    }

    public function deleted(Transaction $transaction)
    {
        $this->audit->log(optional(Auth::user())->id, 'DELETE', get_class($transaction), $transaction->id, "Deleted transaction {$transaction->code}", $transaction->toArray());
    }
}
