<?php

namespace App\Http\Controllers;

use App\Models\GoogleAnalyticsTask;
use App\Repositories\GoogleAnalyticsSitesRepository;
use App\Repositories\GoogleAnalyticsTasksRepository;
use App\Services\GoogleAnalyticsLoadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\OptionsManager;
use App\GoogleDataLoader;
use App\GoogleNeedConfigFileException;
use App\GoogleAPI;
use App\ClickHouseViews;
use App\Models\GoogleAnalyticsSite;
use App\LoadLogger;
use App\Jobs\GaLoadJob;

class GaAccountsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('demo');
    }

    public function index(GoogleAnalyticsSitesRepository $repo)
    {
        $sites = $repo->updateAndListSites();

        return view("gaaccounts.index")
            ->with("sites", $sites);
    }


    public function delete($id, GoogleAnalyticsTasksRepository $repo)
    {
        $site = GoogleAnalyticsSite::findOrFail($id);
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setSite($id);
        $clickHouse->delete();

        $repo->disableAllSiteTasks($site);

        return back()->withSuccess("Данные сайта полностью удалены!");
    }

    public function stop($id, GoogleAnalyticsTasksRepository $repo)
    {
        $site = GoogleAnalyticsSite::findOrFail($id);
        $user = Auth::user();

        $repo->disableLastTasksGroup($site);
       return back()->withSuccess("Загрузка остановлена, загруженные данные удалены!");
    }


}
