<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\ActivityLog;

class AdminTransactionActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_action_buttons_on_pending_transaction()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trx = Transaction::factory()->create(['status' => 'PENDING']);

        $this->actingAs($admin);

        $resp = $this->get(route('admin.transactions.show', $trx->id));
        $resp->assertStatus(200);
        $resp->assertSee('Ship');
        $resp->assertSee('Complete');
        $resp->assertSee('Cancel');
    }

    public function test_admin_transaction_detail_shows_financial_summary_and_status_badge()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trx = Transaction::factory()->create([
            'subtotal' => 1000,
            'discount_type' => 'PERCENT',
            'discount_value' => 10,
            'discount_nominal' => 100,
            'tax_rate' => 10,
            'tax_amount' => 90,
            'grand_total' => 990,
            'status' => 'PENDING',
        ]);

        $this->actingAs($admin);

        $resp = $this->get(route('admin.transactions.show', $trx->id));
        $resp->assertStatus(200);
        $resp->assertSee('Financial Summary');
        $resp->assertSee('Subtotal:');
        $resp->assertSee('Discount');
        $resp->assertSee('Taxable base:');
        $resp->assertSee('Tax amount:');
        $resp->assertSee('Grand total');
        $resp->assertSee($trx->code);
        $resp->assertSee('Ship');
    }

    public function test_admin_can_ship_via_button()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $trx = Transaction::factory()->create(['user_id' => $user->id, 'status' => 'PENDING']);

        $this->actingAs($admin);

        $resp = $this->post(route('admin.transactions.updateStatus', $trx->id), ['status' => 'SHIPPED']);
        $resp->assertRedirect(route('admin.transactions.show', $trx->id));

        $trx->refresh();
        $this->assertEquals('SHIPPED', $trx->status);
        $this->assertEquals($admin->id, $trx->processed_by);
        $this->assertNotNull($trx->processed_at);
    }

    public function test_admin_can_complete_transaction()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trx = Transaction::factory()->create(['status' => 'PENDING']);

        $this->actingAs($admin);

        $resp = $this->post(route('admin.transactions.updateStatus', $trx->id), ['status' => 'COMPLETED']);
        $resp->assertRedirect(route('admin.transactions.show', $trx->id));

        $trx->refresh();
        $this->assertEquals('COMPLETED', $trx->status);
        $this->assertEquals($admin->id, $trx->processed_by);
        $this->assertNotNull($trx->processed_at);
    }

    public function test_non_admin_does_not_see_action_buttons()
    {
        $user = User::factory()->create(['role' => 'user']);
        $trx = Transaction::factory()->create(['status' => 'PENDING']);

        $this->actingAs($user);

        $resp = $this->get(route('admin.transactions.show', $trx->id));
        $resp->assertStatus(403);
    }

    // New tests covering the public transaction view + API patch flow
    public function test_admin_sees_admin_section_on_public_transaction_view()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trx = Transaction::factory()->create(['status' => 'PENDING']);

        $this->actingAs($admin);

        $resp = $this->get(route('transaction.show', $trx->id));
        $resp->assertStatus(200);
        $resp->assertSee('Admin Actions');
        $resp->assertSee('Loading actions');
    }

    public function test_admin_can_update_status_via_api_patch()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trx = Transaction::factory()->create(['status' => 'PENDING']);

        // use Sanctum for API auth
        \Laravel\Sanctum\Sanctum::actingAs($admin);

        $resp = $this->patchJson(route('api.transactions.updateStatus', $trx->id), ['status' => 'SHIPPED']);
        $resp->assertStatus(200);
        $json = $resp->json();
        $this->assertEquals('Transaction status updated successfully.', $json['message']);
        $this->assertEquals('SHIPPED', $json['transaction']['status']);

        $trx->refresh();
        $this->assertEquals('SHIPPED', $trx->status);
        $this->assertEquals($admin->id, $trx->processed_by);

        // Verify an activity log was created for the update
        $log = ActivityLog::where('model_type', Transaction::class)->where('action', 'UPDATE')->where('model_id', $trx->id)->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('status', $log->changes);
        $this->assertEquals('PENDING', $log->changes['status']['old']);
        $this->assertEquals('SHIPPED', $log->changes['status']['new']);
    }
}
