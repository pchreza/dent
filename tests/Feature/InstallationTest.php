<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class InstallationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_installation_page_is_available_before_lock(): void
    {
        $response = $this->get('/install');

        $response->assertOk();
        $response->assertSee('راه‌اندازی Disweb Dental SaaS');
    }

    public function test_installation_creates_system_admin_seeds_access_data_and_locks_route(): void
    {
        $response = $this->post('/install', [
            'product_name' => 'Disweb Dental SaaS',
            'brand_name' => 'Disweb',
            'timezone' => 'Asia/Tehran',
            'admin_name' => 'مدیر سامانه',
            'mobile' => '۰۹۱۲۳۴۵۶۷۸۹',
            'username' => 'superadmin',
            'password' => 'correct horse battery staple',
            'password_confirmation' => 'correct horse battery staple',
        ]);

        $response->assertRedirect('/login');
        $this->assertDatabaseHas('users', [
            'username' => 'superadmin',
            'mobile' => '09123456789',
            'is_system_admin' => true,
        ]);
        $this->assertDatabaseHas('permissions', ['code' => 'patients.view']);
        $this->assertDatabaseHas('roles', ['code' => 'superadmin']);
        Storage::disk('local')->assertExists('installed.lock');

        $this->get('/install')->assertRedirect('/login');
    }

    public function test_installation_rejects_short_admin_password(): void
    {
        $response = $this->from('/install')->post('/install', [
            'product_name' => 'Disweb Dental SaaS',
            'brand_name' => 'Disweb',
            'timezone' => 'Asia/Tehran',
            'admin_name' => 'مدیر سامانه',
            'mobile' => '09123456789',
            'username' => 'superadmin',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertRedirect('/install');
        $response->assertSessionHasErrors('password');
    }
}
