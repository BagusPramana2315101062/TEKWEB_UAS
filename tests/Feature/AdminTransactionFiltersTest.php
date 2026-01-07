<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\TransactionService;
use Illuminate\Support\Arr;

class AdminTransactionFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_by_status_and_search_and_sort()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $product = Product::factory()->create(['selling_price' => 100]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10]);

        $service = $this->app->make(TransactionService::class);

        // create several transactions
        $t1 = $service->createTransaction($admin->id, [['product_id' => $product->id, 'qty' => 1]], ['status' => 'PAID']);
        $t2 = $service->createTransaction($admin->id, [['product_id' => $product->id, 'qty' => 2]], ['status' => 'PAID']);
        $t3 = $service->createTransaction($admin->id, [['product_id' => $product->id, 'qty' => 1]], ['status' => 'PENDING']);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index', ['status' => 'PAID', 'q' => substr($t1->code, 0, 4), 'sort_by' => 'grand_total', 'sort_dir' => 'asc']))
            ->assertStatus(200)
            ->assertSee($t1->code)
            ->assertSee($t2->code)
            ->assertDontSee($t3->code);
    }

    public function test_pagination_works_and_is_ajax_loadable()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['selling_price' => 10]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 200]);

        $service = $this->app->make(TransactionService::class);
        for ($i = 0; $i < 25; $i++) {
            $service->createTransaction($admin->id, [['product_id' => $product->id, 'qty' => 1]], ['status' => 'PAID']);
        }

        $resp = $this->actingAs($admin)
            ->get(route('admin.transactions.index', ['per_page' => 10]));
        $resp->assertStatus(200);
        $resp->assertSee('class="pagination"', false);

        // Simulate AJAX page request
        $ajax = $this->actingAs($admin)->get(route('admin.transactions.index', ['per_page' => 10, 'page' => 2]), ['X-Requested-With' => 'XMLHttpRequest']);
        $ajax->assertStatus(200);
        $ajax->assertSee('class="pagination"', false);
    }
}
