<?php


namespace App\Notifications\Channels;


use Ghasedak\Exceptions\ApiException;
use Ghasedak\Exceptions\HttpException;
use Illuminate\Notifications\Notification;

class GhasedakChannel
{
    public function send($notifiable , Notification $notification)
    {
        if(! method_exists($notification , 'toGhasedakSms')) {
            throw new \Exception('toGhasedakSms not found');
        }

        $data = $notification->toGhasedakSms($notifiable);

        $message = $data['text'];
        $receptor = $data['phone'];

        $apiKey = config('services.ghasedak.key');

        try
        {
            // $lineNumber = "1008566";
            $lineNumber = config('services.ghasedak.line_number');
            $api = new \Ghasedak\GhasedakApi($apiKey);
            $api->SendSimple($receptor,$message,$lineNumber);
        }
        catch(\Ghasedak\Exceptions\ApiException $e){
            echo $e->errorMessage();
        }
        catch(\Ghasedak\Exceptions\HttpException $e){
            echo $e->errorMessage();
        }
    }
}

