<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index('status');
            $table->index('enrolled_at');
            $table->index(['status', 'enrolled_at']);
            $table->index('program_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['enrolled_at']);
            $table->dropIndex(['status', 'enrolled_at']);
            $table->dropIndex(['program_id']);
        });
    }
};