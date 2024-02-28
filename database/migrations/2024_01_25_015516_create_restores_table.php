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
            $table->dateTime('returndate');
            $table->enum('status', ['pending', 'accepted', 'overdue'])->default('pending');
            $table->float('fine')->nullable();
            $table->foreignId('book_id')->references('id')->on('books')->cascadeOnDelete();
            $table->foreignId('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('borrow_id')->references('id')->on('borrows')->cascadeOnDelete();
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
