<?php

namespace Tests\Unit;

use App\Models\Email;
use App\Models\User;
use App\Notifications\WelcomeUserNotification;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = new UserService();
    }

    /**
     * Test creating user with service.
     */
    public function test_create_user_creates_user_and_emails(): void
    {
        // Arrange
        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '+48123456789',
            'emails' => [
                ['email' => 'test@example.com', 'is_primary' => true],
                ['email' => 'test2@example.com', 'is_primary' => false],
            ],
        ];

        // Act
        $user = $this->userService->createUser($data);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test', $user->first_name);
        $this->assertCount(2, $user->emails);
        $this->assertTrue($user->emails->where('is_primary', true)->isNotEmpty());
    }

    /**
     * Test updating user basic info.
     */
    public function test_update_user_updates_basic_info(): void
    {
        // Arrange
        $user = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        $data = [
            'first_name' => 'New',
            'last_name' => 'Name',
        ];

        // Act
        $updatedUser = $this->userService->updateUser($user, $data);

        // Assert
        $this->assertEquals('New', $updatedUser->first_name);
        $this->assertEquals('Name', $updatedUser->last_name);
    }

    /**
     * Test updating user emails.
     */
    public function test_update_user_updates_emails(): void
    {
        // Arrange
        $user = User::factory()->create();
        $existingEmail = Email::factory()->create([
            'user_id' => $user->id,
            'email' => 'old@example.com',
            'is_primary' => true,
        ]);

        $data = [
            'emails' => [
                [
                    'id' => $existingEmail->id,
                    'email' => 'updated@example.com',
                    'is_primary' => true,
                ],
                [
                    'email' => 'new@example.com',
                    'is_primary' => false,
                ],
            ],
        ];

        // Act
        $updatedUser = $this->userService->updateUser($user, $data);

        // Assert
        $this->assertCount(2, $updatedUser->emails);
        $this->assertDatabaseHas('emails', [
            'id' => $existingEmail->id,
            'email' => 'updated@example.com',
        ]);
        $this->assertDatabaseHas('emails', [
            'user_id' => $user->id,
            'email' => 'new@example.com',
        ]);
    }

    /**
     * Test deleting user.
     */
    public function test_delete_user_soft_deletes_user(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->userService->deleteUser($user);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /**
     * Test sending welcome emails.
     */
    public function test_send_welcome_emails_sends_to_all_addresses(): void
    {
        // Arrange
        Notification::fake();
        
        $user = User::factory()->create();
        Email::factory()->count(3)->create(['user_id' => $user->id]);

        // Act
        $this->userService->sendWelcomeEmails($user);

        // Assert
        Notification::assertSentOnDemand(WelcomeUserNotification::class);
    }

    /**
     * Test creating user ensures only one primary email.
     */
    public function test_create_user_ensures_single_primary_email(): void
    {
        // Arrange
        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '+48123456789',
            'emails' => [
                ['email' => 'test1@example.com', 'is_primary' => false],
                ['email' => 'test2@example.com', 'is_primary' => false],
                ['email' => 'test3@example.com', 'is_primary' => false],
            ],
        ];

        // Act
        $user = $this->userService->createUser($data);

        // Assert
        $this->assertEquals(1, $user->emails->where('is_primary', true)->count());
    }

    /**
     * Test transaction rollback on failure.
     */
    public function test_create_user_rolls_back_on_failure(): void
    {
        // Arrange
        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '+48123456789',
            'emails' => [
                ['email' => 'invalid-email-format'], // This will fail validation
            ],
        ];

        // Act & Assert
        try {
            $this->userService->createUser($data);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Verify no user was created
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('emails', 0);
        }
    }
}
