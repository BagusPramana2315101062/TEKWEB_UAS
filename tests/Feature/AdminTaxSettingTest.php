<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\TransactionService;
use App\Models\TaxSetting;

class AdminTaxSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_tax_settings_page()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        TaxSetting::setRate(10.0);

        $this->actingAs($admin)
            ->get(route('admin.settings.tax'))
            ->assertStatus(200)
            ->assertSee('Tax Settings')
            ->assertSee('value="10"', false);
    }

    public function test_admin_can_update_tax_rate_and_it_applies_to_new_transactions()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.tax.update'), ['tax_rate' => '11'])
            ->assertRedirect(route('admin.settings.tax'));

        $this->assertEquals(11.0, TaxSetting::getRate());

        // Create product and stock, then create transaction and assert tax applied
        $product = Product::factory()->create(['selling_price' => 100.00]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'qty' => 10,
            'notes' => 'seed stock',
        ]);

        $service = $this->app->make(TransactionService::class);
        $trx = $service->createTransaction(null, [
            ['product_id' => $product->id, 'qty' => 1]
        ], ['discount_value' => 0]);

        $this->assertEquals(11.0, (float) $trx->tax_rate);
        $this->assertEquals(11.00, (float) $trx->tax_amount);
    }

    public function test_validation_rejects_invalid_tax_rate()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.tax.update'), ['tax_rate' => '-5'])
            ->assertSessionHasErrors('tax_rate');

        $this->actingAs($admin)
            ->post(route('admin.settings.tax.update'), ['tax_rate' => 'not-a-number'])
            ->assertSessionHasErrors('tax_rate');
    }
}
