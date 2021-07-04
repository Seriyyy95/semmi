<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GoogleGscSite;
use App\Models\GoogleAnalyticsSite;
use App\ClickHousePositions;
use App\ClickHouseViews;
use DateTime;

class ChangesController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    public function index(Request $request)
    {
        $request->validate([
            'first_period' => 'regex:/\d{4}\-\d{2}\-\d{2} \- \d{4}\-\d{2}\-\d{2}/',
            'second_period' => 'regex:/\d{4}\-\d{2}\-\d{2} \- \d{4}\-\d{2}\-\d{2}/',
        ]);

        $site_id = $request->session()->get("ga_site_id", 1);
        $sites = GoogleAnalyticsSite::all();

        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();

        if ($request->has("first_period") && $request->has("second_period")) {
            list($firstPeriod, $secondPeriod) = $this->getPeriodsFromRequest($request);
        } else {
            list($firstPeriod, $secondPeriod) = $this->getDefaultPeriods($minDate, $maxDate);
        }
        if ($firstPeriod === false) {
            return back()->withFail("Период задан не верно или недостаточно данных для анализа");
        }
        $data = $clickHouse->getChangesData("pageviews", $firstPeriod, $secondPeriod);
        $grownData = array();
        $downData = array();
        $stableData = array();
        $totalData = 0;
        $totalGrown = 0;
        $countGrown = 0;
        $totalDown = 0;
        $countDown = 0;
        $countStable = 0;
        foreach ($data as $row) {
            $totalData += $row["data"];
            if ($row["result"] > 0) {
                $grownData[] = $row;
                $totalGrown +=  $row["result"];
                $countGrown++;
            } elseif ($row["result"] < 0) {
                $downData[] = $row;
                $totalDown +=  $row["result"];
                $countDown++;
            } else {
                $stableData[] = $row;
                $countStable++;
            }
        }
        uasort($grownData, function ($a, $b) {
            return ($b['result'] - $a['result']);
        });
        uasort($downData, function ($a, $b) {
            return ($a['result'] - $b['result']);
        });
        uasort($stableData, function ($a, $b) {
            return ($b['data'] - $a['data']);
        });
        return view("changes.index")
            ->with("totalData", $totalData)
            ->with("stableData", $stableData)
            ->with("grownData", $grownData)
            ->with("downData", $downData)
            ->with("totalGrown", $totalGrown)
            ->with("totalDown", $totalDown)
            ->with("countGrown", $countGrown)
            ->with("countDown", $countDown)
            ->with("countStable", $countStable)
            ->with("first_period", $firstPeriod)
            ->with("second_period", $secondPeriod)
            ->with("minDate", $minDate)
            ->with("maxDate", $maxDate)
            ->with("site_id", $site_id)
            ->with("sites", $sites);
    }

    public function keywords(Request $request)
    {
        $request->validate([
            'first_period' => 'regex:/\d{4}\-\d{2}\-\d{2} \- \d{4}\-\d{2}\-\d{2}/',
            'second_period' => 'regex:/\d{4}\-\d{2}\-\d{2} \- \d{4}\-\d{2}\-\d{2}/',
            'url' => 'required|url',
        ]);
        $user = Auth::user();
        $ga_site_id = $request->session()->get("ga_site_id", 1);
        $gaSite = GoogleAnalyticsSite::findOrFail($ga_site_id);
        $gscSite = GoogleGscSite::where("domain", $gaSite->domain)->first();
        $site_id = $gscSite->id;

        $url = $request->get("url");

        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setSite($site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();

        if ($request->has("first_period") && $request->has("second_period")) {
            list($firstPeriod, $secondPeriod) = $this->getPeriodsFromRequest($request);
        } else {
            list($firstPeriod, $secondPeriod) = $this->getDefaultPeriods($minDate, $maxDate);
        }
        if ($firstPeriod === false) {
            return back()->withFail("Период задан не верно или недостаточно данных для анализа");
        }
        $data = $clickHouse->getKeywordsChangesData($url, $firstPeriod, $secondPeriod, "clicks");
        $grownData = array();
        $downData = array();
        $stableData = array();
        $totalData = 0;
        $totalGrown = 0;
        $countGrown = 0;
        $totalDown = 0;
        $countDown = 0;
        $countStable = 0;
        foreach ($data as $row) {
            $totalData += $row["data"];
            if ($row["result"] > 0) {
                $grownData[] = $row;
                $totalGrown +=  $row["result"];
                $countGrown++;
            } elseif ($row["result"] < 0) {
                $downData[] = $row;
                $totalDown +=  $row["result"];
                $countDown++;
            } else {
                $stableData[] = $row;
                $countStable++;
            }
        }
        uasort($grownData, function ($a, $b) {
            return ($b['result'] - $a['result']);
        });
        uasort($downData, function ($a, $b) {
            return ($a['result'] - $b['result']);
        });
        uasort($stableData, function ($a, $b) {
            return ($b['data'] - $a['data']);
        });
        return view("changes.keywords")
            ->with("totalData", $totalData)
            ->with("stableData", $stableData)
            ->with("grownData", $grownData)
            ->with("downData", $downData)
            ->with("totalGrown", $totalGrown)
            ->with("totalDown", $totalDown)
            ->with("countGrown", $countGrown)
            ->with("countDown", $countDown)
            ->with("countStable", $countStable);
    }

    private function getPeriodsFromRequest($request)
    {
        $fPeriodString = $request->get("first_period");
        $sPeriodString = $request->get("second_period");
        $fPeriodData = explode(" - ", $fPeriodString);
        $sPeriodData = explode(" - ", $sPeriodString);
        if (count($sPeriodData) < 2 || count($fPeriodData) < 2) {
            return array(false, false);
        }
        $firstPeriod = array(
                "startDate" => $fPeriodData[0],
                "endDate" => $fPeriodData[1],
            );
        $secondPeriod = array(
                "startDate" => $sPeriodData[0],
                "endDate" => $sPeriodData[1],
            );
        return array($firstPeriod, $secondPeriod);
    }

    private function getDefaultPeriods($min_date, $max_date)
    {
        $minDate =  DateTime::createFromFormat('Y-m-d', $min_date);
        $maxDate =  DateTime::createFromFormat('Y-m-d', $max_date);
        if (($maxDate->diff($minDate)->format("%a")) > 60) {
            $fEndDate = $maxDate->format("Y-m-d");
            $maxDate->modify("-30 days");
            $fStartDate = $maxDate->format("Y-m-d");
            $sEndDate = $maxDate->format("Y-m-d");
            $maxDate->modify("-30 days");
            $sStartDate = $maxDate->format("Y-m-d");
            return array(
                array("startDate" => $sStartDate, "endDate" => $sEndDate),
                array("startDate" => $fStartDate, "endDate" => $fEndDate),
            );
        } elseif (($maxDate->diff($minDate)->format("%a")) > 14) {
            $fEndDate = $maxDate->format("Y-m-d");
            $maxDate->modify("-7 days");
            $fStartDate = $maxDate->format("Y-m-d");
            $sEndDate = $maxDate->format("Y-m-d");
            $maxDate->modify("-7 days");
            $sStartDate = $maxDate->format("Y-m-d");
            return array(
                array("startDate" => $sStartDate, "endDate" => $sEndDate),
                array("startDate" => $fStartDate, "endDate" => $fEndDate),
            );
        } else {
            $nowDate = (new DateTime)->format("Y-m-d");
            return array(
                array("startDate" => $nowDate, "endDate" => $nowDate),
                array("startDate" => $nowDate, "endDate" => $nowDate)
            );
        }
    }
}
