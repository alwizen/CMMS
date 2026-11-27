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

    public function __construct(public User $user, public string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = (int) config('auth.invitation_expiry_days', 7);

        return (new MailMessage)
            ->subject('Undangan akun '.config('app.name'))
            ->greeting('Halo,')
            ->line('Anda diundang untuk bergabung ke '.config('app.name').'.')
            ->line('Klik tombol di bawah untuk mengaktifkan akun dan membuat password Anda.')
            ->action('Aktifkan Akun', $this->url)
            ->line("Tautan ini berlaku selama {$days} hari dan hanya bisa dipakai sekali.")
            ->line('Abaikan email ini bila Anda merasa tidak diundang.');
    }
}
