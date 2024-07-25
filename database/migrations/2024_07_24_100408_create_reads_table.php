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
        Schema::create('reads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('writer');
            $table->integer('page_amount');
            $table->year('published');
            $table->string('publisher');
            $table->enum('category', ['Buku Fiksi','Buku Pengetahuan (Non paket)','Kamus','Ensiklopedia','Al-Quran tafsir','Buku Paket','Cerpen'    ])->nullable();
            $table->string('image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reads');
    }
};
