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

        try
        {
            $username = config('services.meliPayamak.username');
            $password = config('services.meliPayamak.password');
            ini_set("soap.wsdl_cache_enabled","0");
            $sms = new SoapClient("http://api.payamak-panel.com/post/Send.asmx?wsdl",array("encoding"=>"UTF-8"));
            $data = array(
                "username"=>$username,
                "password"=>$password,
                // "text"=>array($code),
                "text"=>array($code,$code),
                "to"=>$receptor,
                // "bodyId"=>"259235", // for mahakweb
                // "bodyId"=>"259360", // for mahakweb
                "bodyId"=>"372965", // for zanburak
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

