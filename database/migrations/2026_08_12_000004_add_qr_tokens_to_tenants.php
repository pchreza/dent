<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('qr_token_hash', 128)->nullable()->unique()->after('branding');
            $table->text('qr_token_encrypted')->nullable()->after('qr_token_hash');
        });

        DB::table('tenants')->select('id')->orderBy('id')->each(function (object $tenant): void {
            $token = Str::random(64);
            DB::table('tenants')->where('id', $tenant->id)->update([
                'qr_token_hash' => hash('sha256', $token),
                'qr_token_encrypted' => Crypt::encryptString($token),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropUnique(['qr_token_hash']);
            $table->dropColumn(['qr_token_hash', 'qr_token_encrypted']);
        });
    }
};
