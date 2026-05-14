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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('program_id')->constrained('programs');
            $table->foreignId('stage_id')->constrained('funnel_stages');
            $table->enum('status', ['ongoing','passed','failed','dropout'])
                ->default('ongoing');
            $table->foreignId('dropoff_reason_id')
                ->nullable()
                ->constrained('dropoff_reasons')
                ->nullOnDelete();
            $table->date('enrolled_date');
            $table->date('completed_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index untuk mempercepat query analisis
            $table->index(['program_id', 'stage_id', 'status']);
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
