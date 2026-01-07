<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\TransactionService;

class AdminTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_index_and_show()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $product = Product::factory()->create(['selling_price' => 120.00]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'qty' => 5,
            'notes' => 'seed stock',
        ]);

        $service = $this->app->make(TransactionService::class);
        $trx = $service->createTransaction($admin->id, [
            ['product_id' => $product->id, 'qty' => 1]
        ], ['tax_rate' => 0]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index'))
            ->assertStatus(200)
            ->assertSee($trx->code);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $trx->id))
            ->assertStatus(200)
            ->assertSee($trx->code)
            ->assertSee(number_format($trx->grand_total, 0, ',', '.'))
            // ensure admin navigation is present on admin pages
            ->assertSee('Admin');
    }

    public function test_admin_index_uses_admin_show_links()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['selling_price' => 100.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 5, 'notes' => 'seed']);

        $service = $this->app->make(TransactionService::class);
        $trx = $service->createTransaction($admin->id, [['product_id' => $product->id, 'qty' => 1]], ['tax_rate' => 0]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index'))
            ->assertStatus(200)
            ->assertSee(route('admin.transactions.show', $trx->id))
            ->assertDontSee(route('transaction.show', $trx->id));
    }

    public function test_non_admin_cannot_access_transactions()
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.transactions.index'))
            ->assertStatus(403);
    }

    public function test_admin_can_search_transactions_by_user_name_and_email()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'Alice Johnson', 'email' => 'alice@example.com']);

        $product = Product::factory()->create(['selling_price' => 50.00]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'qty' => 5,
            'notes' => 'seed stock',
        ]);

        $service = $this->app->make(TransactionService::class);
        $trx = $service->createTransaction($user->id, [
            ['product_id' => $product->id, 'qty' => 1]
        ], ['tax_rate' => 0]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index', ['q' => 'Alice']))
            ->assertStatus(200)
            ->assertSee($trx->code);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index', ['q' => 'alice@example.com']))
            ->assertStatus(200)
            ->assertSee($trx->code);
    }

    public function test_admin_show_page_displays_user_email()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'Email Test', 'email' => 'email.test@example.com']);

        $product = Product::factory()->create(['selling_price' => 75.00]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'qty' => 5,
            'notes' => 'seed stock',
        ]);

        $service = $this->app->make(TransactionService::class);
        $trx = $service->createTransaction($user->id, [
            ['product_id' => $product->id, 'qty' => 1]
        ], ['tax_rate' => 0]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $trx->id))
            ->assertStatus(200)
            ->assertSee('Email:')
            ->assertSee('email.test@example.com');
    }

    public function test_search_scales_with_many_transactions()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // create many transactions (with auto-created users)
        \App\Models\Transaction::factory()->count(200)->create();

        // create a target user/transaction that should be found
        $targetUser = User::factory()->create(['name' => 'Zed Search', 'email' => 'zed.search@example.com']);
        $targetTrx = \App\Models\Transaction::factory()->create(['user_id' => $targetUser->id, 'code' => 'TRX-UNIQUE-SEARCH-1']);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index', ['q' => 'Zed Search']))
            ->assertStatus(200)
            ->assertSee($targetTrx->code);
    }

    public function test_admin_can_export_csv_transactions()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'CSV Tester', 'email' => 'csv@example.com']);

        $product = Product::factory()->create(['selling_price' => 75.00]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'qty' => 5,
            'notes' => 'seed stock',
        ]);

        $service = $this->app->make(TransactionService::class);
        $trx = $service->createTransaction($user->id, [
            ['product_id' => $product->id, 'qty' => 1]
        ], ['tax_rate' => 0]);

        $resp = $this->actingAs($admin)
            ->get(route('admin.transactions.export', ['q' => 'CSV Tester']));

        $resp->assertStatus(200);
        // Content-Type may include charset; assert it contains text/csv
        $this->assertStringContainsString('text/csv', $resp->headers->get('content-type') ?? '');
        $content = (string) $resp->getContent();
        $this->assertStringContainsString('id,code,user_name,user_email,subtotal,discount_nominal,tax_amount,grand_total,status,transacted_at,processed_by,processed_at', $content);
        $this->assertStringContainsString($trx->code, $content);
    }
}
