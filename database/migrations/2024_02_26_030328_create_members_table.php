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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('nisn')->unique();
            $table->string('name'); 
            $table->enum('gender', ['Laki-laki', 'Perempuan']); 
            $table->string('birthplace'); 
            $table->date('date_of_birth'); 
            $table->string('phone')->unique(); 
            $table->text('address'); 
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
