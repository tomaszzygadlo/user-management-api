<?php

namespace App\Services;

use App\Models\Email;
use App\Models\User;
use App\Notifications\WelcomeUserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class UserService
{
    /**
     * Create a new user with emails.
     *
     * @param array<string, mixed> $data
     * @throws \Exception
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create user
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone_number' => $data['phone_number'],
            ]);

            // Create emails
            $this->syncEmails($user, $data['emails']);

            Log::info("User created successfully", [
                'user_id' => $user->id,
                'email_count' => count($data['emails']),
            ]);

            return $user->load('emails');
        });
    }

    /**
     * Update an existing user.
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @throws \Exception
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Update user basic info
            $user->update(array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
            ]));

            // Update emails if provided
            if (isset($data['emails'])) {
                $this->updateEmails($user, $data['emails']);
            }

            Log::info("User updated successfully", [
                'user_id' => $user->id,
            ]);

            return $user->fresh(['emails']);
        });
    }

    /**
     * Delete a user (soft delete).
     *
     * @throws \Exception
     */
    public function deleteUser(User $user): bool
    {
        try {
            $userId = $user->id;
            $deleted = $user->delete();

            if ($deleted) {
                Log::info("User deleted successfully", [
                    'user_id' => $userId,
                ]);
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error("Failed to delete user", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send welcome emails to all user's email addresses.
     */
    public function sendWelcomeEmails(User $user): void
    {
        $user->load('emails');

        if ($user->emails->isEmpty()) {
            Log::warning("Cannot send welcome emails: user has no email addresses", [
                'user_id' => $user->id,
            ]);
            return;
        }

        // Send notification to all email addresses
        $emailAddresses = $user->emails->pluck('email')->toArray();
        
        Notification::route('mail', $emailAddresses)
            ->notify(new WelcomeUserNotification($user));

        Log::info("Welcome emails queued", [
            'user_id' => $user->id,
            'email_count' => count($emailAddresses),
            'emails' => $emailAddresses,
        ]);
    }

    /**
     * Sync emails for a user (used during creation).
     *
     * @param array<int, array<string, mixed>> $emailsData
     */
    private function syncEmails(User $user, array $emailsData): void
    {
        foreach ($emailsData as $emailData) {
            Email::create([
                'user_id' => $user->id,
                'email' => $emailData['email'],
                'is_primary' => $emailData['is_primary'] ?? false,
            ]);
        }
    }

    /**
     * Update emails for a user (used during update).
     *
     * @param array<int, array<string, mixed>> $emailsData
     */
    private function updateEmails(User $user, array $emailsData): void
    {
        $emailIds = [];

        foreach ($emailsData as $emailData) {
            // Check if email should be deleted
            if (isset($emailData['delete']) && $emailData['delete'] === true) {
                if (isset($emailData['id'])) {
                    Email::where('id', $emailData['id'])
                        ->where('user_id', $user->id)
                        ->delete();
                }
                continue;
            }

            // Update existing or create new email
            if (isset($emailData['id'])) {
                $email = Email::where('id', $emailData['id'])
                    ->where('user_id', $user->id)
                    ->first();

                if ($email) {
                    $email->update([
                        'email' => $emailData['email'],
                        'is_primary' => $emailData['is_primary'] ?? false,
                    ]);
                    $emailIds[] = $email->id;
                }
            } else {
                // Create new email
                $email = Email::create([
                    'user_id' => $user->id,
                    'email' => $emailData['email'],
                    'is_primary' => $emailData['is_primary'] ?? false,
                ]);
                $emailIds[] = $email->id;
            }
        }

        // Ensure only one primary email
        $this->ensureSinglePrimaryEmail($user);
    }

    /**
     * Ensure that only one email is marked as primary.
     */
    private function ensureSinglePrimaryEmail(User $user): void
    {
        $primaryEmails = $user->emails()->where('is_primary', true)->get();

        if ($primaryEmails->count() > 1) {
            // Keep the first primary, unmark others
            $primaryEmails->skip(1)->each(function (Email $email) {
                $email->update(['is_primary' => false]);
            });
        } elseif ($primaryEmails->count() === 0 && $user->emails()->count() > 0) {
            // If no primary, mark the first email as primary
            $user->emails()->first()->update(['is_primary' => true]);
        }
    }
}
