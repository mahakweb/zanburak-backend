<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Melipayamak\MelipayamakApi;
use SoapClient;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Send the given notification via SMS.
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
        $message = $data['message'] ?? $data;
        $phone = $notifiable->mobile ?? $data['phone'] ?? null;

        if (!$phone) {
            Log::warning("No phone number found for user {$notifiable->id}");
            return;
        }

        try {
            $username = config('services.meliPayamak.username');
            $password = config('services.meliPayamak.password');
            
            if (!$username || !$password) {
                Log::error("MeliPayamak credentials not configured");
                return;
            }

            ini_set("soap.wsdl_cache_enabled", "0");
            $sms = new SoapClient(
                "http://api.payamak-panel.com/post/Send.asmx?wsdl",
                array("encoding" => "UTF-8")
            );

            // استفاده از الگوی پیامک که باید در پنل MeliPayamak ایجاد شود
            // bodyId باید در config یا notification تنظیم شود
            $bodyId = $data['body_id'] ?? config('services.meliPayamak.notification_template_id', '372965');
            
            $smsData = array(
                "username" => $username,
                "password" => $password,
                "text" => array($message),
                "to" => $phone,
                "bodyId" => $bodyId,
            );

            $result = $sms->SendByBaseNumber($smsData)->SendByBaseNumberResult;
            
            Log::info("SMS sent successfully", [
                'user_id' => $notifiable->id,
                'phone' => $phone,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending SMS: " . $e->getMessage(), [
                'user_id' => $notifiable->id,
                'phone' => $phone,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

