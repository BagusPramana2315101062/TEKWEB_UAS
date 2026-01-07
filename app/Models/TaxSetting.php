<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaxSetting extends Model
{
    protected $fillable = ['tax_rate'];

    protected $casts = [
        'tax_rate' => 'float',
    ];

    // Returns the currently configured tax rate from DB, or falls back to config
    public static function getRate(): float
    {
        try {
            // Avoid querying if the table does not exist (e.g., migrations not run yet)
            if (!\Illuminate\Support\Facades\Schema::hasTable('tax_settings')) {
                return (float) config('tax.default_rate', 10.0);
            }

            return Cache::remember('tax_rate', 60, function () {
                $row = self::first();
                return $row ? (float) $row->tax_rate : (float) config('tax.default_rate', 10.0);
            });
        } catch (\Throwable $e) {
            // Any DB error: fall back to config default
            return (float) config('tax.default_rate', 10.0);
        }
    }

    // Update rate and refresh cache
    public static function setRate(float $rate): self
    {
        $instance = self::first() ?? new self();
        $instance->tax_rate = $rate;
        $instance->save();
        Cache::put('tax_rate', (float) $instance->tax_rate, 60);
        return $instance;
    }
}
