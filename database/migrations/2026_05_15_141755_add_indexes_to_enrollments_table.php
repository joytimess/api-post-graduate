<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['stage_id', 'status']);
            $table->index(['student_id', 'stage_id']);
            $table->index('enrolled_date');
            $table->index(['stage_id', 'enrolled_date']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['stage_id', 'status']);
            $table->dropIndex(['student_id', 'stage_id']);
            $table->dropIndex(['enrolled_date']);
            $table->dropIndex(['stage_id', 'enrolled_date']);
            $table->dropIndex(['student_id', 'status']);
        });
    }
};