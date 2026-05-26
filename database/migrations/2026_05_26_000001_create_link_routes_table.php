<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_routes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('link_id')->constrained('links')->cascadeOnDelete();
            $table->string('provider')->default('osrm');
            $table->json('geometry')->nullable();
            $table->double('distance_meters')->nullable();
            $table->double('duration_seconds')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('link_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_routes');
    }
};

