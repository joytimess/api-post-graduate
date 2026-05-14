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
        Schema::create('attrition_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('analysis_sessions');
            $table->foreignId('stage_id')->constrained('funnel_stages');
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->decimal('attrition_rate', 5, 2)->default(0.0);
            $table->text('dropoff_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attrittion_analysises');
    }
};
