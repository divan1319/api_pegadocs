<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_PASSWORD = 'Abcd1234!@xy';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /**
     * Cabeceras mínimas para que Sanctum trate la petición como SPA stateful (sesión + CSRF en producción).
     *
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function spaHeaders(array $headers = []): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        return array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Referer' => $baseUrl.'/',
        ], $headers);
    }

    public function test_user_requires_authentication(): void
    {
        $response = $this->withHeaders($this->spaHeaders())
            ->getJson('/api/v1/user');

        $response->assertUnauthorized();
    }

    public function test_register_creates_session_and_returns_user(): void
    {
        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/register', [
            'name' => 'Usuario Prueba',
            'email' => 'nuevo@example.com',
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'nuevo@example.com');

        $this->assertAuthenticatedAs(User::query()->where('email', 'nuevo@example.com')->first());
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'existente@example.com',
        ]);

        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'existente@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'existente@example.com',
        ]);

        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'existente@example.com',
            'password' => 'incorrecta',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders($this->spaHeaders())
            ->getJson('/api/v1/user');

        $response->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_succeeds_when_authenticated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeaders($this->spaHeaders())
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();
    }
}
