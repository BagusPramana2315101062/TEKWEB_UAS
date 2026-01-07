<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->string('discount_type')->nullable()->after('line_total');
            $table->decimal('discount_value', 14, 2)->default(0.00)->after('discount_type');
            $table->decimal('discount_nominal', 14, 2)->default(0.00)->after('discount_value');
        });
    }

    public function down()
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_nominal']);
        });
    }
};