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
        Schema::create('restores', function (Blueprint $table) {
            $table->id();
            $table->date('returndate');
            $table->float('fine')->nullable();
            $table->foreignId('book_id')->references('id')->on('books')->cascadeOnDelete();
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('borrow_id')->references('id')->on('borrows')->cascadeOnDelete();
            $table->enum('status', ['Menunggu', 'Dikembalikan', 'Denda'])->default('Menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restores');
    }
};
