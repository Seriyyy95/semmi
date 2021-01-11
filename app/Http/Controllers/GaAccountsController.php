<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\OptionsManager;
use App\GoogleDataLoader;
use App\GoogleNeedConfigFileException;
use App\GoogleAPI;
use App\ClickHouseViews;
use App\GoogleAnalyticsSite;
use App\GATask;
use App\LoadLogger;
use App\Jobs\GaLoadJob;

class GaAccountsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('demo');
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
            $sites = $gloader->listAnalyticsSites(function ($site) {
                $lastElement = GATask::selectRaw("MAX(date) as date")
                    ->where("user_id", $site->user_id)
                    ->where("site_id", $site->id)
                    ->where("status", "!=", "disabled")
                    ->limit(1)
                    ->first();
                $firstElement = GATask::selectRaw("MIN(date) as date")
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
            return view("gaaccounts.index")
                ->with("sites", $sites);
        } catch (GoogleNeedConfigFileException $e) {
            Session::flash("fail", "Необходимо загрузить файл сервис аккаунта Google в разделе 'Настройка доступа'");
            return view("gaaccounts.index")
                ->with("sites", array());
        }
    }

    public function load(Request $request, $id)
    {
        $site = GoogleAnalyticsSite::findOrFail($id);
        $user = Auth::user();

        $logger = new LoadLogger($user->id, "ga");

        $lastElement = GATask::selectRaw("MAX(date) as date")
            ->where("user_id", $user->id)
            ->where("site_id", $site->id)
            ->where("status", "!=", "disabled")
            ->limit(1)
            ->first();
        if ($lastElement != null && $lastElement->date != null) {
            $dates = $this->datesRange($lastElement->date, $site->end_date, 50);
        } else {
            $dates = $this->datesRange($site->start_date, $site->end_date, 50);
        }
        $count = 0;
        if ($request->has("last_task_id") && $request->get("last_task_id") > 0) {
            $lastTaskId = $request->get("last_task_id");
            $logger->write("В задание $lastTaskId добавлено " . count($dates) . " дат");
        } else {
            $lastTaskId = null;
            $logger->write("Новое задание создано, добавлено " . count($dates) . " дат");
        }

        if (count($dates) > 0) {
            foreach ($dates as $date) {
                $count++;
                $gscTask = new GATask();
                $gscTask->date = $date;
                $gscTask->user_id = $user->id;
                $gscTask->site_id = $id;
                $gscTask->save();
                if ($lastTaskId == null) {
                    $lastTaskId = $gscTask->id;
                }
                GaLoadJob::dispatch($gscTask)
                    ->onConnection('database')
                    ->onQueue("gsc_data");
            }
            $site->last_task_id = $lastTaskId;
            $site->parsent = 0;
            $site->save();
        }
        if ($count == 0) {
            Session::flash("Новые даты добавлены в очередь на загрузку!");
        }
        return response()->json(array(
            "last_task_id" => $lastTaskId,
            "count" => $count,
        ));
    }

    public function status($id)
    {
        $site = GoogleAnalyticsSite::findOrFail($id);
        $result = array(
            "parsent" => $site->parsent,
            "site_id" => $id
        );
        return response()->json($result);
    }

    public function delete($id)
    {
        $site = GoogleAnalyticsSite::findOrFail($id);
        $user = Auth::user();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($id);
        $clickHouse->delete();
        GATask::where("site_id", $id)
            ->where("user_id", $user->id)
            ->update(array("status" => "disabled"));
        return back()->withSuccess("Данные сайта полностью удалены!");
    }

    public function stop($id)
    {
        $site = GoogleAnalyticsSite::findOrFail($id);
        $user = Auth::user();
        $last_task_id = $site->last_task_id;

        GATask::where("user_id", $user->id)
            ->where("site_id", $id)
            ->where("id", ">=", $last_task_id)
            ->where("status", "active")
            ->update(array("status" => "disabled"));
        return back()->withSuccess("Загрузка остановлена, загруженные данные удалены!");
    }

    private function datesRange($first, $last, $count, $step = "+1 day", $output_format = "Y-m-d")
    {
        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        if ($current == $last) {
            return array();
        }

        $counter = 0;
        while ($current <= $last && $counter++ < $count) {
            $dates[] = date($output_format, $current);
            $current = strtotime($step, $current);
        }
        array_shift($dates);
        return $dates;
    }
}
