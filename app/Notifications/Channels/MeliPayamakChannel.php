<?php


namespace App\Notifications\Channels;


use Ghasedak\Exceptions\ApiException;
use Ghasedak\Exceptions\HttpException;
use Illuminate\Notifications\Notification;
use Melipayamak\MelipayamakApi;
use SoapClient;

class MeliPayamakChannel
{
    public function send($notifiable , Notification $notification)
    {
        if(! method_exists($notification , 'toMeliPayamakOtp')) {
            throw new \Exception('toMeliPayamakOtp not found');
        }

        $data = $notification->toMeliPayamakOtp($notifiable);

        $receptor = $data['phone'];
        $code = $data['code'];
        // Web OTP (Android Chrome SMS autofill) requires the SMS to end with: @<domain> #<code>
        // MeliPayamak OTP template should include a line like: @zanburak.ir #{1} (both params are the code)

        try
        {
            $username = config('services.meliPayamak.username');
            $password = config('services.meliPayamak.password');
            ini_set("soap.wsdl_cache_enabled","0");
            $sms = new SoapClient("http://api.payamak-panel.com/post/Send.asmx?wsdl",array("encoding"=>"UTF-8"));
            $data = array(
                "username"=>$username,
                "password"=>$password,
                "text"=>array($code, $code),
                "to"=>$receptor,
                "bodyId"=>config('services.meliPayamak.otp_template_id', '372965'),
            );
            $send_Result = $sms->SendByBaseNumber($data)->SendByBaseNumberResult;
            // echo $send_Result;
        }
        catch(ApiException $e){
            echo $e->errorMessage();
        }
        catch(HttpException $e){
            echo $e->errorMessage();
        }
    }
}

