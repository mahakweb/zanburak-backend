<?php

namespace App\Notifications\User;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

class RoleChangedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $newRoles;
    public $oldRoles;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Collection $newRoles, Collection $oldRoles = null)
    {
        parent::__construct();
        $this->newRoles = $newRoles;
        $this->oldRoles = $oldRoles;
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'role-changed';
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $rolesText = $this->newRoles->pluck('label')->implode('، ');
        
        $mail = (new MailMessage)
            ->subject('تغییر نقش')
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line("نقش‌های شما به «{$rolesText}» تغییر یافت.");
        
        if ($this->oldRoles && $this->oldRoles->isNotEmpty()) {
            $oldRolesText = $this->oldRoles->pluck('label')->implode('، ');
            $mail->line("نقش‌های قبلی: {$oldRolesText}");
        }
        
        $mail->action('ورود به پنل', frontendUrl('panel'))
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
        $rolesText = $this->newRoles->pluck('label')->implode('، ');
        $message = "نقش‌های شما به «{$rolesText}» تغییر یافت.";
        
        if ($this->oldRoles && $this->oldRoles->isNotEmpty()) {
            $oldRolesText = $this->oldRoles->pluck('label')->implode('، ');
            $message .= " نقش‌های قبلی: {$oldRolesText}";
        }

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
        $rolesText = $this->newRoles->pluck('label')->implode('، ');
        $message = "نقش‌های شما به <span class=\"text-primary font-bold\">{$rolesText}</span> تغییر یافت.";
        
        if ($this->oldRoles && $this->oldRoles->isNotEmpty()) {
            $oldRolesText = $this->oldRoles->pluck('label')->implode('، ');
            $message .= " <span class=\"text-muted\">(قبلی: {$oldRolesText})</span>";
        }

        return [
            'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
            'email' => $notifiable->email,
            'icon-class' => 'info',
            'icon' => '<svg width="18" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" fill="currentColor"></path></svg>',
            'message' => $message,
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
}


