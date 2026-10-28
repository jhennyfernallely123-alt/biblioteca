<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google' => ['client_id' => 'google-id', 'client_secret' => 'google-secret'],
            'services.facebook' => ['client_id' => 'facebook-id', 'client_secret' => 'facebook-secret'],
        ]);
    }

    public function test_new_social_user_is_created_and_sent_to_complete_nickname(): void
    {
        $this->mockProvider('google', [
            'id' => 'google-123',
            'name' => 'Ana Social',
            'email' => 'ana@example.com',
        ]);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('profile.complete'));
        $this->assertAuthenticated();

        $user = User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertNull($user->nickname);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
        $this->assertDatabaseCount('shelves', 3);
    }

    public function test_existing_email_is_linked_without_duplicating_the_account(): void
    {
        $user = User::factory()->create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'nickname' => 'Ana',
        ]);

        $this->mockProvider('google', [
            'id' => 'google-123',
            'name' => 'Otro Nombre',
            'email' => 'ana@example.com',
        ]);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Ana', $user->fresh()->nickname);
        $this->assertSame('Ana', $user->fresh()->name);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);
    }

    public function test_existing_social_account_logs_in_without_creating_another_user(): void
    {
        $user = User::factory()->create(['nickname' => 'Ana']);
        $user->socialAccounts()->create([
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);

        $this->mockProvider('google', [
            'id' => 'google-123',
            'name' => 'Ana Social',
            'email' => $user->email,
        ]);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);
    }

    public function test_provider_without_email_returns_to_login_with_an_error(): void
    {
        $this->mockProvider('google', [
            'id' => 'google-123',
            'name' => 'Ana Social',
            'email' => null,
        ]);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'El proveedor no nos dio un correo. Usa otro método de acceso.');
        $this->assertGuest();
    }

    public function test_unconfigured_provider_returns_not_found(): void
    {
        config([
            'services.google' => ['client_id' => null, 'client_secret' => null],
        ]);

        $this->get('/auth/google/redirect')->assertNotFound();
    }

    public function test_provider_cancellation_returns_to_login_with_an_error(): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->once()->andThrow(new RuntimeException('denied'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'No pudimos completar el acceso con el proveedor. Inténtalo de nuevo.');
        $this->assertGuest();
    }

    public function test_redirect_route_returns_the_provider_redirect(): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($driver);

        $this->get('/auth/google/redirect')
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    private function mockProvider(string $provider, array $data): void
    {
        $socialiteUser = (new SocialiteUser)->map($data);

        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->once()->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->once()->with($provider)->andReturn($driver);
    }
}
