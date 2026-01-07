<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\ActivityLog;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_index_and_show()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Create a product (CREATE log) and then update it (UPDATE log)
        $product = Product::factory()->create([
            'selling_price' => 10.00,
            'purchase_price' => 5.00,
            'name' => 'Loggable Product'
        ]);

        $old = $product->selling_price;
        $new = 15.50;
        $product->update(['selling_price' => $new]);

        // Index page shows CREATE and UPDATE and description containing product name
        $resp = $this->get('/admin/activity-logs');
        $resp->assertStatus(200);
        $resp->assertSeeText('CREATE');
        $resp->assertSeeText('UPDATE');
        $resp->assertSeeText('Loggable Product');

        // Show page for the UPDATE log shows changed field and old/new values
        $updateLog = ActivityLog::where('model_type', Product::class)->where('action', 'UPDATE')->first();
        $this->assertNotNull($updateLog, 'Expected UPDATE activity log exists');

        $show = $this->get("/admin/activity-logs/{$updateLog->id}");
        $show->assertStatus(200);

        $show->assertSeeText('selling_price');
        $show->assertSeeText(number_format($old, 2));
        $show->assertSeeText(number_format($new, 2));
        $show->assertSeeText('View raw JSON');
    }

    public function test_non_admin_cannot_access_activity_logs()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $this->get('/admin/activity-logs')->assertForbidden();

        // Guest should be redirected to login
        auth()->logout();
        $this->get('/admin/activity-logs')->assertRedirect('/login');
    }

    public function test_filters_work_on_index()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Generate one product and one transaction to produce different model_type logs.
        $product = Product::factory()->create(['name' => 'Filter Product']);
        // Trigger another model's log by creating a transaction via service or directly:
        // For brevity, manually create an ActivityLog row for Transaction model to test filter behavior.
        \App\Models\ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'CREATE',
            'model_type' => \App\Models\Transaction::class,
            'model_id' => 999,
            'description' => 'Manual transaction log',
        ]);

        // Filter by model_type=Product
        $resp = $this->get('/admin/activity-logs?model_type=Product');
        $resp->assertStatus(200);
        $resp->assertSeeText('Product');
        $resp->assertDontSeeText('Manual transaction log');
    }
}
