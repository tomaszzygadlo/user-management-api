<?php

namespace Database\Seeders;

use App\Models\Email;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create sample users with emails
        $this->createSampleUsers();
    }

    /**
     * Create sample users for testing.
     */
    private function createSampleUsers(): void
    {
        // User 1: Jan Kowalski with 3 emails
        $user1 = User::factory()->create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone_number' => '+48123456789',
        ]);

        Email::factory()->create([
            'user_id' => $user1->id,
            'email' => 'jan.kowalski@example.com',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        Email::factory()->create([
            'user_id' => $user1->id,
            'email' => 'jan.k@work.com',
            'is_primary' => false,
            'verified_at' => now(),
        ]);

        Email::factory()->create([
            'user_id' => $user1->id,
            'email' => 'kowalski.jan@personal.pl',
            'is_primary' => false,
        ]);

        // User 2: Anna Nowak with 2 emails
        $user2 = User::factory()->create([
            'first_name' => 'Anna',
            'last_name' => 'Nowak',
            'phone_number' => '+48987654321',
        ]);

        Email::factory()->create([
            'user_id' => $user2->id,
            'email' => 'anna.nowak@example.com',
            'is_primary' => true,
            'verified_at' => now(),
        ]);

        Email::factory()->create([
            'user_id' => $user2->id,
            'email' => 'a.nowak@company.com',
            'is_primary' => false,
        ]);

        // User 3: Piotr Wiśniewski with 1 email
        $user3 = User::factory()->create([
            'first_name' => 'Piotr',
            'last_name' => 'Wiśniewski',
            'phone_number' => '+48555123456',
        ]);

        Email::factory()->create([
            'user_id' => $user3->id,
            'email' => 'piotr.wisniewski@example.com',
            'is_primary' => true,
        ]);

        // Additional 7 random users
        User::factory()
            ->count(7)
            ->create()
            ->each(function (User $user) {
                // Create 1-3 emails for each user
                $emailCount = rand(1, 3);
                
                Email::factory()
                    ->count($emailCount)
                    ->create([
                        'user_id' => $user->id,
                        'is_primary' => false,
                    ]);

                // Mark first email as primary
                $user->emails()->first()->update(['is_primary' => true]);
            });
    }
}
