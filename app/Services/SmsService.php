<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public static function sendOtp($phone, $otp)
    {


        $suid = 'Edutech';
        $spass = 'India@123';
        $ssenderid = 'IQWING';
        $dlttempid = '1307160984068567287';
        $dltentityid = '1301159135888445655';
        $fbpage = 'MyIQwing';

        $message = "$otp is your IQdigit App OTP Pls subscribe our FB page to get update alerts $fbpage For enquiry vist www.iqwing.in";

        $url = 'https://api.pinnacle.in/index.php/sms/urlsms?sender=' . $ssenderid .
            '&numbers=' . $phone .
            '&messagetype=TXT&message=' . urlencode($message) .
            '&response=Y&apikey=3084c6-d0f4c3-83a1f6-d735b5-a92e64';

//        print_r($url); exit;

        $response = Http::get($url);

        return $response->successful();
    }
}
