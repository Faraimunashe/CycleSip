<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $code,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your CycleSip email')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Use the verification code below to confirm your email address.')
            ->line('**'.$this->code.'**')
            ->line('This code expires in 15 minutes.')
            ->line('If you did not create an account, no action is required.');
    }
}
