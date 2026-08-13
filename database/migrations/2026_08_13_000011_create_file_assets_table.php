<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('owner_type', 120);
            $table->unsignedBigInteger('owner_id');
            $table->string('category', 40);
            $table->string('disk', 40)->default('local');
            $table->string('path', 500);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->json('metadata_json')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->nullable()->index();
            $table->timestamps();
            $table->index(['tenant_id', 'owner_type', 'owner_id', 'deleted_at']);
            $table->index(['tenant_id', 'category', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_assets');
    }
};
