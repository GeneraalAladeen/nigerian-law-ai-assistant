<?php

namespace Tests\Feature\Chat;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StopGenerationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated requests are redirected to the login page.
     */
    public function test_unauthenticated_request_is_redirected_to_login(): void
    {
        $response = $this->post(route('chat.stop'));

        $response->assertRedirect(route('login'));
    }

    /**
     * An authenticated request returns 204 and sets the cache stop flag.
     */
    public function test_authenticated_request_sets_cache_flag_and_returns_no_content(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('chat.stop'));

        $response->assertNoContent();
        $this->assertTrue(Cache::get('chat_stop_'.$user->id));
    }

    /**
     * The stop flag is scoped to the requesting user and does not affect other users.
     */
    public function test_stop_flag_is_scoped_to_the_requesting_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA)->post(route('chat.stop'));

        $this->assertTrue(Cache::get('chat_stop_'.$userA->id));
        $this->assertNull(Cache::get('chat_stop_'.$userB->id));
    }

    /**
     * The stop flag is present immediately after the request.
     */
    public function test_stop_flag_is_present_after_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('chat.stop'));

        $this->assertTrue(Cache::has('chat_stop_'.$user->id));
    }
}
