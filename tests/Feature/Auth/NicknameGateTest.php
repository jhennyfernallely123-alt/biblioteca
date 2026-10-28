<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NicknameGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_nickname_cannot_open_the_library(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('profile.complete'));
    }

    public function test_user_without_nickname_cannot_open_settings(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->get('/settings')
            ->assertRedirect(route('profile.complete'));
    }

    public function test_user_without_nickname_can_open_complete_profile(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->get('/complete-profile')
            ->assertOk();
    }

    public function test_user_without_nickname_can_logout(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
