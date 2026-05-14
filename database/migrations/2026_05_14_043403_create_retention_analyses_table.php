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
        Schema::create('retention_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('analysis_sessions');
            $table->decimal('retention_rate', 5, 2)->default(0.0);
            $table->unsignedInteger('active_students')->default(0);
            $table->unsignedInteger('inactive_students')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retention_analyses');
    }
};
