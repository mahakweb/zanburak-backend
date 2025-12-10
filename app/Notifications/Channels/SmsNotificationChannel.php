<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use SoapClient;
use Illuminate\Support\Facades\Log;

/**
 * کانال اختصاصی ارسال پیامک نوتیفیکیشن با استفاده از الگوی کلی
 * این کانال جدا از کانال کد تایید است و برای ارسال همه نوتیفیکیشن‌ها استفاده می‌شود
 * از یک الگوی کلی در پنل ملی پیامک استفاده می‌کند که پارامترها را دریافت می‌کند
 */
class SmsNotificationChannel
{
    /**
     * Send the given notification via SMS using general template.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toSms')) {
            throw new \Exception('toSms method not found in notification');
        }

        $data = $notification->toSms($notifiable);
        $phone = $notifiable->mobile ?? $data['phone'] ?? null;

        if (!$phone) {
            Log::warning("SmsNotificationChannel: No phone number found for user {$notifiable->id}");
            return;
        }

        try {
            $username = config('services.meliPayamak.username');
            $password = config('services.meliPayamak.password');
            
            if (!$username || !$password) {
                Log::error("SmsNotificationChannel: MeliPayamak credentials not configured");
                return;
            }

            ini_set("soap.wsdl_cache_enabled", "0");
            $sms = new SoapClient(
                "http://api.payamak-panel.com/post/Send.asmx?wsdl",
                array("encoding" => "UTF-8")
            );

            // دریافت body_id از notification
            // body_id باید در toSms() برگردانده شود
            $bodyId = $data['body_id'] ?? null;
            
            if (!$bodyId) {
                Log::error("SmsNotificationChannel: SMS notification template body_id not provided in notification", [
                    'notification' => get_class($notification),
                    'user_id' => $notifiable->id
                ]);
                return;
            }
            
            // دریافت پارامترها از notification
            // پارامترها باید به صورت آرایه باشد که با {0}, {1}, {2} و غیره در الگو جایگزین می‌شوند
            if (!isset($data['params']) || !is_array($data['params'])) {
                Log::warning("SmsNotificationChannel: No params provided in notification", [
                    'notification' => get_class($notification),
                    'user_id' => $notifiable->id
                ]);
                return;
            }
            
            $textParams = $data['params'];
            
            $smsData = array(
                "username" => $username,
                "password" => $password,
                "text" => $textParams,
                "to" => $phone,
                "bodyId" => $bodyId,
            );

            $result = $sms->SendByBaseNumber($smsData)->SendByBaseNumberResult;
            
            Log::info("SmsNotificationChannel: SMS notification sent successfully", [
                'user_id' => $notifiable->id,
                'phone' => $phone,
                'body_id' => $bodyId,
                'params_count' => count($textParams),
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error("SmsNotificationChannel: Error sending SMS notification: " . $e->getMessage(), [
                'user_id' => $notifiable->id,
                'phone' => $phone,
                'body_id' => $bodyId ?? null,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

