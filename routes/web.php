<?php

use Illuminate\Support\Facades\Route;
use Spatie\GoogleCalendar\Facades\GoogleCalendar;
use App\Http\Controllers\GoogleServiceController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth::routes(['verify' => true]);


Route::get('/', function () {
	return view('welcome');
});


Route::get('/dashboard', [GoogleServiceController::class, 'dashboardWithGoogleCalendarEvents'])->middleware('checkGoogleAuth')->name('dashboard');

Route::get('google/redirect', [GoogleServiceController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('google/callback', [GoogleServiceController::class, 'handleGoogleCallback']);
Route::get('google/logout', [GoogleServiceController::class, 'logout'])->name('google.logout');


require __DIR__ . '/auth.php';
