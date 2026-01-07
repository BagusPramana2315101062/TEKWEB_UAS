<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tax_settings')) {
            Schema::create('tax_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('tax_rate', 5, 2)->default(10.00)->comment('Tax rate as percentage (0-100)');
                $table->timestamps();
            });

            // Insert a default row if none exists
            \Illuminate\Support\Facades\DB::table('tax_settings')->insert([
                'tax_rate' => config('tax.default_rate', 10.00),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
