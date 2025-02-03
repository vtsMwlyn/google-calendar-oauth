<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckGoogleAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
	{
		// Check if the user has an access token (i.e., authenticated via Google OAuth)
		if (!session()->has('google_access_token')) {
			return redirect()->route('google.redirect');  // Redirect to Google OAuth login page if not authenticated
		}

		return $next($request);
	}

}
