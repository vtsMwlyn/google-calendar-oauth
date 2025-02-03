<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;

class GoogleCalendarService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(config('app.google_credentials_path'));
        $this->client->addScope(Calendar::CALENDAR_READONLY);
        $this->client->setRedirectUri(config('app.google_redirect_uri'));
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function fetchAccessTokenWithAuthCode($code)
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }

    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);
    }

    public function listEvents($calendarId = 'primary')
    {
        $service = new Calendar($this->client);
        return $service->events->listEvents($calendarId)->getItems();
    }
}
