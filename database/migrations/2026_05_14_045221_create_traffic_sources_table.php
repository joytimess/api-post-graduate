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
        Schema::create('traffic_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', ['social_media', 'search', 'event', 'referral', 'direct']);
            $table->decimal('cost_per_lead', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic_sources');
    }
};
