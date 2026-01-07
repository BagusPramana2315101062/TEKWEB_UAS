<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->unsignedBigInteger('commentable_id');
            $table->string('commentable_type');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('content');
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id']);
            $table->index(['parent_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('comments');
    }
};