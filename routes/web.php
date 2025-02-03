<?php

use Illuminate\Support\Facades\Route;
use Spatie\GoogleCalendar\Facades\GoogleCalendar;
use App\Http\Controllers\GoogleCalendarController;


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


Route::get('/dashboard', function () {
	return view('dashboard');
})->middleware('checkGoogleAuth')->name('dashboard');

Route::get('google/redirect', [GoogleCalendarController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('google/callback', [GoogleCalendarController::class, 'handleGoogleCallback']);
Route::get('google/logout', [GoogleCalendarController::class, 'logout'])->name('google.logout');


require __DIR__ . '/auth.php';
