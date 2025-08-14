<?php

if (! function_exists('getMessageZarinpal')){
    function getMessageZarinpal($code){
        $message = "";
        switch($code){
            case "-51":
                $message = "پرداخت ناموفق بود";
                break;
            case "-52":
                $message = "خطای غیر منتظره با پشتیبانی تماس بگیرید";
                break;
            case "-53":
                $message = "اتوریتی برای این مرچنت کد نیست";
                break;
            case "-54":
                $message = "اتوریتی نامعتبر است";
                break;
            case "101":
                $message = "تراکنش قبلا وریفای شده است";
                break;
            case "-50":
                $message = "مبلغ پرداخت شده با مقدار مبلغ در وریفای متفاوت است";
                break;
            case "-12":
                $message = "تلاش بیش از حد در یک بازه زمانی کوتاه.";
                break;
            case "100":
                $message = "پرداخت با موفقیت انجام شد";
                break;

            default:
                $message = "خطای غیر منتظره رخ داده است با پشتیبانی تماس بگیرید";
        }

        return $message;
    }
}




