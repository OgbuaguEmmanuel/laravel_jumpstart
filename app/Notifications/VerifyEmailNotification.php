<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    /**
     * The callback that should be used to build the mail message.
     */
    public string $callbackUrl;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->greeting("Dear {$notifiable->full_name},")
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not create an account, no further action is required.');
    }

    public function toDatabase($notifiable): array
    {
        return $this->formatData(
            'Verify Email Address',
            'Please verify your email address by clicking the link we sent you.',
            [
                'verification_url' => $this->verificationUrl($notifiable),
                'callbackUrl' => $this->callbackUrl,
            ]
        );
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        $signedRoute = URL::temporarySignedRoute(
            'auth.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        // Pull down the signed route for restructuring with the callbackUrl
        $parsedUrl = parse_url($signedRoute);
        parse_str($parsedUrl['query'], $urlQueries);

        // Build the query parameters
        $parameters = http_build_query([
            'expires' => $urlQueries['expires'],
            'hash' => $urlQueries['hash'],
            'id' => $urlQueries['id'],
            'signature' => $urlQueries['signature'],
        ]);

        return "{$this->callbackUrl}?{$parameters}";
    }
}
