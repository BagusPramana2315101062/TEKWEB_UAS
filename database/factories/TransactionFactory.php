<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'code' => 'TRX' . $this->faker->unique()->numerify('#####'),
            'subtotal' => $this->faker->randomFloat(2, 10000, 500000),
            'tax_rate' => 0,
            'tax_amount' => 0,
            'grand_total' => $this->faker->randomFloat(2, 10000, 500000),
            'status' => 'PENDING',
            'transacted_at' => now(),
        ];
    }
}
