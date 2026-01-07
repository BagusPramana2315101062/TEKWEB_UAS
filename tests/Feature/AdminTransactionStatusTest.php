<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Transaction;

class AdminTransactionStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_transaction_status_and_processed_by_is_set()
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

    public function test_non_admin_cannot_update_transaction_status()
    {
        $user = User::factory()->create(['role' => 'user']);
        $trx = Transaction::factory()->create(['status' => 'PENDING']);

        $this->actingAs($user);

        $resp = $this->post(route('admin.transactions.updateStatus', $trx->id), ['status' => 'SHIPPED']);

        $resp->assertStatus(403);
    }
}
