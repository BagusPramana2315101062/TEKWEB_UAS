<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tax_settings')) {
            Schema::create('tax_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('tax_rate', 8, 2)->default(10.00);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tax_settings');
    }
};
