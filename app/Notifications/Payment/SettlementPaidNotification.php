<?php

namespace App\Notifications\Payment;

use App\Models\Settlement;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class SettlementPaidNotification extends BaseNotification
{
    public function __construct(public Settlement $settlement)
    {
        parent::__construct();
    }

    protected function getEventSlug(): ?string
    {
        return 'settlement-paid';
    }

    public function toMail($notifiable)
    {
        $amount = number_format((int) $this->settlement->amount);
        $when = optional($this->settlement->paid_at)->format('Y-m-d H:i');

        return (new MailMessage)
            ->subject('تسویه جدید انجام شد')
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام '.($notifiable->first_name ?? 'مدرس').' عزیز!')
            ->line('تسویه سهم شما ثبت شد.')
            ->line('مبلغ: '.$amount.' تومان')
            ->line('شماره تسویه: #'.$this->settlement->id)
            ->line('تاریخ: '.($when ?: '—'));
    }

    public function toArray($notifiable): array
    {
        $amount = number_format((int) $this->settlement->amount);

        return [
            'name' => trim(($notifiable->first_name ?? '').' '.($notifiable->last_name ?? '')),
            'email' => $notifiable->email,
            'icon-class' => 'success',
            'icon' => '<svg width="18" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 7H20V17H4V7Z" stroke="currentColor" stroke-width="1.5"/><path d="M4 10H20" stroke="currentColor" stroke-width="1.5"/></svg>',
            'message' => 'تسویه جدید انجام شد. مبلغ '.$amount.' تومان. شماره تسویه #'.$this->settlement->id.'.',
            'settlement_id' => $this->settlement->id,
            'settlement_uuid' => $this->settlement->uuid,
            'amount' => (int) $this->settlement->amount,
            'paid_at' => optional($this->settlement->paid_at)->toIso8601String(),
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
};
