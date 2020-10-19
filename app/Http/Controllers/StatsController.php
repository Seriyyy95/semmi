<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\GoogleGscSite;
use App\GoogleAnalyticsSite;
use App\ClickHousePositions;
use App\ClickHouseViews;
use DateTime;

class StatsController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    public function impressions(Request $request)
    {
        $request->validate([
            'interval' => 'integer',
        ]);

        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $interval = $this->getInterval($request);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.positions")
            ->with("callback", "/stats/get_url_positions")
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
            ->with("useKeywords", true)
            ->with("invertColor", false);
    }

    public function clicks(Request $request)
    {
        $request->validate([
            'interval' => 'integer',
        ]);
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $interval = $this->getInterval($request);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.positions")
            ->with("callback", "/stats/get_url_positions")
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
            ->with("useKeywords", true)
            ->with("invertColor", false);
    }

    public function ctr(Request $request)
    {
        $request->validate([
            'interval' => 'integer',
        ]);
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $interval = $this->getInterval($request);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.positions")
            ->with("callback", "/stats/get_url_positions")
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
            ->with("useKeywords", true)
            ->with("invertColor", false);
    }

    public function positions(Request $request)
    {
        $request->validate([
            'interval' => 'integer',
        ]);
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $interval = $this->getInterval($request);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.positions")
            ->with("callback", "/stats/get_url_positions")
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
            ->with("useKeywords", true)
            ->with("invertColor", true);
    }

    public function pageviews(Request $request)
    {
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $sites = GoogleAnalyticsSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getMonthsPeriods($minDate, $maxDate);
        $periodsMetadata = $this->getPeriodsMetadata($periods);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.pageviews")
            ->with("callback", "/stats/get_url_pageviews")
            ->with("title", "Просмотры")
            ->with("periods", $periods)
            ->with("periodsMetadata", $periodsMetadata)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("field", 'pageviews')
            ->with("aggFunction", 'sum')
            ->with("minValue", "0")
            ->with("maxValue", "15000")
            ->with("invertColor", false);
    }

    public function revenue(Request $request)
    {
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $sites = GoogleAnalyticsSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getMonthsPeriods($minDate, $maxDate);
        $periodsMetadata = $this->getPeriodsMetadata($periods);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.pageviews")
            ->with("callback", "/stats/get_url_pageviews")
            ->with("title", "Доход")
            ->with("periods", $periods)
            ->with("periodsMetadata", $periodsMetadata)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("field", 'adsenseRevenue')
            ->with("aggFunction", 'sum')
            ->with("minValue", "0")
            ->with("maxValue", "5")
            ->with("invertColor", false);
    }

    public function organicSearches(Request $request)
    {
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $sites = GoogleAnalyticsSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getMonthsPeriods($minDate, $maxDate);
        $periodsMetadata = $this->getPeriodsMetadata($periods);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.pageviews")
            ->with("callback", "/stats/get_url_pageviews")
            ->with("title", "Просмотры из поиска")
            ->with("periods", $periods)
            ->with("periodsMetadata", $periodsMetadata)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("field", 'organicSearches')
            ->with("aggFunction", 'sum')
            ->with("minValue", "0")
            ->with("maxValue", "15000")
            ->with("invertColor", false);
    }



    public function getUrlPositions(Request $request)
    {
        $request->validate([
            'interval' => 'required|integer',
            'url' => 'required|url',
            'field' => 'required|in:impressions,clicks,avg_position,avg_ctr',
            'agg_function' => 'required|in:sum,avg'
        ]);
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $url = $request->get("url");
        $field = $request->get("field");
        $interval = $request->get("interval");
        $aggFunc = $request->get("agg_function");
        $isSearch = $request->get("is_search", false);
        if ($isSearch == true) {
            $request->session()->put('search_url', $url);
        } else {
            $request->session()->forget('search_url');
        }
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $data = $clickHouse->getPositionsHistory($periods, $url, $field, $aggFunc);
        return response()->json($data);
    }

    public function getUrlPageviews(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'field' => 'required|in:pageviews,adsenseRevenue,organicSearches',
            'agg_function' => 'required|in:sum,avg'
        ]);
        $user = Auth::user();
        $site_id = $request->session()->get("site_id", 1);
        $url = $request->get("url");
        $field = $request->get("field");
        $aggFunc = $request->get("agg_function");
        $isSearch = $request->get("is_search", false);
        if ($isSearch == true) {
            $request->session()->put('search_url', $url);
        } else {
            $request->session()->forget('search_url');
        }
        $sites = GoogleAnalyticsSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getMonthsPeriods($minDate, $maxDate);
        $data = $clickHouse->getDetailedHistory($periods, $url, $field, $aggFunc);
        return response()->json($data);
    }

    public function selectSite(Request $request)
    {
        $site_id = $request->get("site_id");
        $gscSite = GoogleGscSite::find($site_id);
        $gaSite = GoogleAnalyticsSite::find($site_id);

        if ($gscSite == null && $gaSite == null) {
            return back()->withFail("Сайт $site_id не найден!");
        }
        $request->session()->forget('search_url');
        $request->session()->put('site_id', $site_id);
        return back();
    }

    private function getPeriods($start_date, $end_date, $interval=30, $all=false)
    {
        if ($start_date == "0000-00-00") {
            return array();
        }
        $startDate = DateTime::createFromFormat('Y-m-d', $start_date);
        $endDate = DateTime::createFromFormat('Y-m-d', $end_date);
        $periods = array();
        $counter = 0;
        do {
            $counter++;
            $periodEndDate = $endDate->format("Y-m-d");
            $endDate->modify("-$interval days");
            $periodStartDate = $endDate->format("Y-m-d");
            array_unshift($periods, array(
                "start_date" => $periodStartDate,
                "end_date" => $periodEndDate,
            ));
            if ($all == false && $counter > 10) {
                break;
            }
        } while ($endDate > $startDate);
        return $periods;
    }

    private function getMonthsPeriods($start_date, $end_date)
    {
        if ($start_date == "0000-00-00") {
            return array();
        }
        $startDate = DateTime::createFromFormat('Y-m-d', $start_date);
        $endDate = DateTime::createFromFormat('Y-m-d', $end_date);
        $startDate->modify('first day of this month');
        $endDate->modify("last day of this month");
        $periods = array();
        do {
            $periodEndDate = $endDate->format("Y-m-d");
            $endDate->modify("first day of this month");
            $periodStartDate = $endDate->format("Y-m-d");
            $endDate->modify("-1 days");
            array_unshift($periods, array(
                "start_date" => $periodStartDate,
                "end_date" => $periodEndDate,
            ));
        } while ($endDate > $startDate);
        return $periods;
    }

    private function getPeriodsMetadata($periods)
    {
        $metadata = array();
        $firstYear = null;
        $lastYear = null;
        foreach ($periods as $index=>$period) {
            $startDate = DateTime::createFromFormat('Y-m-d', $period["start_date"]);
            $month = $startDate->format("m");
            $year = $startDate->format("Y");

            if ($firstYear == null) {
                $firstYear = $year;
            }
            $lastYear = $year;

            $metadata[$month.$year] = array(
                "period" => $period["start_date"] . " - " . $period["end_date"],
                "index" => $index,
                "month" => $month,
                "year" => $year
            );
        }
        return array(
            "firstYear" => $firstYear,
            "lastYear" => $lastYear,
            "periods" => $metadata,
        );
    }

    private function getInterval(Request $request)
    {
        if ($request->session()->has("interval")) {
            $saved_interval = $request->session()->get("interval");
        } else {
            $saved_interval = 30;
        }
        $interval = $request->get("interval", $saved_interval);
        if ($interval != $saved_interval) {
            $request->session()->put("interval", $interval);
        }
        return $interval;
    }
}
