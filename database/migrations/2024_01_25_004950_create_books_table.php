<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 20);
            $table->text('synopsis')->nullable();
            $table->string('isbn', 30);
            $table->string('writer', 50);
            $table->integer('page_amount');
            $table->integer('stock_amount');
            $table->year('published');
            $table->string('category')->nullable();
            $table->string('image');
            $table->enum('status', ['available', 'loaned', 'returned'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
