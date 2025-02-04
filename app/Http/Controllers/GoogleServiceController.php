<?php

namespace App\Http\Controllers;

use Exception;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Tasks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Google_Service_Oauth2;

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
		// Check if the 'code' parameter is present in the request (OAuth flow completed)
		// if ($request->has('code')) {
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

			if (isset($token['error'])) {
				return redirect()->route('login')->with('error', 'Google login failed');
			}

			// // Check if the refresh token is returned
			// if (!isset($token['refresh_token'])) {
			// 	return redirect()->route('login')->with('error', 'Refresh token not returned. Please try again.');
			// }

			// Store the access token and refresh token in the session or database
			session(['google_access_token' => $token['access_token']]);
			// session(['google_refresh_token' => $token['refresh_token']]); // Store refresh token

			$oauthService = new Google_Service_Oauth2($client);
            $userInfo = $oauthService->userinfo->get();

			session(['google_user_info' => [
				'name' => $userInfo->name,
				'email' => $userInfo->email,
				'picture' => $userInfo->picture,
			]]);
			session()->save();

			return redirect()->route('dashboard');

		// 	try {
		// 		// Exchange the authorization code for an access token
		// 		$accessToken = $client->fetchAccessTokenWithAuthCode($request->get('code'));

		// 		// Check if access token is received
		// 		if (isset($accessToken['access_token'])) {
		// 			// Store the access token for future requests (e.g., in session or database)
		// 			session(['google_access_token' => $accessToken]);

        //             $oauthService = new Google_Service_Oauth2($client);
        //             $userInfo = $oauthService->userinfo->get();

        //             // Store the user info in session or database
        //             session(['google_user_info' => [
        //                 'name' => $userInfo->name,
        //                 'email' => $userInfo->email,
		// 				'picture' => $userInfo->picture,
        //             ]]);
		// 			session()->save();

		// 			// Now the login process is complete
		// 			return redirect()->route('dashboard');  // Redirect to your desired route (e.g., dashboard)
		// 		} else {
		// 			return redirect()->route('login')->with('error', 'Failed to authenticate.');
		// 		}
		// 	} catch (Exception $e) {
		// 		return redirect()->route('login')->with('error', 'OAuth error: ' . $e->getMessage());
		// 	}
		// } else {
		// 	// If no code is received, that means the login process is incomplete or failed
		// 	return redirect()->route('login')->with('error', 'OAuth process failed.');
		// }
	}

	public function refreshGoogleAccessToken()
	{
		$refreshToken = session('google_refresh_token');

		if (!$refreshToken) {
			return redirect()->route('login')->with('error', 'Google refresh token not found');
		}

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

		// Set the refresh token
		$client->setAccessToken(['refresh_token' => $refreshToken]);

		// Check if the access token is expired
		if ($client->isAccessTokenExpired()) {
			// Refresh the access token
			$newAccessToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

			// Save the new access token in the session
			session(['google_access_token' => $newAccessToken['access_token']]);

			// You can now use the refreshed access token to make Google API requests
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

		if (!$accessToken) {
			return redirect()->route('login')->with('error', 'Google access token not found');
		}

		// Set up the Google client with the stored access token
		$client = new Google_Client();
		// $client->setAccessToken($accessToken['access_token']);
		$client->setAccessToken($accessToken);

		// // Check if the access token is still valid
		// if ($client->isAccessTokenExpired()) {
		// 	// Handle expired access token (refresh it if necessary or re-authenticate)
		// 	return redirect()->route('login')->with('error', 'Your session is expired');
		// }

		// Create a new Google Calendar service object
		$service = new Google_Service_Calendar($client);

		// ===== RETRIEVE ALL EVENTS FROM CALENDAR ===== //
		// Get the list of calendars
		$calendarList = $service->calendarList->listCalendarList();

		// Initialize an array to hold all the event data
		$allEventData = [];
		$targetCalendarNames = ['Tasks', 'vannestheo@gmail.com']; // Specify the calendar names you want to retrieve events from

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
			'allTaskData' => $allTaskData
		]);
	}

}
