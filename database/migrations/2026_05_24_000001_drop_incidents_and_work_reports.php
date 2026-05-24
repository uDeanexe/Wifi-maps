<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('work_reports');
        Schema::dropIfExists('incidents');
    }

    public function down(): void
    {
        Schema::create('incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('node_id')->nullable()->constrained('nodes')->nullOnDelete();
            $table->string('category')->default('kerusakan');
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
            $table->text('description')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }
};

