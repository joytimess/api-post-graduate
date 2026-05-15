<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('traffic_source_id')
                  ->nullable()
                  ->after('program_id')
                  ->constrained('traffic_sources')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['traffic_source_id']);
            $table->dropColumn('traffic_source_id');
        });
    }
};