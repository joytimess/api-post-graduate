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
        Schema::create('funnel_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('analysis_sessions');
            $table->foreignId('stage_id')->constrained('funnel_stages');
            $table->unsignedInteger('total_prospects')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0.00);
            $table->decimal('dropoff_rate', 5, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funnel_entries');
    }
};
