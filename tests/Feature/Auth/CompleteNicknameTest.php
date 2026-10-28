<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompleteNicknameTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/complete-profile')->assertRedirect(route('login'));
    }

    public function test_user_without_nickname_sees_the_page(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->get('/complete-profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/CompleteNickname', false));
    }

    public function test_user_with_nickname_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create(['nickname' => 'Ana']);

        $this->actingAs($user)
            ->get('/complete-profile')
            ->assertRedirect(route('dashboard'));
    }

    public function test_nickname_is_required(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->post('/complete-profile', ['nickname' => ''])
            ->assertSessionHasErrors('nickname');
    }

    public function test_nickname_cannot_exceed_fifty_characters(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->post('/complete-profile', ['nickname' => str_repeat('a', 51)])
            ->assertSessionHasErrors('nickname');
    }

    public function test_user_can_complete_nickname(): void
    {
        $user = User::factory()->create(['nickname' => null]);

        $this->actingAs($user)
            ->post('/complete-profile', ['nickname' => 'Luna'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('Luna', $user->fresh()->nickname);
    }
}
