<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ISPService
{
    protected $baseUrl;
    protected $httpOptions;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.radius_api.base_url', 'http://my.sparktech.ly/app/'), '/') . '/';

        $this->httpOptions = [
            'verify' => config('services.radius_api.verify_ssl', false)
                ? (config('services.radius_api.ca_cert_path') ?: true)
                : false,
        ];
    }

    protected function http()
    {
        return Http::withOptions($this->httpOptions);
    }

    protected function httpWithAuth($authKey)
    {
        return $this->http()->withHeaders([
            'adv_auth' => $authKey,
        ]);
    }

    public function login($username, $password)
    {
        return $this->http()->asForm()->post($this->baseUrl . 'login', compact('username', 'password'));
    }

    public function autologin($ip)
    {
        return $this->http()->asForm()->post($this->baseUrl . 'autologin', compact('ip'));
    }

    public function getProfiles($authKey)
    {
        return $this->httpWithAuth($authKey)->post($this->baseUrl . 'profiles');
    }

    public function getProfilesBySubscrip($authKey)
    {
        return $this->httpWithAuth($authKey)->post($this->baseUrl . 'getprofiles');
    }

    public function getExtraQuota($authKey)
    {
        return $this->httpWithAuth($authKey)->post($this->baseUrl . 'getextraquota');
    }

    public function setProfile($authKey, $profileId)
    {
        return $this->httpWithAuth($authKey)->asForm()->post($this->baseUrl . 'setprofile', [
            'profile_id' => $profileId,
        ]);
    }

    public function register(array $data)
    {
        return $this->http()->asForm()->post($this->baseUrl . 'register', $data);
    }

    public function addCard($authKey, $cardNumber)
    {
        return $this->httpWithAuth($authKey)->asForm()->post($this->baseUrl . 'addcard', [
            'cardnum' => $cardNumber,
        ]);
    }

    public function getBalance($authKey)
    {
        return $this->httpWithAuth($authKey)->post($this->baseUrl . 'getBalance');
    }

    public function gMoney($authKey)
    {
        return $this->httpWithAuth($authKey)->post($this->baseUrl . 'gmoney');
    }

    public function logout($authKey)
    {
        return $this->httpWithAuth($authKey)->post($this->baseUrl . 'logout');
    }

    public function userDetails($authKey)
    {
        return $this->httpWithAuth($authKey)->post($this->baseUrl . 'details');
    }

    public function renew($authKey)
    {
        return $this->httpWithAuth($authKey)->post($this->baseUrl . 'renew');
    }

    public function setTrans($authKey, $transnum, $phonenum)
    {
        return $this->httpWithAuth($authKey)->asForm()->post($this->baseUrl . 'settrans', compact('transnum', 'phonenum'));
    }

    public function saveDetails(array $data = [])
    {
        return $this->http()->asForm()->post($this->baseUrl . 'saveDetails', $data);
    }

    public function notificationKey($authKey, $newKey)
    {
        return $this->httpWithAuth($authKey)->asForm()->post($this->baseUrl . 'noti', [
            'new_key' => $newKey,
        ]);
    }

    public function bandwidth($authKey, array $filters = [])
    {
        return $this->httpWithAuth($authKey)->asForm()->post($this->baseUrl . 'band', $filters);
    }

    public function events($authKey, array $filters = [])
    {
        return $this->httpWithAuth($authKey)->asForm()->post($this->baseUrl . 'events', $filters);
    }

    public function invoices($authKey, array $filters = [])
    {
        return $this->httpWithAuth($authKey)->asForm()->post($this->baseUrl . 'invoices', $filters);
    }

    public function detailsUser($authKey)
    {
        return $this->httpWithAuth($authKey)->post($this->baseUrl . 'details');
    }
}
