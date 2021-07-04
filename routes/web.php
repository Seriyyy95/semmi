<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Http\Controllers as Web;

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

Route::get('/install', [Web\InstallController::class, 'index'])
    ->name("installer.index");
Route::post('/install', [Web\InstallController::class, 'install'])
    ->name("installer.install");
Route::get('/home', [Web\PageController::class,'index'])
    ->name('home');

Route::get('gscsettings', [Web\GscSettingsController::class, 'index'])
    ->name("gscsettings.index");
Route::post('gscsettings', [Web\GscSettingsController::class, 'apply'])
    ->name("gscsettings.apply");
Route::get('gscsettings/delete', [Web\GscSettingsController::class, 'delete'])
    ->name("gscsettings.delete");

Route::get('gscaccounts', [Web\GscAccountsController::class, 'index'])
    ->name("gscaccounts.index");
Route::get('gscaccounts/{id}/delete', [Web\GscAccountsController::class, 'delete'])
    ->name("gscaccounts.delete");
Route::get('gscaccounts/{id}/stop', [Web\GscAccountsController::class, 'stop'])
    ->name("gscaccounts.stop");

Route::get('gaaccounts', [Web\GaAccountsController::class, 'index'])
    ->name("gaaccounts.index");
Route::get('gaaccounts/{id}/delete', [Web\GaAccountsController::class, 'delete'])
    ->name("gaaccounts.delete");
Route::get('gaaccounts/{id}/stop', [Web\GaAccountsController::class, 'stop'])
    ->name("gaaccounts.stop");

Route::get('/logs/{source}', [Web\LogsController::class, 'index'])
    ->name("logs.index");

Route::get("page", [Web\PageController::class, 'index'])
    ->name("page.index");
Route::get("page/keyword_graph", [Web\PageController::class, 'keywordGraph'])
    ->name("page.keyword_graph");


Route::get("stats/select_ga", "StatsController@selectGaSite")->name("stats.select_ga_site");

Route::get("changes", [Web\ChangesController::class, 'index'])
    ->name("changes.index");
Route::get("changes/keywords", [Web\ChangesController::class, 'keywords'])
    ->name("changes.keywords");

Route::get("request", [Web\RequestController::class, 'index'])
    ->name("request.index");
