<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected User $invitedUser,
        protected string $panelUrl
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = url(route('filament.admin.auth.invitation', [
            'token' => $this->invitedUser->invitation_token,
        ]));

        return (new MailMessage)
            ->subject('You have been invited to ' . config('app.name'))
            ->greeting("Hello {$this->invitedUser->name}!")
            ->line('You have been invited to access ' . config('app.name') . '.')
            ->line('Click the button below to accept your invitation and set up your account.')
            ->action('Accept Invitation', $acceptUrl)
            ->line('This invitation link will expire in 7 days.')
            ->line('If you did not expect this invitation, no action is required.')
            ->salutation('Regards, ' . config('app.name') . ' Team');
    }
}
