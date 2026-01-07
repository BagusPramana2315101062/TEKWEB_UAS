<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Models\Transaction;
use Carbon\Carbon;

class TransactionCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_creation_triggers_reports_tag_flush_when_supported()
    {
        // Mock tag support: expect tags('reports') -> flush() to be called
        Cache::shouldReceive('tags')->once()->with(['reports','transactions_per_month'])->andReturnSelf();
        Cache::shouldReceive('flush')->once();
        Cache::shouldReceive('tags')->once()->with(['reports','category_share'])->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $user = \App\Models\User::factory()->create();

        Transaction::create([ 'user_id' => $user->id, 'code' => 'X1-'.uniqid(), 'subtotal' => 10, 'grand_total' => 10, 'status' => 'PAID', 'transacted_at' => Carbon::now() ]);

        // If no exceptions, the expectations on Cache facade are satisfied
        $this->assertTrue(true);
    }
}
