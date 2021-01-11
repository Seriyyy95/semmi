<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\User;

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

Auth::routes(['register' => false]);

Route::get('/', function () {
    $usersCount = User::count();
    if ($usersCount > 0) {
        return redirect(route("home"));
    } else {
        return redirect(route("installer.index"));
    }
});

Route::get('/install', 'InstallController@index')->name("installer.index");
Route::post('/install', 'InstallController@install')->name("installer.install");
Route::get('/home', 'PageController@index')->name('home')->middleware('auth');

Route::get('gscsettings', "GscSettingsController@index")->name("gscsettings.index");
Route::post('gscsettingsapply', "GscSettingsController@apply")->name("gscsettings.apply");

Route::get('gscaccounts', "GscAccountsController@index")->name("gscaccounts.index");
Route::get('gscaccounts/{id}/load', "GscAccountsController@load")->name("gscaccounts.load");
Route::get('gscaccounts/{id}/delete', "GscAccountsController@delete")->name("gscaccounts.delete");
Route::get('gscaccounts/{id}/status', "GscAccountsController@status")->name("gscaccounts.status");
Route::get('gscaccounts/{id}/stop', "GscAccountsController@stop")->name("gscaccounts.stop");

Route::get('gaaccounts', "GaAccountsController@index")->name("gaaccounts.index");
Route::get('gaaccounts/{id}/load', "GaAccountsController@load")->name("gaaccounts.load");
Route::get('gaaccounts/{id}/delete', "GaAccountsController@delete")->name("gaaccounts.delete");
Route::get('gaaccounts/{id}/status', "GaAccountsController@status")->name("gaaccounts.status");
Route::get('gaaccounts/{id}/stop', "GaAccountsController@stop")->name("gaaccounts.stop");

Route::get('/logs/{source}', 'LogsController@index')->name("logs.index");

Route::get("page", "PageController@index")->name("page.index");
Route::get("page/get_url_calendar", "PageController@getUrlCalendar")->name("page.calendar");
Route::get("page/get_url_keywords", "PageController@getUrlKeywords")->name("page.keywords");
Route::get("page/get_url_graph", "PageController@getUrlGraph")->name("page.graph");
Route::get("page/keyword_graph", "PageController@keywordGraph")->name("page.keyword_graph");

Route::get("stats/select_ga", "StatsController@selectGaSite")->name("stats.select_ga_site");

Route::get("changes", "ChangesController@index")->name("changes.index");
Route::get("changes/keywords", "ChangesController@keywords")->name("changes.keywords");

Route::get("request", "RequestController@index")->name("request.index");
Route::get("request/execute", "RequestController@execute")->name("request.execute");
