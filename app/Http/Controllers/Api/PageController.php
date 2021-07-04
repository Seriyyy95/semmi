<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\ClickHousePositions;
use App\ClickHouseViews;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetUrlCalendarRequest;
use App\Http\Requests\GetUrlKeywordsRequest;
use App\Models\GoogleAnalyticsSite;
use App\Models\GoogleGscSite;
use App\Services\PeriodsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Psy\Util\Json;

/**
 * Class PageController
 * @package App\Http\Controllers\Api
 */
class PageController extends Controller
{

    private PeriodsService $periodsService;

    /**
     * PageController constructor.
     * @param PeriodsService $service
     */
    public function __construct(PeriodsService $service){
        $this->periodsService = $service;
        $this->middleware('auth:api');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUrlCalendar(GetUrlCalendarRequest $request) : JsonResponse
    {
        $data = $request->validated();

        $site_id = $data['site_id'];
        $url = $data['url'];
        $field = $data['field'];
        $aggFunc = $data['agg_function'];

        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setSite((int)$site_id);
        $minDate = $clickHouse->getMinDate();
        $maxDate = $clickHouse->getMaxDate();
        $periods = $this->periodsService->getPeriods($minDate, $maxDate, "month", true);
        $data = $clickHouse->getHistoryData($periods, $url, $field, $aggFunc);

        return response()->json($data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getUrlKeywords(GetUrlKeywordsRequest $request) : JsonResponse
    {
        $data = $request->validated();

        $url = $data["url"];
        $ga_site_id = $data["ga_site_id"];

        $gaSite = GoogleAnalyticsSite::findOrFail($ga_site_id);
        $gscSite = GoogleGscSite::where("domain", $gaSite->domain)->first();
        if ($gscSite == null) {
            return response()->json(array());
        }
        $site_id = $gscSite->id;

        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setSite((int)$site_id);
        $keywords = $clickHouse->getAllUrlKeywords($url);

        return response()->json($keywords);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function getUrlGraph(GetUrlKeywordsRequest $request) : JsonResponse
    {
        $data = $request->validated();

        $url = $data["url"];
        $ga_site_id = $data["ga_site_id"];

        $gaSite = GoogleAnalyticsSite::findOrFail($ga_site_id);
        $gscSite = GoogleGscSite::where("domain", $gaSite->domain)->first();
        if ($gscSite == null) {
            return response()->json(array());
        }
        $site_id = $gscSite->id;

        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setSite((int) $site_id);
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

}
