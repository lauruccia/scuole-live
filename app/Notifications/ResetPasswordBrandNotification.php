<?php

namespace App\Notifications;

use App\Models\SchoolSetting;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordBrandNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('Reimposta la password — ' . SchoolSetting::schoolName())
            ->view('emails.reset-password-brand', [
                'url' => $url,
                'notifiable' => $notifiable,
                'expire' => config('auth.passwords.users.expire', 60),
            ]);
    }

    protected function resetUrl($notifiable): string
    {
        // URL reset generato da Laravel (include token + email)
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
