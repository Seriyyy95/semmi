<?php

namespace App\Http\Controllers;

use App\Repositories\GscSitesRepository;
use App\Repositories\GscTaskRepository;
use Illuminate\Support\Facades\Auth;
use App\ClickHousePositions;
use App\Models\GoogleGscSite;

class GscAccountsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('demo');
    }

    public function index(GscSitesRepository $repo)
    {
       $sites = $repo->updateAndListSites();

       return view("gscaccounts.index")
                ->with("sites", $sites);

    }

    public function delete($id, GscTaskRepository $repo)
    {
        $site = GoogleGscSite::findOrFail($id);
        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setSite($id);
        $clickHouse->delete();

        $repo->disableAllSiteTasks($site);

        return back()->withSuccess("Данные сайта полностью удалены!");
    }

    public function stop($id, GscTaskRepository $repo)
    {
        $site = GoogleGscSite::findOrFail($id);

        $repo->disableLastTasksGroup($site);

        return back()->withSuccess("Загрузка остановлена, загруженные данные удалены!");
    }
}
