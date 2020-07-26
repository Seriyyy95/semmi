<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\GoogleGscSite;
use App\ClickHouse;
use DateTime;

class StatsController extends Controller
{

    public function impressions(Request $request){
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $interval = $request->get("inverval", 30);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouse::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.positions")
            ->with("title", "История показов")
            ->with("periods", $periods)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("interval", $interval)
            ->with("field", "impressions")
            ->with("minValue", "0")
            ->with("maxValue", "600")
            ->with("aggFunction", 'sum')
            ->with("invertColor", false);
    }

    public function clicks(Request $request){
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $interval = $request->get("inverval", 30);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouse::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.positions")
            ->with("title", "История показов")
            ->with("periods", $periods)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("interval", $interval)
            ->with("field", "clicks")
            ->with("minValue", "0")
            ->with("maxValue", "600")
            ->with("aggFunction", 'sum')
            ->with("invertColor", false);
    }

    public function ctr(Request $request){
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $interval = $request->get("inverval", 30);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouse::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.positions")
            ->with("title", "История показов")
            ->with("periods", $periods)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("interval", $interval)
            ->with("field", "avg_ctr")
            ->with("minValue", "0")
            ->with("maxValue", "0.5")
            ->with("aggFunction", 'avg')
            ->with("invertColor", false);
    }

    public function positions(Request $request){
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $interval = $request->get("interval", 30);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouse::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.positions")
            ->with("title", "История позиций")
            ->with("periods", $periods)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("field", 'avg_position')
            ->with("interval", $interval)
            ->with("aggFunction", 'avg')
            ->with("minValue", "1")
            ->with("maxValue", "20")
            ->with("invertColor", true);
    }

    public function getUrlPositions(Request $request){
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $url = $request->get("url");
        $field = $request->get("field");
        $interval = $request->get("interval");
        $aggFunc = $request->get("agg_function");
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouse::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $data = $clickHouse->getPositionsHistory($periods, $url, $field, $aggFunc);
        return response()->json($data);
    }

    public function selectSite(Request $request){
        $site_id = $request->get("site_id");
        $site = GoogleGscSite::find($site_id);
        if ($site == null) {
            return back()->withFail("Сайт $site_id не найден!");
        }
        $request->session()->put('site_id', $site_id);
        return back();
    }

    private function getPeriods($start_date, $end_date, $interval=30){
        if($start_date == "0000-00-00"){
            return array();
        }
        $startDate = DateTime::createFromFormat('Y-m-d', $start_date);
        $endDate = DateTime::createFromFormat('Y-m-d', $end_date);
        $periods = array();
        $counter = 0;
        do{
            $counter++;
            $periodEndDate = $endDate->format("Y-m-d");
            $endDate->modify("-$interval days");
            $periodStartDate = $endDate->format("Y-m-d");
            array_unshift($periods, array(
                "start_date" => $periodStartDate,
                "end_date" => $periodEndDate,
            ));
        }while($endDate > $startDate && $counter < 10);
        return $periods;
    }

}
