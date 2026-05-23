<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('nodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('node_type_id')->constrained('node_types')->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->text('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->integer('topology_x')->default(100);
            $table->integer('topology_y')->default(100);
            $table->timestamps();
        });

        Schema::create('links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_node_id')->constrained('nodes')->cascadeOnDelete();
            $table->foreignId('target_node_id')->constrained('nodes')->cascadeOnDelete();
            $table->string('cable_type')->nullable();
            $table->integer('core_count')->nullable();
            $table->string('core_number')->nullable();
            $table->string('pon_name')->nullable();
            $table->string('odc_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['source_node_id', 'target_node_id']);
        });

        Schema::create('photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('node_id')->constrained('nodes')->cascadeOnDelete();
            $table->string('file_path');
            $table->text('caption')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('node_id')->nullable()->constrained('nodes')->nullOnDelete();
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_contact')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('noc_admin_name')->nullable();
            $table->string('technician_name')->nullable();
            $table->string('technician_contact')->nullable();
            $table->string('technician_email')->nullable();
            $table->text('work_order_notes')->nullable();
            $table->text('technician_report')->nullable();
            $table->string('status')->default('reported');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('work_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incident_id')->nullable()->constrained('incidents')->nullOnDelete();
            $table->foreignId('node_id')->nullable()->constrained('nodes')->nullOnDelete();
            $table->string('technician_name')->nullable();
            $table->string('report_title');
            $table->text('description');
            $table->string('photo_path')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_reports');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('photos');
        Schema::dropIfExists('links');
        Schema::dropIfExists('nodes');
        Schema::dropIfExists('node_types');
    }
};
