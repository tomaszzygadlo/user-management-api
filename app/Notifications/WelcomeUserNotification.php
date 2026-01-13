<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Welcome email notification sent to new users.
 */
class WelcomeUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly User $user
    ) {
        \Log::info('>>> WelcomeUserNotification CONSTRUCTOR', [
            'user_id' => $user->id,
            'user_name' => $user->first_name . ' ' . $user->last_name,
        ]);

        // Use default queue instead of high
        $this->onQueue('default');

        \Log::info('>>> Notification queued on: default', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        \Log::info('>>> WelcomeUserNotification->via() called', [
            'user_id' => $this->user->id,
            'channels' => ['mail'],
        ]);

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        \Log::info('>>> WelcomeUserNotification->toMail() called', [
            'user_id' => $this->user->id,
            'notifiable' => get_class($notifiable),
        ]);

        try {
            $message = (new MailMessage)
                ->subject('Witamy!')
                ->greeting('Witamy!')
                ->line($this->getWelcomeMessage())
                ->line('Dziękujemy za rejestrację w naszym systemie.')
                ->line('Jeśli masz jakieś pytania, skontaktuj się z nami.')
                ->salutation('Pozdrawiamy,')
                ->salutation('Zespół User Management');

            \Log::info('>>> MailMessage created successfully', [
                'user_id' => $this->user->id,
                'subject' => 'Witamy!',
            ]);

            return $message;
        } catch (\Exception $e) {
            \Log::error('>>> toMail() FAILED', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the welcome message.
     */
    private function getWelcomeMessage(): string
    {
        return sprintf(
            'Witamy użytkownika %s %s',
            $this->user->first_name,
            $this->user->last_name
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->full_name,
            'message' => $this->getWelcomeMessage(),
        ];
    }
}
