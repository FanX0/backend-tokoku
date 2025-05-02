<?php

namespace App\Services;

use Google_Client;
use Illuminate\Support\Facades\Log;

class GoogleAccessTokenService
{
    protected $client;

    public function __construct()
    {
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

        $credentialsPath = env('GOOGLE_APPLICATION_CREDENTIALS');
        Log::info('Google Credentials Path: ' . $credentialsPath);

        $this->client = new Google_Client();
        $this->client->setAuthConfig($credentialsPath);
        $this->client->setScopes($scopes);
    }

    public function getAccessToken()
    {
        $this->client->fetchAccessTokenWithAssertion();
        return $this->client->getAccessToken()['access_token'];
    }
}
