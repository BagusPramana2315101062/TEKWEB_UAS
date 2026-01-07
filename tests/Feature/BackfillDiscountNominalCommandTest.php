<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Transaction;
use App\Models\TransactionItem;

class BackfillDiscountNominalCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_command_updates_discount_and_tax_fields()
    {
        // create a few transactions with various discount inputs
        $t1 = Transaction::create(['code' => 'T1', 'subtotal' => 100, 'discount_type' => 'PERCENT', 'discount_value' => 10, 'tax_amount' => 0, 'grand_total' => 100]);
        $t2 = Transaction::create(['code' => 'T2', 'subtotal' => 50, 'discount_type' => null, 'discount_value' => 5, 'tax_amount' => 0, 'grand_total' => 50]);
        $t3 = Transaction::create(['code' => 'T3', 'subtotal' => 20, 'discount_type' => 'PERCENT', 'discount_value' => 150, 'tax_amount' => 0, 'grand_total' => 20]);

        // run command
        $this->artisan('transactions:backfill-discount-nominal')->assertExitCode(0);

        $t1->refresh(); $t2->refresh(); $t3->refresh();

        $this->assertEquals(10.00, (float)$t1->discount_nominal);
        $this->assertGreaterThan(0, (float)$t1->tax_amount);

        $this->assertEquals(5.00, (float)$t2->discount_nominal);

        // 150% should be clamped to subtotal 20
        $this->assertEquals(20.00, (float)$t3->discount_nominal);
        $this->assertEquals(0.00, (float)$t3->tax_amount);
    }
}
