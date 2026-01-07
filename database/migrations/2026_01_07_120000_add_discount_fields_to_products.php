<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('discount_type')->nullable()->after('selling_price'); // PERCENT or NOMINAL
            $table->decimal('discount_value', 14, 2)->default(0.00)->after('discount_type');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['discount_type','discount_value']);
        });
    }
};