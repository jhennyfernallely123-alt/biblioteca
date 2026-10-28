<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SocialProvidersTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_providers_are_disabled_without_credentials(): void
    {
        config([
            'services.google' => ['client_id' => null, 'client_secret' => null],
            'services.facebook' => ['client_id' => null, 'client_secret' => null],
        ]);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('socialProviders.google', false)
            ->where('socialProviders.facebook', false));
    }

    public function test_social_providers_are_enabled_with_credentials(): void
    {
        config([
            'services.google' => ['client_id' => 'google-id', 'client_secret' => 'google-secret'],
            'services.facebook' => ['client_id' => 'facebook-id', 'client_secret' => 'facebook-secret'],
        ]);

        $this->get('/login')->assertInertia(fn (Assert $page) => $page
            ->where('socialProviders.google', true)
            ->where('socialProviders.facebook', true));
    }
}
