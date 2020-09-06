<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use Storage;
use App\OptionsManager;
use App\GoogleDataLoader;
use App\GoogleNeedConfigFileException;
use App\GoogleAPI;
use App\ClickHouse;
use App\GoogleGscSite;
use App\GscTask;
use App\Jobs\GscLoadJob;

class GscAccountsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $optionsManager = new OptionsManager();
        $optionsManager->setUser($user->id);
        try {
            $gloader = new GoogleDataLoader($optionsManager);
            $gloader->setMode(GoogleAPI::MODE_SERVICE);
            $gloader->setUser($user->id);
            $sites = $gloader->listGSCSites(function ($site) {
                $lastElement = GscTask::selectRaw("MAX(date) as date")
                    ->where("user_id", $site->user_id)
                    ->where("site_id", $site->id)
                    ->where("status", "!=", "disabled")
                    ->limit(1)
                    ->first();
                $firstElement = GscTask::selectRaw("MIN(date) as date")
                    ->where("user_id", $site->user_id)
                    ->where("site_id", $site->id)
                    ->where("status", "!=", "disabled")
                    ->limit(1)
                    ->first();
                if ($lastElement != null && $lastElement->date != null) {
                    $site->last_date = $lastElement->date;
                } else {
                    $site->last_date = null;
                }
                if ($firstElement != null && $firstElement->date != null) {
                    $site->first_date = $firstElement->date;
                } else {
                    $site->first_date = null;
                }
            });
            return view("gscaccounts.index")
                ->with("sites", $sites);
        } catch (GoogleNeedConfigFileException $e) {
            Session::flash("fail", "Необходимо загрузить файл сервис аккаунта Google в разделе 'Настройка доступа'");
            return view("gscaccounts.index")
                ->with("sites", array());
        }
    }

    public function load($id)
    {
        $site = GoogleGscSite::findOrFail($id);
        $user = Auth::user();

        $lastElement = GscTask::selectRaw("MAX(date) as date")
            ->where("user_id", $user->id)
            ->where("site_id", $site->id)
            ->where("status", "!=", "disabled")
            ->limit(1)
            ->first();
        if ($lastElement != null && $lastElement->date != null) {
            $dates = $this->datesRange($lastElement->date, $site->end_date);
        } else {
            $dates = $this->datesRange($site->start_date, $site->end_date);
        }
        $count = 0;
        if (count($dates) > 0) {
            $lastTaskId = null;
            foreach ($dates as $date) {
                $count++;
                $gscTask = new GscTask();
                $gscTask->date = $date;
                $gscTask->user_id = $user->id;
                $gscTask->site_id = $id;
                $gscTask->save();
                if ($lastTaskId == null) {
                    $lastTaskId = $gscTask->id;
                }
                GscLoadJob::dispatch($gscTask)
                ->onConnection('database')
                ->onQueue("gsc_data");
            }
            $site->last_task_id = $lastTaskId;
            $site->parsent = 0;
            $site->save();
        }
        return back()->withSuccess("Добавлено $count дат для загрузки");
    }

    public function status($id)
    {
        $site = GoogleGscSite::findOrFail($id);
        $result = array(
            "parsent" => $site->parsent,
            "site_id" => $id
        );
        return response()->json($result);
    }

    public function delete($id)
    {
        $site = GoogleGscSite::findOrFail($id);
        $user = Auth::user();
        $clickHouse = ClickHouse::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($id);
        $clickHouse->delete();
        GscTask::where("site_id", $id)
            ->where("user_id", $user->id)
            ->update(array("status" => "disabled"));
        return back()->withSuccess("Данные сайта полностью удалены!");
    }

    public function stop($id)
    {
        $site = GoogleGscSite::findOrFail($id);
        $user = Auth::user();
        $last_task_id = $site->last_task_id;
        $gscTask = GscTask::find($last_task_id);
        $lastDate = $gscTask->date;

        $clickHouse = ClickHouse::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site->id);

        GscTask::where("user_id", $user->id)
            ->where("site_id", $id)
            ->where("id", ">=", $last_task_id)
            ->update(array("status" => "disabled"));
        $clickHouse->deleteOlderThan($lastDate);
        return back()->withSuccess("Загрузка остановлена, загруженные данные удалены!");
    }

    private function datesRange($first, $last, $step = "+1 day", $output_format="Y-m-d")
    {
        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        if ($current == $last) {
            return array();
        }

        while ($current <= $last) {
            $dates[] = date($output_format, $current);
            $current = strtotime($step, $current);
        }

        return $dates;
    }
}
