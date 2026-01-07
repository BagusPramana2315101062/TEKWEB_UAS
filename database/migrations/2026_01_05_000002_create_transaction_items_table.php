<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionItemsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('qty')->unsigned();
            $table->decimal('price', 14, 2)->unsigned();
            $table->decimal('line_total', 14, 2)->unsigned();
            $table->timestamps();

            $table->index(['product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_items');
    }
}
