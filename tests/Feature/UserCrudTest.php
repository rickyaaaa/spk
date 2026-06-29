<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_index_requires_authentication(): void
    {
        $response = $this->get(route('users.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_user_list(): void
    {
        $user = User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        $otherUser = User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertSee('johndoe');
        $response->assertSee('Jane Smith');
        $response->assertSee('janesmith');
    }

    public function test_can_create_user(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Guru Satu',
            'username' => 'guru1',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Guru Satu',
            'username' => 'guru1',
        ]);

        $createdUser = User::query()->where('username', 'guru1')->firstOrFail();
        $this->assertTrue(Hash::check('secret123', $createdUser->password));
    }

    public function test_can_update_user_without_password(): void
    {
        $admin = User::factory()->create();
        $targetUser = User::factory()->create([
            'name' => 'Old Name',
            'username' => 'olduser',
            'password' => Hash::make('original_password'),
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $targetUser), [
            'name' => 'New Name',
            'username' => 'newuser',
            'password' => '', // blank password should not change it
        ]);

        $response->assertRedirect(route('users.index'));
        $targetUser->refresh();

        $this->assertEquals('New Name', $targetUser->name);
        $this->assertEquals('newuser', $targetUser->username);
        $this->assertTrue(Hash::check('original_password', $targetUser->password));
    }

    public function test_can_update_user_with_new_password(): void
    {
        $admin = User::factory()->create();
        $targetUser = User::factory()->create([
            'password' => Hash::make('old_password'),
        ]);

        $response = $this->actingAs($admin)->put(route('users.update', $targetUser), [
            'name' => 'Updated Name',
            'username' => 'updateduser',
            'password' => 'new_secret_password',
        ]);

        $response->assertRedirect(route('users.index'));
        $targetUser->refresh();

        $this->assertTrue(Hash::check('new_secret_password', $targetUser->password));
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_user_can_delete_other_user(): void
    {
        $admin = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $otherUser));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $otherUser->id]);
    }
}
