<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_stage_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('code', 80);
            $table->string('name', 180);
            $table->string('category', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('treatment_catalog', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name', 180);
            $table->string('category', 120)->nullable();
            $table->unsignedInteger('default_duration_minutes')->nullable();
            $table->decimal('default_price', 14, 2)->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('treatment_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('status', 40)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->date('started_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->decimal('estimated_total', 14, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'patient_id', 'status']);
        });

        Schema::create('treatment_plan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('treatment_plan_id')->constrained('treatment_plans')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('treatment_stage_definitions')->restrictOnDelete();
            $table->foreignId('treatment_id')->nullable()->constrained('treatment_catalog')->nullOnDelete();
            $table->string('tooth_code', 30)->nullable();
            $table->string('status', 40)->default('planned')->index();
            $table->string('priority', 30)->default('normal');
            $table->decimal('estimated_cost', 14, 2)->nullable();
            $table->date('planned_on')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'treatment_plan_id', 'status']);
        });

        Schema::create('working_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            $table->unique(['tenant_id', 'branch_id', 'day_of_week']);
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('practitioner_id')->nullable()->constrained('practitioners')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('title', 200);
            $table->string('appointment_type', 80)->nullable();
            $table->string('status', 40)->default('scheduled')->index();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'starts_at', 'status']);
            $table->index(['tenant_id', 'patient_id', 'starts_at']);
        });

        Schema::create('appointment_status_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_status_history');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('working_hours');
        Schema::dropIfExists('treatment_plan_items');
        Schema::dropIfExists('treatment_plans');
        Schema::dropIfExists('treatment_catalog');
        Schema::dropIfExists('treatment_stage_definitions');
    }
};
