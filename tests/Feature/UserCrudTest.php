<?php

namespace Tests\Feature;

use App\Models\Email;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test listing users with pagination.
     */
    public function test_can_list_users(): void
    {
        // Arrange
        User::factory()
            ->count(5)
            ->has(Email::factory()->count(2))
            ->create();

        // Act
        $response = $this->getJson('/api/users');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'first_name',
                        'last_name',
                        'full_name',
                        'phone_number',
                        'emails',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                ],
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test searching users.
     */
    public function test_can_search_users(): void
    {
        // Arrange
        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        // Act
        $response = $this->getJson('/api/users?search=John');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('John', $response->json('data.0.first_name'));
    }

    /**
     * Test creating a user with emails.
     */
    public function test_can_create_user_with_emails(): void
    {
        // Arrange
        $userData = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone_number' => '+48123456789',
            'emails' => [
                ['email' => 'jan@example.com', 'is_primary' => true],
                ['email' => 'jan.work@example.com', 'is_primary' => false],
            ],
        ];

        // Act
        $response = $this->postJson('/api/users', $userData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'full_name',
                    'phone_number',
                    'emails',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
        ]);

        $this->assertDatabaseHas('emails', [
            'email' => 'jan@example.com',
            'is_primary' => true,
        ]);

        $this->assertDatabaseCount('emails', 2);
    }

    /**
     * Test validation fails when creating user without required fields.
     */
    public function test_cannot_create_user_without_required_fields(): void
    {
        // Act
        $response = $this->postJson('/api/users', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'phone_number', 'emails']);
    }

    /**
     * Test validation fails when creating user with invalid email.
     */
    public function test_cannot_create_user_with_invalid_email(): void
    {
        // Arrange
        $userData = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone_number' => '+48123456789',
            'emails' => [
                ['email' => 'invalid-email'],
            ],
        ];

        // Act
        $response = $this->postJson('/api/users', $userData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['emails.0.email']);
    }

    /**
     * Test showing a specific user.
     */
    public function test_can_show_user(): void
    {
        // Arrange
        $user = User::factory()
            ->has(Email::factory()->count(2))
            ->create();

        // Act
        $response = $this->getJson("/api/users/{$user->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ],
            ]);

        $this->assertCount(2, $response->json('data.emails'));
    }

    /**
     * Test showing non-existent user returns 404.
     */
    public function test_showing_non_existent_user_returns_404(): void
    {
        // Act
        $response = $this->getJson('/api/users/99999');

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test updating a user.
     */
    public function test_can_update_user(): void
    {
        // Arrange
        $user = User::factory()
            ->has(Email::factory()->count(2))
            ->create();

        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ];

        // Act
        $response = $this->putJson("/api/users/{$user->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'first_name' => 'Updated',
                    'last_name' => 'Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    /**
     * Test updating user emails.
     */
    public function test_can_update_user_emails(): void
    {
        // Arrange
        $user = User::factory()->create();
        $email = Email::factory()->create([
            'user_id' => $user->id,
            'email' => 'old@example.com',
            'is_primary' => true,
        ]);

        $updateData = [
            'emails' => [
                [
                    'id' => $email->id,
                    'email' => 'new@example.com',
                    'is_primary' => true,
                ],
                [
                    'email' => 'additional@example.com',
                    'is_primary' => false,
                ],
            ],
        ];

        // Act
        $response = $this->putJson("/api/users/{$user->id}", $updateData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('emails', [
            'id' => $email->id,
            'email' => 'new@example.com',
        ]);

        $this->assertDatabaseHas('emails', [
            'user_id' => $user->id,
            'email' => 'additional@example.com',
        ]);
    }

    /**
     * Test deleting a user.
     */
    public function test_can_delete_user(): void
    {
        // Arrange
        $user = User::factory()
            ->has(Email::factory()->count(2))
            ->create();

        // Act
        $response = $this->deleteJson("/api/users/{$user->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /**
     * Test deleting user cascades to emails.
     */
    public function test_deleting_user_cascades_to_emails(): void
    {
        // Arrange
        $user = User::factory()->create();
        $email1 = Email::factory()->create(['user_id' => $user->id]);
        $email2 = Email::factory()->create(['user_id' => $user->id]);

        // Act
        $this->deleteJson("/api/users/{$user->id}");

        // Assert
        $this->assertDatabaseMissing('emails', ['id' => $email1->id]);
        $this->assertDatabaseMissing('emails', ['id' => $email2->id]);
    }
}
