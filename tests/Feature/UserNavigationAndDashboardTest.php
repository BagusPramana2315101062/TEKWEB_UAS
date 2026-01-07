<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNavigationAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_nav_shows_products_for_guest()
    {
        $resp = $this->get(route('products.index'));
        $resp->assertStatus(200);
        $resp->assertSee('Products');
    }

    public function test_authenticated_user_nav_has_my_transactions_and_profile()
    {
        $user = User::factory()->create();
        $resp = $this->actingAs($user)->get(route('dashboard'));

        $resp->assertStatus(200);
        $resp->assertSee('My Transactions');
        $resp->assertSee('Profile');
    }

    public function test_admin_nav_has_admin_links()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $resp = $this->actingAs($admin)->get(route('dashboard'));

        $resp->assertStatus(200);
        $resp->assertSee('Admin');
        $resp->assertSee('Transactions');
        $resp->assertDontSee('/admin/comments');
    }

    public function test_dashboard_shows_cards_and_recent_transactions()
    {
        $user = User::factory()->create();
        // create products and transactions
        Product::factory()->count(3)->create();

        $trx = Transaction::factory()->create([
            'user_id' => $user->id,
            'status' => 'PENDING',
            'grand_total' => 125000,
        ]);

        $resp = $this->actingAs($user)->get(route('dashboard'));
        $resp->assertStatus(200);
        $resp->assertSee('Products Available');
        $resp->assertSee('View Products');
        $resp->assertSee('View My Transactions');
        $resp->assertSee($trx->code);
    }
}
