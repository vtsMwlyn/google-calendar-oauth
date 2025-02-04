<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Tasks;
use Google_Service_Oauth2;
use Illuminate\Support\Facades\Hash;

class GoogleServiceController extends Controller
{
    // Redirect the user to Google's OAuth authorization page
    public function redirectToGoogle()
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CALENDAR_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CALENDAR_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_CALENDAR_REDIRECT_URI'));
        $client->addScope([
			Google_Service_Calendar::CALENDAR,
			Google_Service_Tasks::TASKS,
			Google_Service_Oauth2::USERINFO_PROFILE,
			Google_Service_Oauth2::USERINFO_EMAIL
		]);
		$client->setAccessType('offline'); // Request offline access to get the refresh token
    	$client->setApprovalPrompt('force'); // Ensures the user always gets a refresh token

        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    // Handle the OAuth callback
    public function handleGoogleCallback(Request $request)
	{
		$client = new Google_Client();
		$client->setClientId(env('GOOGLE_CALENDAR_CLIENT_ID'));
		$client->setClientSecret(env('GOOGLE_CALENDAR_CLIENT_SECRET'));
		$client->setRedirectUri(env('GOOGLE_CALENDAR_REDIRECT_URI'));
		$client->addScope([
			Google_Service_Calendar::CALENDAR,
			Google_Service_Tasks::TASKS,
			Google_Service_Oauth2::USERINFO_PROFILE,
			Google_Service_Oauth2::USERINFO_EMAIL
		]);

		// Exchange the authorization code for access and refresh tokens
		$token = $client->fetchAccessTokenWithAuthCode($request->code);

		// dd($token);

		if (isset($token['error'])) {
			return redirect()->route('login')->with('error', 'Google login failed');
		}

		// Store the access token and refresh token in the session or database
		session(['google_access_token' => $token['access_token']]);

		// Logged in user information
		$oauthService = new Google_Service_Oauth2($client);
		$userInfo = $oauthService->userinfo->get();

		// User Registration
		$appUser = User::updateOrCreate(
			[
				'email' => $userInfo->email
			], [
				'name' => $userInfo->name,
				'password' => Hash::make('password')
			]
		);

		// Store refresh token if available (only returned on first authorization)
		if (isset($token['refresh_token'])) {
			$appUser->update(['google_refresh_token' => $token['refresh_token']]);
		} else {
			// If refresh token is missing, retrieve it from the database
			$existingToken = $appUser->google_refresh_token;
			if ($existingToken) {
				session(['google_refresh_token' => $existingToken]);
			}
		}

		session(['google_user_info' => [
			'name' => $userInfo->name,
			'email' => $userInfo->email,
			'picture' => $userInfo->picture,
		]]);
		session()->save();

		return redirect()->route('dashboard');
	}

	public function refreshGoogleAccessToken()
	{
		// Set up the Google client
		$client = new Google_Client();
		$client->setClientId(env('GOOGLE_CLIENT_ID'));
		$client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
		$client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
		$client->addScope([
			Google_Service_Calendar::CALENDAR,
			Google_Service_Oauth2::USERINFO_PROFILE,
			Google_Service_Oauth2::USERINFO_EMAIL,
			Google_Service_Tasks::TASKS,
		]);

		$accessToken = session('google_access_token');
		$client->setAccessToken($accessToken);

		// Retrieve stored refresh token
		$refreshToken = session('google_refresh_token') ?? User::where('email', session('google_user_info.email'))->value('google_refresh_token');

		if (!$refreshToken) {
			return response()->json(['error' => 'No refresh token available. Please re-authenticate.'], 401);
		}

		// $client->setRefreshToken($refreshToken); // ✅ Explicitly set the refresh token

		// Check if the access token is expired
		if ($client->isAccessTokenExpired()) {
			return 'hiya expired';

			// Refresh the access token
			$newAccessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

			if (isset($newAccessToken['error'])) {
				return response()->json(['error' => 'Failed to refresh token', 'details' => $newAccessToken], 400);
			}

			// Save the new access token in session
			session(['google_access_token' => $newAccessToken['access_token']]);

			// If a new refresh token is provided, update it in the database
			if (isset($newAccessToken['refresh_token'])) {
				User::where('email', session('google_user_info.email'))->update(['google_refresh_token' => $newAccessToken['refresh_token']]);
				session(['google_refresh_token' => $newAccessToken['refresh_token']]);
			}

			session()->save();
		}
	}



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

	public function dashboardWithGoogleCalendarEvents()
	{
		// $this->refreshGoogleAccessToken();

		// Retrieve the access token from the session
		$accessToken = session('google_access_token');

		// Set up the Google client with the stored access token
		$client = new Google_Client();
		// $client->setAccessToken($accessToken['access_token']);
		$client->setAccessToken($accessToken);

		// // Check if the access token is still valid
		if ($client->isAccessTokenExpired()) {
			$this->refreshGoogleAccessToken();
		}

		$oauthService = new Google_Service_Oauth2($client);
        $userInfo = $oauthService->userinfo->get();

		// Create a new Google Calendar service object
		$service = new Google_Service_Calendar($client);

		// ===== RETRIEVE ALL EVENTS FROM CALENDAR ===== //
		// Get the list of calendars
		$calendarList = $service->calendarList->listCalendarList();

		// Initialize an array to hold all the event data
		$allEventData = [];
		$targetCalendarNames = [$userInfo->email]; // Specify the calendar names you want to retrieve events from

		foreach ($calendarList->getItems() as $calendar) {
			$calendarName = $calendar->getSummary();

			// Check if this calendar is in your target calendars
			if (in_array($calendarName, $targetCalendarNames)) {
				$calendarId = $calendar->getId();

				// Retrieve events from this calendar
				$events = $service->events->listEvents($calendarId, [
					'maxResults' => 10,
					'orderBy' => 'startTime',
					'singleEvents' => true,
					'timeMin' => date('c'), // Current time in ISO 8601 format
				]);

				foreach ($events->getItems() as $event) {
					$allEventData[] = [
						'calendar' => $calendarName,
						'summary' => $event->getSummary(),
						'start' => $event->getStart()->getDateTime(),
						'end' => $event->getEnd()->getDateTime(),
					];
				}
			}
		}

		// ===== RETRIEVE ALL TASKS ===== //
		// Google Tasks service
		$tasksService = new Google_Service_Tasks($client);

		// Get the list of task lists (you can filter by task list name if needed)
		$taskLists = $tasksService->tasklists->listTasklists();

		$allTaskData = [];

		foreach ($taskLists->getItems() as $taskList) {
			// For each task list, retrieve the tasks
			$taskListId = $taskList->getId();

			$tasks = $tasksService->tasks->listTasks($taskListId);

			$tasksArray = $tasksArray = $tasks->getItems();
			usort($tasksArray, function($a, $b) {
				$dueA = $a->getDue() ? strtotime($a->getDue()) : 0;
				$dueB = $b->getDue() ? strtotime($b->getDue()) : 0;
				return $dueA - $dueB;
			});

			foreach ($tasksArray as $task) {
				$allTaskData[] = [
					'taskList' => $taskList->getTitle(),
					'title' => $task->getTitle(),
					'due' => $task->getDue(),
				];
			}
		}

		// Return the event data (you can pass this to a view or further process)
		return view('dashboard', [
			'allEventData' => $allEventData,
			'allTaskData' => $allTaskData,
			'token_is_expired' => $client->isAccessTokenExpired()
		]);
	}

}
