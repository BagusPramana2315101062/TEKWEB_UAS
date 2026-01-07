<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_admin_links()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $resp = $this->get('/admin/dashboard');
        $resp->assertStatus(200);
        $resp->assertSee('Transactions');
        $resp->assertDontSee('/admin/comments');
        $resp->assertSee('Activity Logs');
    }

    public function test_non_admin_does_not_see_admin_links_in_nav()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $resp = $this->get('/dashboard');
        $resp->assertStatus(200);
        // 'My Transactions' is shown to users, but admin-specific links should be absent
        $resp->assertDontSee('Admin');
        $resp->assertDontSee('Activity Logs');
        $resp->assertDontSee('/admin/comments');
    }
}
