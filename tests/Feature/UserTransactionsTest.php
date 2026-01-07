<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\TransactionService;

class UserTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_transactions()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 50.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 5]);

        $trx = $this->app->make(TransactionService::class)->createTransaction($user->id, [['product_id' => $product->id, 'qty' => 1]], ['tax_rate' => 0]);

        $this->actingAs($user)->get(route('transactions.index'))->assertStatus(200)->assertSee($trx->code);
        $this->actingAs($user)->get(route('transactions.show', $trx->id))->assertStatus(200)->assertSee(number_format($trx->grand_total, 2));
    }
}
