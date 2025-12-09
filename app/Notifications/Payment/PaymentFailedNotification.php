<?php

namespace App\Notifications\Payment;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentFailedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $amount;
    public $reason;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(int $amount, string $reason = null)
    {
        parent::__construct();
        $this->amount = $amount;
        $this->reason = $reason;
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'payment-failed';
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('پرداخت ناموفق')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line("پرداخت شما به مبلغ " . number_format($this->amount) . " تومان ناموفق بود.");
        
        if ($this->reason) {
            $mail->line("دلیل: {$this->reason}");
        }
        
        $mail->line('لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.')
            ->action('تلاش مجدد', frontendUrl('cart'))
            ->line('با تشکر از شما');

        return $mail;
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toSms($notifiable)
    {
        $message = "پرداخت شما به مبلغ " . number_format($this->amount) . " تومان ناموفق بود.";
        
        if ($this->reason) {
            $message .= " دلیل: {$this->reason}";
        }
        
        $message .= " لطفاً دوباره تلاش کنید.";

        return [
            'message' => $message . ' زنبورک',
            'phone' => $notifiable->mobile,
            'body_id' => config('services.meliPayamak.notification_template_id', null),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $message = "پرداخت شما به مبلغ <span class=\"text-primary font-bold\">" . number_format($this->amount) . " تومان</span> ناموفق بود.";
        
        if ($this->reason) {
            $message .= " <span class=\"text-warning\">دلیل: {$this->reason}</span>";
        }

        return [
            'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
            'email' => $notifiable->email,
            'icon-class' => 'error',
            'icon' => '<svg width="18" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" fill="currentColor"></path></svg>',
            'message' => $message,
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
}


