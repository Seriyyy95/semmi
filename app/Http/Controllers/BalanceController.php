<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Seriyyy95\WPConnector\Connector as WPConnector;

use App\ClickHouseViews;
use App\GoogleAnalyticsSite;
use App\WPUrl;
use App\WpSite;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $user_id = Auth::user()->id;
        $site_id = $request->session()->get("ga_site_id", 1);
        $sites = GoogleAnalyticsSite::where("user_id", $user_id)->get();
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user_id);
        $clickHouse->setSite($site_id);
        $urls = $clickHouse->getUrls();

        return view("balance.index")
            ->with("urls", $urls)
            ->with("sites", $sites)
            ->with("site_id", $site_id);
    }

    public function urlInfo(Request $request)
    {
        $request->validate([
            'url' => 'required',
        ]);
        $user_id = Auth::user()->id;
        $site_id = $request->session()->get("ga_site_id", 1);
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user_id);
        $clickHouse->setSite($site_id);

        $url = $request->get("url");

        if ($url !== "all") {
            $totalRevenue = $clickHouse->getUrlRevenue($url);
            $avgRevenue = $clickHouse->getAvgRevenue($url);
            $totalPageviews = $clickHouse->getUrlPageviews($url);
            $avgPageviews = $clickHouse->getAvgPageviews($url);
            $firstDate = $clickHouse->getFirstUrlDate($url);

            $data = array(
                "url" => $url,
                "revenue" => $totalRevenue,
                "avg_revenue" => $avgRevenue,
                "pageviews" => $totalPageviews,
                "avg_pageviews" => $avgPageviews,
                "first_date" => $firstDate,
            );
        } else {
            $totalRevenue = $clickHouse->getTotalRevenue();
            $avgRevenue = $clickHouse->getTotalAvgRevenue();
            $totalPageviews = $clickHouse->getTotalPageviews();
            $avgPageviews = $clickHouse->getTotalAvgPageviews();

            $data = array(
                "id" => "-1",
                "url" => "Все данные",
                "revenue" => $totalRevenue,
                "avg_revenue" => $avgRevenue,
                "pageviews" => $totalPageviews,
                "avg_pageviews" => $avgPageviews,
            );
        }
        return response()->json($data);
    }
}
