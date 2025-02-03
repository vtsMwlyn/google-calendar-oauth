<?php

namespace App\Http\Controllers;

use Exception;
use Google_Client;
use Google_Service_Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Google_Service_Oauth2;

class GoogleCalendarController extends Controller
{
    // Redirect the user to Google's OAuth authorization page
    public function redirectToGoogle()
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CALENDAR_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CALENDAR_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_CALENDAR_REDIRECT_URI'));
        $client->addScope(Google_Service_Calendar::CALENDAR);

        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    // Handle the OAuth callback
    public function handleGoogleCallback(Request $request)
	{
		// Check if the 'code' parameter is present in the request (OAuth flow completed)
		if ($request->has('code')) {
			$client = new Google_Client();
			$client->setClientId(config('google-calendar.client_id'));
			$client->setClientSecret(config('google-calendar.client_secret'));
			$client->setRedirectUri(config('google-calendar.redirect_uri'));
			$client->addScope(Google_Service_Calendar::CALENDAR);

			try {
				// Exchange the authorization code for an access token
				$accessToken = $client->fetchAccessTokenWithAuthCode($request->get('code'));

				// Check if access token is received
				if (isset($accessToken['access_token'])) {
					// Store the access token for future requests (e.g., in session or database)
					session(['google_access_token' => $accessToken]);

					// Now the login process is complete
					return redirect()->route('dashboard');  // Redirect to your desired route (e.g., dashboard)
				} else {
					return redirect()->route('login')->with('error', 'Failed to authenticate.');
				}
			} catch (Exception $e) {
				return redirect()->route('login')->with('error', 'OAuth error: ' . $e->getMessage());
			}
		} else {
			// If no code is received, that means the login process is incomplete or failed
			return redirect()->route('login')->with('error', 'OAuth process failed.');
		}
	}

	// public function handleGoogleCallback(Request $request)
    // {
    //     // Initialize Google Client
    //     $client = new Google_Client();
    //     $client->setClientId(config('google-calendar.client_id'));
    //     $client->setClientSecret(config('google-calendar.client_secret'));
    //     $client->setRedirectUri(config('google-calendar.redirect_uri'));
    //     $client->addScope(Google_Service_Calendar::CALENDAR);

    //     // Retrieve the access token from the callback
    //     $accessToken = $client->fetchAccessTokenWithAuthCode($request->get('code'));

	// 	dd($accessToken);

    //     // Save the access token and refresh token in the session (or in the database)
    //     session(['google_access_token' => $token]);

    //     // Initialize the OAuth2 service to fetch user info
    //     $oauthService = new Google_Service_Oauth2($client);
    //     $userInfo = $oauthService->userinfo->get();

    //     // Store the user info in session or database
    //     session(['google_user_info' => [
    //         'name' => $userInfo->name,
    //         'email' => $userInfo->email,
    //     ]]);

    //     // Redirect to a dashboard or home page
    //     return redirect()->route('dashboard');
    // }

	public function logout()
    {
        // Create a new Google Client
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CALENDAR_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CALENDAR_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_CALENDAR_REDIRECT_URI'));

        // Get the access token from the session
        $accessToken = session('google_access_token');

        // If access token exists, revoke it
        if ($accessToken) {
            $client->setAccessToken($accessToken);
            $client->revokeToken();  // This will revoke the token
        }

        // Clear the Google-related session data
        session()->forget(['google_access_token', 'google_refresh_token', 'google_user_info']);

        // Redirect to a homepage or login page
        return redirect()->route('login');
    }

}
