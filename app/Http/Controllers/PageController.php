<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\GoogleAnalyticsSite;
use App\GoogleGscSite;
use App\ClickHouseViews;
use App\ClickHousePositions;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $site_id = $request->session()->get("ga_site_id", 1);
        $sites = GoogleAnalyticsSite::where("user_id", $user->id)->get();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, "month", true);
        $periodsMetadata = $this->getPeriodsMetadata($periods);
        $urls = $clickHouse->getUrls($periods);
        return view("page.index")
            ->with("periods", $periods)
            ->with("periodsMetadata", $periodsMetadata)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("invertColor", false);
    }

    public function keywordGraph(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
        ]);
        $user = Auth::user();
        $keyword = $request->get("keyword");

        $ga_site_id = $request->session()->get("ga_site_id", 1);
        $gaSite = GoogleAnalyticsSite::findOrFail($ga_site_id);
        $gscSite = GoogleGscSite::where("domain", $gaSite->domain)->first();
        if ($gscSite == null) {
            return response()->json(array());
        }
        $site_id = $gscSite->id;

        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $positionRawData = $clickHouse->getKeywordGraphData($keyword, "month", "avg_position", "avg");
        $clicksRawData = $clickHouse->getKeywordGraphData($keyword, "month", "clicks", "sum");
        $impressionsRawData = $clickHouse->getKeywordGraphData($keyword, "month", "impressions", "sum");
        $cvRawData = $clickHouse->getKeywordCVData($keyword, "month", "avg_position");
        $headerData = array();
        $positionData = array();
        $clicksData = array();
        $impressionsData = array();
        $cvData = array();
        foreach ($positionRawData as $row) {
            $headerData[] = $row["row_date"];
            $positionData[] = $row["row_value"];
        }
        foreach ($clicksRawData as $row) {
            $clicksData[] = $row["row_value"];
        }
        foreach ($impressionsRawData as $row) {
            $impressionsData[] = $row["row_value"];
        }
        foreach ($cvRawData as $row) {
            $cvData[] = round($row["stdev_value"] / $row["avg_value"] * 100, 2);
        }

        return view("page.keywords_graph")
            ->with("headerData", $headerData)
            ->with("positionsData", $positionData)
            ->with("clicksData", $clicksData)
            ->with("impressionsData", $impressionsData)
            ->with("cvData", $cvData);
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

        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->getPeriods($minDate, $maxDate, "month", true);
        $data = $clickHouse->getHistoryData($periods, $url, $field, $aggFunc);
        return response()->json($data);
    }

    public function getUrlKeywords(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);
        $user = Auth::user();
        $url = $request->get("url");

        $ga_site_id = $request->session()->get("ga_site_id", 1);
        $gaSite = GoogleAnalyticsSite::findOrFail($ga_site_id);
        $gscSite = GoogleGscSite::where("domain", $gaSite->domain)->first();
        if ($gscSite == null) {
            return response()->json(array());
        }
        $site_id = $gscSite->id;

        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $keywords = $clickHouse->getAllUrlKeywords($url);
        return response()->json($keywords);
    }

    public function getUrlGraph(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);
        $user = Auth::user();
        $url = $request->get("url");

        $ga_site_id = $request->session()->get("ga_site_id", 1);
        $gaSite = GoogleAnalyticsSite::findOrFail($ga_site_id);
        $gscSite = GoogleGscSite::where("domain", $gaSite->domain)->first();
        if ($gscSite == null) {
            return response()->json(array());
        }
        $site_id = $gscSite->id;

        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setUser($user->id);
        $clickHouse->setSite($site_id);
        $positionsRawData = $clickHouse->getUrlGraphData($url, "month", "avg_position", "avg");
        $cvRawData = $clickHouse->getUrlCVData($url, "month", "avg_position");
        $headerData = array();
        $positionsData = array();
        $cvData = array();
        foreach ($positionsRawData as $row) {
            $headerData[] = $row["row_date"];
            $positionsData[] = $row["row_value"];
        }
        foreach ($cvRawData as $row) {
            $cvData[] = $row["row_value"];
        }

        return response()->json(array(
            "headerData" => $headerData,
            "positionsData" => $positionsData,
            "cvData" => $cvData,
        ));
    }

    private function getPeriods($start_date, $end_date, $interval="month", $all=false)
    {
        if ($start_date == "0000-00-00") {
            return array();
        }
        $startDate = \DateTime::createFromFormat('Y-m-d', $start_date);
        $endDate = \DateTime::createFromFormat('Y-m-d', $end_date);
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
            $startDate = \DateTime::createFromFormat('Y-m-d', $period["start_date"]);
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

    private function makeLastDayOfQuarter(\DateTime $date)
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

    private function makeFirstDayOfQuarter(\DateTime $date)
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
}
