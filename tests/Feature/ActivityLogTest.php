<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\StockMovement;
use App\Services\TransactionService;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_create_update_delete_logs_created_and_updated_and_deleted()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create
        $product = Product::create([
            'category_id' => \App\Models\Category::factory()->create()->id,
            'name' => 'Log Product',
            'slug' => 'log-product',
            'purchase_price' => 5.00,
            'selling_price' => 10.00,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => Product::class,
            'model_id' => $product->id,
            'action' => 'CREATE',
        ]);

        // Update
        $product->update(['selling_price' => 12.00]);

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => Product::class,
            'model_id' => $product->id,
            'action' => 'UPDATE',
        ]);

        $log = ActivityLog::where('model_type', Product::class)->where('model_id', $product->id)->where('action','UPDATE')->first();
        $this->assertNotEmpty($log->changes);
        $this->assertArrayHasKey('selling_price', $log->changes);
        $this->assertEquals(10.00, $log->changes['selling_price']['old']);
        $this->assertEquals(12.00, $log->changes['selling_price']['new']);

        // Delete
        $product->delete();
        $this->assertDatabaseHas('activity_logs', [
            'model_type' => Product::class,
            'model_id' => $product->id,
            'action' => 'DELETE',
        ]);
    }

    public function test_transaction_creation_logs_activity()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $product = Product::factory()->create(['selling_price' => 50.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 5]);

        $service = $this->app->make(TransactionService::class);
        $trx = $service->createTransaction($admin->id, [ ['product_id' => $product->id, 'qty' => 1] ]);

        $this->assertDatabaseHas('activity_logs', [
            'model_type' => \App\Models\Transaction::class,
            'model_id' => $trx->id,
            'action' => 'CREATE',
        ]);

        // Also ensure stock movement creation logged
        $sm = \App\Models\StockMovement::where('ref_type','transaction')->where('ref_id',$trx->id)->first();
        $this->assertNotNull($sm);
        $this->assertDatabaseHas('activity_logs', [
            'model_type' => \App\Models\StockMovement::class,
            'model_id' => $sm->id,
            'action' => 'CREATE',
        ]);
    }
}
