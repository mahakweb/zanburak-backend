<?php

namespace App\Notifications\Messenger;

use App\Notifications\Channels\GhasedakChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitation sent to someone who is not yet a member of Zanburak, nudging
 * them to register so they can chat with the inviter.
 */
class InviteToZanburak extends Notification
{
    use Queueable;

    public function __construct(
        public string $inviterName,
        public string $registerUrl,
        public string $channel = 'email', // email | sms
        public ?string $phone = null
    ) {}

    public function via($notifiable): array
    {
        return $this->channel === 'sms' ? [GhasedakChannel::class] : ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('دعوت به زنبورک')
            ->greeting('سلام!')
            ->line("{$this->inviterName} شما را به گفتگو در زنبورک دعوت کرده است.")
            ->line('برای پیوستن و پاسخ دادن، کافیست ثبت‌نام کنید.')
            ->action('ثبت‌نام در زنبورک', $this->registerUrl)
            ->line('اگر این دعوت را انتظار نداشتید، می‌توانید این پیام را نادیده بگیرید.');
    }

    public function toGhasedakSms($notifiable): array
    {
        return [
            'phone' => $this->phone,
            'text' => "{$this->inviterName} شما را به گفتگو در زنبورک دعوت کرد. برای پیوستن ثبت‌نام کنید:\n{$this->registerUrl}",
        ];
    }
}
