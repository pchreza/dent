<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_user_can_login_with_mobile_and_password(): void
    {
        $user = User::factory()->create([
            'mobile' => '09123456789',
            'password' => 'correct horse battery staple',
        ]);

        $response = $this->post('/login', [
            'identifier' => '۰۹۱۲۳۴۵۶۷۸۹',
            'password' => 'correct horse battery staple',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_user_can_login_with_username_and_password(): void
    {
        $user = User::factory()->create([
            'username' => 'superadmin',
            'password' => 'correct horse battery staple',
        ]);

        $response = $this->post('/login', [
            'identifier' => 'SuperAdmin',
            'password' => 'correct horse battery staple',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_validation_uses_persian_required_message(): void
    {
        $response = $this->from('/login')->post('/login', []);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'identifier' => 'واردکردن شمارهٔ موبایل یا نام کاربری الزامی است.',
            'password' => 'واردکردن رمز عبور الزامی است.',
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'mobile' => '09123456789',
            'password' => 'correct horse battery staple',
        ]);

        $response = $this->from('/login')->post('/login', [
            'identifier' => '09123456789',
            'password' => 'correct horse battery staple',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
