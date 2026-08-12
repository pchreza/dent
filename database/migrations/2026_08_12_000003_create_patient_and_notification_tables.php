<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('patient_no', 60);
            $table->string('first_name', 100);
            $table->string('last_name', 120);
            $table->string('national_id', 20);
            $table->date('birth_date')->nullable();
            $table->string('gender', 32)->nullable();
            $table->string('mobile', 20);
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('insurance_name', 160)->nullable();
            $table->json('emergency_contact')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'patient_no']);
            $table->unique(['tenant_id', 'national_id']);
            $table->unique(['tenant_id', 'mobile']);
            $table->index(['tenant_id', 'status', 'last_name', 'first_name']);
        });

        Schema::create('medical_condition_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('code', 80);
            $table->string('name', 180);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('patient_medical_conditions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('condition_id')->constrained('medical_condition_definitions')->cascadeOnDelete();
            $table->string('value', 120)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'patient_id']);
        });

        Schema::create('patient_allergies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('substance_name', 180);
            $table->string('reaction', 240)->nullable();
            $table->string('severity', 32)->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_critical')->default(false)->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'patient_id', 'is_critical']);
        });

        Schema::create('patient_medications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('name', 180);
            $table->string('dosage', 120)->nullable();
            $table->string('frequency', 120)->nullable();
            $table->string('duration', 120)->nullable();
            $table->text('instruction')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('patient_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visibility', 20)->default('public')->index();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('qr_registration_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('token_hash', 128)->index();
            $table->json('payload');
            $table->json('duplicate_match')->nullable();
            $table->string('status', 40)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'created_at']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 80);
            $table->string('title', 180);
            $table->text('body');
            $table->string('status', 32)->default('unread')->index();
            $table->string('action_url', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'recipient_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('qr_registration_requests');
        Schema::dropIfExists('patient_notes');
        Schema::dropIfExists('patient_medications');
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('patient_medical_conditions');
        Schema::dropIfExists('medical_condition_definitions');
        Schema::dropIfExists('patients');
    }
};
