<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api as Api;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('gscaccounts/{id}/load', [ Api\GscAccountsController::class, 'load'])
    ->name("gscaccounts.load");
Route::get('gscaccounts/{id}/status', [ Api\GscAccountsController::class, 'status'])
    ->name("gscaccounts.load");
Route::get('gscaccounts/{id}/autoload/{state}', [ Api\GscAccountsController::class, 'autoload'])
    ->name("gscaccounts.autoload");

Route::get('gaaccounts/{id}/load', [ Api\GaAccountsController::class, 'load'])
    ->name("gaaccounts.load");
Route::get('gaaccounts/{id}/status', [ Api\GaAccountsController::class, 'status'])
    ->name("gaaccounts.load");
Route::get('gaaccounts/{id}/autoload/{state}', [ Api\GaAccountsController::class, 'autoload'])
    ->name("gaaccounts.autoload");


Route::get("page/get_url_calendar", [Api\PageController::class, "getUrlCalendar"])
    ->name("page.calendar");
Route::get("page/get_url_keywords", [Api\PageController::class, "getUrlKeywords"])
    ->name("page.keywords");
Route::get("page/get_url_graph", [Api\PageController::class, "getUrlGraph"])
    ->name("page.graph");

Route::get("request/execute", [Api\RequestController::class, 'execute'])
    ->name("request.execute");

