<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExotelSmsService
{
    public function sendOtp($mobile, $otp)
    {
        try {

            $message = "Dear User, your OTP for login is {$otp} Please do not share it with anyone. Team Surya Path Kundli And Life Guidance";

            // Clean mobile number to be a 10-digit format matching the working curl
            $cleanedMobile = preg_replace('/[^0-9]/', '', $mobile);
            if (strlen($cleanedMobile) === 12 && str_starts_with($cleanedMobile, '91')) {
                $cleanedMobile = substr($cleanedMobile, 2);
            } elseif (strlen($cleanedMobile) > 10) {
                $cleanedMobile = substr($cleanedMobile, -10);
            }

            $url = "https://api.exotel.com/v1/Accounts/".env('EXOTEL_ACCOUNT_SID')."/Sms/send.json";

            $response = Http::withBasicAuth(
                env('EXOTEL_API_KEY'),
                env('EXOTEL_API_TOKEN')
            )->withHeaders([
                'accept' => 'application/json',
            ])->asForm()->post($url, [
                'From' => env('EXOTEL_SENDER_ID'),
                'To' => $cleanedMobile,
                'Body' => $message,
                'DltEntityId' => env('EXOTEL_DLT_ENTITY_ID'),
                'DltTemplateId' => env('EXOTEL_DLT_TEMPLATE_ID'),
            ]);

            Log::info('Exotel Config', [
                'sid' => env('EXOTEL_ACCOUNT_SID'),
                'sender' => env('EXOTEL_SENDER_ID'),
                'url' => $url,
            ]);

            Log::info('Exotel Raw Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            Log::info('Exotel SMS Response', [
                'mobile' => $mobile,
                'response' => $response->json()
            ]);

            return $response->json();

        } catch (\Exception $e) {

            Log::error('Exotel SMS Error: '.$e->getMessage());

            return false;
        }
    }
}