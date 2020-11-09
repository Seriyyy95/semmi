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
            'interval' => 'string|in:week,month,quarter',
        ]);

        $user = Auth::user();
        $site_id = $request->session()->get("gsc_site_id", 1);
        $interval = $this->getInterval($request);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.history")
            ->with("callback", "/stats/get_url_history")
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
            'interval' => 'string|in:week,month,quarter',
        ]);
        $user = Auth::user();
        $site_id = $request->session()->get("gsc_site_id", 1);
        $interval = $this->getInterval($request);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.history")
            ->with("callback", "/stats/get_url_history")
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
            'interval' => 'string|in:week,month,quarter',
        ]);
        $user = Auth::user();
        $site_id = $request->session()->get("gsc_site_id", 1);
        $interval = $this->getInterval($request);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $maxValue = $clickHouse->getMaxValue("ctr", $interval, "avg");
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.history")
            ->with("callback", "/stats/get_url_history")
            ->with("title", "История показов")
            ->with("periods", $periods)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("interval", $interval)
            ->with("field", "avg_ctr")
            ->with("minValue", "0")
            ->with("maxValue", $maxValue)
            ->with("aggFunction", 'avg')
            ->with("useKeywords", true)
            ->with("invertColor", false);
    }

    public function positions(Request $request)
    {
        $request->validate([
            'interval' => 'string|in:week,month,quarter',
        ]);
        $user = Auth::user();
        $site_id = $request->session()->get("gsc_site_id", 1);
        $interval = $this->getInterval($request);
        $sites = GoogleGscSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, $interval);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.history")
            ->with("callback", "/stats/get_url_history")
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

    public function pageviews(Request $request)
    {
        $user = Auth::user();
        $site_id = $request->session()->get("ga_site_id", 1);
        $sites = GoogleAnalyticsSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $maxValue = $clickHouse->getMaxValue("pageviews", "month", "sum");
        $periods = $this->getPeriods($minDate, $maxDate, "month", true);
        $periodsMetadata = $this->getPeriodsMetadata($periods);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.calendar")
            ->with("callback", "/stats/get_url_calendar")
            ->with("title", "Просмотры")
            ->with("periods", $periods)
            ->with("periodsMetadata", $periodsMetadata)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("field", 'pageviews')
            ->with("aggFunction", 'sum')
            ->with("minValue", "0")
            ->with("maxValue", $maxValue)
            ->with("invertColor", false);
    }

    public function revenue(Request $request)
    {
        $user = Auth::user();
        $site_id = $request->session()->get("ga_site_id", 1);
        $sites = GoogleAnalyticsSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $maxValue = $clickHouse->getMaxValue("adsenseRevenue", "month", "sum");
        $periods = $this->getPeriods($minDate, $maxDate, "month", true);
        $periodsMetadata = $this->getPeriodsMetadata($periods);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.calendar")
            ->with("callback", "/stats/get_url_calendar")
            ->with("title", "Доход")
            ->with("periods", $periods)
            ->with("periodsMetadata", $periodsMetadata)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("field", 'adsenseRevenue')
            ->with("aggFunction", 'sum')
            ->with("minValue", "0")
            ->with("maxValue", $maxValue)
            ->with("invertColor", false);
    }

    public function organicSearches(Request $request)
    {
        $user = Auth::user();
        $site_id = $request->session()->get("ga_site_id", 1);
        $sites = GoogleAnalyticsSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $maxValue = $clickHouse->getMaxValue("organicSearches", "month", "sum");
        $periods = $this->getPeriods($minDate, $maxDate, "month", true);
        $periodsMetadata = $this->getPeriodsMetadata($periods);
        $urls = $clickHouse->getUrls($periods);
        return view("stats.calendar")
            ->with("callback", "/stats/get_url_calendar")
            ->with("title", "Просмотры из поиска")
            ->with("periods", $periods)
            ->with("periodsMetadata", $periodsMetadata)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("field", 'organicSearches')
            ->with("aggFunction", 'sum')
            ->with("minValue", "0")
            ->with("maxValue", $maxValue)
            ->with("invertColor", false);
    }



    public function getUrlHistory(Request $request)
    {
        $request->validate([
            'interval' => 'required|string|in:week,month,quarter',
            'url' => 'required|url',
            'field' => 'required|in:impressions,clicks,avg_position,avg_ctr',
            'agg_function' => 'required|in:sum,avg'
        ]);
        $user = Auth::user();
        $site_id = $request->session()->get("gsc_site_id", 1);
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
        $data = $clickHouse->getHistoryData($periods, $url, $field, $aggFunc);
        return response()->json($data);
    }

    public function getUrlCalendar(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'field' => 'required|in:pageviews,adsenseRevenue,organicSearches',
            'agg_function' => 'required|in:sum,avg'
        ]);
        $user = Auth::user();
        $site_id = $request->session()->get("ga_site_id", 1);
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
        $periods = $this->getPeriods($minDate, $maxDate, "month", true);
        $data = $clickHouse->getHistoryData($periods, $url, $field, $aggFunc);
        return response()->json($data);
    }

    public function selectGscSite(Request $request)
    {
        $site_id = $request->get("site_id");
        $gaSite = GoogleAnalyticsSite::find($site_id);

        if ($gaSite == null) {
            return back()->withFail("Сайт $site_id не найден!");
        }
        $request->session()->forget('search_url');
        $request->session()->put('gsc_site_id', $site_id);
        return back();
    }

    public function selectGaSite(Request $request)
    {
        $site_id = $request->get("site_id");
        $gaSite = GoogleAnalyticsSite::find($site_id);

        if ($gaSite == null) {
            return back()->withFail("Сайт $site_id не найден!");
        }
        $request->session()->forget('search_url');
        $request->session()->put('ga_site_id', $site_id);
        return back();
    }

    public function selectWpSite(Request $request)
    {
        $site_id = $request->get("site_id");

        /*        if ($gaSite == null) {
                    return back()->withFail("Сайт $site_id не найден!");
                }*/
        $request->session()->forget('search_url');
        $request->session()->put('wp_site_id', $site_id);
        return back();
    }



    private function getPeriods($start_date, $end_date, $interval="month", $all=false)
    {
        if ($start_date == "0000-00-00") {
            return array();
        }
        $startDate = DateTime::createFromFormat('Y-m-d', $start_date);
        $endDate = DateTime::createFromFormat('Y-m-d', $end_date);
        if ($interval == "week") {
            $startDate->modify("this week");
            $endDate->modify("this week +6 days");
        } elseif ($interval == "month") {
            $startDate->modify('first day of this month');
            $endDate->modify("last day of this month");
        } elseif ($interval == "quarter") {
            $this->makeFirstDayOfQuarter($startDate);
            $this->makeLastDayOfQuarter($endDate);
        }
        $periods = array();
        $counter = 0;
        do {
            $counter++;
            $periodEndDate = $endDate->format("Y-m-d");
            if ($interval == "week") {
                $endDate->modify("this week");
            } elseif ($interval == "month") {
                $endDate->modify("first day of this month");
            } elseif ($interval == "quarter") {
                $this->makeFirstDayOfQuarter($endDate);
            }
            $periodStartDate = $endDate->format("Y-m-d");
            if ($interval == "week") {
                $periodName =  $periodStartDate . " - " . $periodEndDate;
            } elseif ($interval == "month") {
                $periodName = $endDate->format("Y") . "-" . $endDate->format("n");
            } elseif ($interval == "quarter") {
                $periodName = $endDate->format("Y") . "Q" . ceil($endDate->format("n")/3);
            }
            $endDate->modify("-1 days");
            array_unshift($periods, array(
                "start_date" => $periodStartDate,
                "end_date" => $periodEndDate,
                "name" => $periodName,
            ));
            if ($all == false && $counter > 10) {
                break;
            }
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

    private function makeLastDayOfQuarter(DateTime $date)
    {
        $month = $date->format('n') ;

        if ($month < 4) {
            $date->modify('last day of march ' . $date->format('Y'));
        } elseif ($month > 3 && $month < 7) {
            $date->modify('first day of june ' . $date->format('Y'));
        } elseif ($month > 6 && $month < 10) {
            $date->modify('first day of september ' . $date->format('Y'));
        } elseif ($month > 9) {
            $date->modify('first day of december ' . $date->format('Y'));
        }
    }

    private function makeFirstDayOfQuarter(DateTime $date)
    {
        $month = $date->format('n') ;

        if ($month < 4) {
            $date->modify('first day of january ' . $date->format('Y'));
        } elseif ($month > 3 && $month < 7) {
            $date->modify('first day of april ' . $date->format('Y'));
        } elseif ($month > 6 && $month < 10) {
            $date->modify('first day of july ' . $date->format('Y'));
        } elseif ($month > 9) {
            $date->modify('first day of october ' . $date->format('Y'));
        }
    }

    private function getInterval(Request $request)
    {
        if ($request->session()->has("interval")) {
            $saved_interval = $request->session()->get("interval");
        } else {
            $saved_interval = 'month';
        }
        $interval = $request->get("interval", $saved_interval);
        if ($interval != $saved_interval) {
            $request->session()->put("interval", $interval);
        }
        return $interval;
    }
}
