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
        Schema::create('dropoff_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->enum('category', [
                'academic',
                'financial', 
                'personal',
                'administrative',
                'unknown'
            ])->default('unknown');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dropoff_reasons');
    }
};
