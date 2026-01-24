<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MessageService
{
    // 
    static protected $apikey = env('WHATSAPP_API_KEY');
    static protected $apiurl = "https://api.uno-bot.ly";

    static public function to($phone, $msg, $private_token = null): bool
    {
        $phone = self::phoneFormater($phone);
        $private_token = $private_token ?? self::$apikey;
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $private_token,
            'Content-Type'  => 'application/json',
        ])->post(self::$apiurl . '/api/v1/whatsapp-developer/send-message', [
            'number' => $phone,
            'text' => $msg,
        ]);
        return $response->status() == 200 ? true : false;
    }

    static private function phoneFormater($phone)
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if (strpos($phone, '0') === 0) {
            $phone = '218' . substr($phone, 1);
        }

        if (strpos($phone, '218') === 0) {
            return $phone;
        }
        return '218' . $phone;
    }
}
