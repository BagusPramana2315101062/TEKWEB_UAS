<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;

class BackfillDiscountNominal extends Command
{
    protected $signature = 'transactions:backfill-discount-nominal {--chunk=100}';
    protected $description = 'Backfill discount_nominal and tax_amount for existing transactions using current rules/config';

    public function handle()
    {
        $chunk = (int) $this->option('chunk');
        $this->info("Starting backfill (chunk={$chunk})...");

        Transaction::chunk($chunk, function ($transactions) {
            foreach ($transactions as $trx) {
                $subtotal = (float) $trx->subtotal;
                $discountType = strtoupper($trx->discount_type ?? '');
                $discountValue = (float) ($trx->discount_value ?? 0.0);

                if ($discountType === 'PERCENT') {
                    $discountNominal = round($subtotal * ($discountValue / 100), 2);
                } else {
                    $discountNominal = round($discountValue, 2);
                }

                if ($discountNominal < 0) $discountNominal = 0.0;
                if ($discountNominal > $subtotal) $discountNominal = $subtotal;

                $taxRate = (float) ($trx->tax_rate ?? 0.0);
                // if stored tax_rate is zero or unset, use DB setting (or config fallback)
                if ($taxRate <= 0) $taxRate = \App\Models\TaxSetting::getRate();
                $taxableBase = max(0, $subtotal - $discountNominal);
                $taxAmount = round($taxableBase * ($taxRate / 100), 2);

                $trx->discount_nominal = $discountNominal;
                $trx->tax_rate = $taxRate;
                $trx->tax_amount = $taxAmount;
                $trx->grand_total = round($subtotal - $discountNominal + $taxAmount, 2);
                $trx->save();
            }
        });

        $this->info('Backfill completed.');
        return 0;
    }
}
