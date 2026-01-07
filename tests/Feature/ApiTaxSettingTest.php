<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\TaxSetting;

class ApiTaxSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_tax_setting()
    {
        TaxSetting::setRate(12.50);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/tax-setting')
            ->assertStatus(200)
            ->assertJson(['tax_rate' => 12.5]);
    }

    public function test_admin_can_update_tax_setting()
    {
        TaxSetting::setRate(9.00);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/tax-setting', ['tax_rate' => 7.5])
            ->assertStatus(200)
            ->assertJson(['tax_rate' => 7.5]);

        $this->assertDatabaseHas('tax_settings', ['tax_rate' => 7.5]);
    }

    public function test_update_rejects_invalid_values()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/tax-setting', ['tax_rate' => -5])
            ->assertStatus(422);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/tax-setting', ['tax_rate' => 150])
            ->assertStatus(422);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/tax-setting', ['tax_rate' => 'not-a-number'])
            ->assertStatus(422);
    }

    public function test_non_admin_cannot_update()
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/admin/tax-setting', ['tax_rate' => 5.0])
            ->assertStatus(403);
    }
}
