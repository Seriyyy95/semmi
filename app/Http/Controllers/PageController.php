<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\ClickHousePositions;
use App\Models\GoogleGscSite;
use App\Services\PeriodsService;
use Illuminate\Http\Request;
use App\Models\GoogleAnalyticsSite;
use App\ClickHouseViews;

/**
 * Class PageController
 * @package App\Http\Controllers
 */
class PageController extends Controller
{

    private PeriodsService $periodsService;

    /**
     * PageController constructor.
     * @param PeriodsService $periodsService
     */
    public function __construct(PeriodsService $periodsService)
    {
        $this->periodsService = $periodsService;
        $this->middleware("auth");
    }

    public function index(Request $request)
    {
        $site_id = $request->session()->get("ga_site_id", 1);
        $sites = GoogleAnalyticsSite::all();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setSite((int)$site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->periodsService->getPeriods($minDate, $maxDate, "month", true);
        $periodsMetadata = $this->periodsService->getPeriodsMetadata($periods);
        $urls = $clickHouse->getUrls();

        return view("page.index")
            ->with("periods", $periods)
            ->with("periodsMetadata", $periodsMetadata)
            ->with("urls", $urls)
            ->with("site_id", $site_id)
            ->with("sites", $sites)
            ->with("startUrl", $request->get("url", ""));
    }

    public function keywordGraph(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
        ]);
        $keyword = $request->get("keyword");

        $ga_site_id = $request->session()->get("ga_site_id", 1);
        $gaSite = GoogleAnalyticsSite::findOrFail($ga_site_id);
        $gscSite = GoogleGscSite::where("domain", $gaSite->domain)->first();
        if ($gscSite == null) {
            return response()->json(array());
        }
        $site_id = $gscSite->id;

        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setSite((int)$site_id);
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
}
