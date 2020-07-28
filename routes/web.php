<?php

use Illuminate\Support\Facades\Route;

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

Auth::routes();

Route::get('/', 'GscAccountsController@index');
Route::get('/home', 'GscAccountsController@index')->name('home')->middleware('auth');
Route::get('gscsettings', "GscSettingsController@index")->name("gscsettings.index");
Route::post('gscsettingsapply', "GscSettingsController@apply")->name("gscsettings.apply");

Route::get('gscaccounts', "GscAccountsController@index")->name("gscaccounts.index");
Route::get('gscaccounts/{id}/load', "GscAccountsController@load")->name("gscaccounts.load");
Route::get('gscaccounts/{id}/delete', "GscAccountsController@delete")->name("gscaccounts.delete");
Route::get('gscaccounts/{id}/status', "GscAccountsController@status")->name("gscaccounts.status");
Route::get('gscaccounts/{id}/stop', "GscAccountsController@stop")->name("gscaccounts.stop");

Route::get("stats/positions", "StatsController@positions")->name("stats.positions");
Route::get("stats/clicks", "StatsController@clicks")->name("stats.clicks");
Route::get("stats/impressions", "StatsController@impressions")->name("stats.impressions");
Route::get("stats/ctr", "StatsController@ctr")->name("stats.ctr");
Route::get("stats/select", "StatsController@selectSite")->name("stats.select_site");
Route::get("stats/get_url_positions", "StatsController@getUrlPositions")->name("stats.get_url_positions");

Route::get("changes/impressions", "ChangesController@impressions")->name("changes.impressions");
Route::get("changes/clicks", "ChangesController@clicks")->name("changes.clicks");
Route::get("changes/keywords", "ChangesController@keywords")->name("changes.keywords");