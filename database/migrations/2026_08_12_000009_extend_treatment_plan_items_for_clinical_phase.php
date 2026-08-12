<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_plan_items', function (Blueprint $table): void {
            $table->string('surface_code', 4)->nullable()->after('tooth_code');
            $table->index(['tenant_id', 'tooth_code', 'surface_code'], 'treatment_item_tooth_lookup');
        });

        Schema::create('treatment_plan_item_status_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('treatment_plan_item_id')->constrained('treatment_plan_items')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'treatment_plan_item_id', 'id'], 'treatment_item_status_history_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_plan_item_status_history');

        Schema::table('treatment_plan_items', function (Blueprint $table): void {
            $table->dropIndex('treatment_item_tooth_lookup');
            $table->dropColumn('surface_code');
        });
    }
};
