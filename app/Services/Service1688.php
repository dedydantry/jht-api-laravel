<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Service1688
{
    public static function getToken()
    {
        $response = Http::asForm()->post('https://gw.open.1688.com/openapi/http/1/system.oauth2/getToken/'.config('caribarang.app_key_1688'), [
            'grant_type' =>'authorization_code',
            'client_id' => config('caribarang.app_key_1688'),
            'client_secret' => config('caribarang.app_secret_1688'),
            'redirect_uri' => 'http://localhost:8000/1688/callback-token',
            'code' => ''
        ]);

        $response = $response->object();
        $now = Carbon::now();

        Cache::add('access_token_1688', $response->access_token, $now->addHour(9));
        return $response;
    }
    
    public static function refreshToken()
    {
        $response = Http::asForm()->post('https://gw.open.1688.com/openapi/param2/1/system.oauth2/getToken/'.config('caribarang.app_key_1688'), [
            'grant_type' => 'refresh_token',
            'client_id' => config('caribarang.app_key_1688'),
            'client_secret' => config('caribarang.app_secret_1688'),
            'refresh_token' => config('caribarang.refresh_token_1688') 
        ]);

        $response = $response->object();
        $now = Carbon::now();
        Cache::add('access_token_1688', $response->access_token, $now->addHour(9));
        return $response;
    }

    public static function token()
    {
        if(!Cache::has('access_token_1688')){
            Cache::forget('access_token_1688');
            self::refreshToken();
        }

        return Cache::get('access_token_1688');
    }
}