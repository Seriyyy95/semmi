<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Seriyyy95\WPConnector\Connector as WPConnector;

use App\ClickHouseViews;
use App\OptionsManager;
use App\WPBinding;
use App\WPUrl;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $user_id = Auth::user()->id;
        $site_id = $request->session()->get("wp_site_id", 1);
        $optionsManager = new OptionsManager();
        $optionsManager->setUser($user_id);
        $wpConnKey = $optionsManager->getValue("wpconnector_key");
        $wpConnUrl = env("WP_CONNECTOR_URL");
        if ($wpConnKey == null || $wpConnUrl == null) {
            return redirect(route('wpconnector.index'))->withFail("WPConnector не настроен");
        }
        $wpConnector = new WPConnector();
        $wpConnector->setDomain($wpConnUrl);
        $wpConnector->setApiKey($wpConnKey);
        $sites = $wpConnector->sites();
        $loadedSites = WPUrl::select("site_id", "domain")
            ->where("user_id", $user_id)
            ->groupBy("domain", "site_id")->get();
        $sitesArray = array();
        foreach ($sites as $id=>$domain) {
            $sitesArray[] = new class($id, $domain) {
                public $id;
                public $domain;

                public function __construct($id, $domain)
                {
                    $this->id = $id;
                    $this->domain = $domain;
                }
            };
        }
        $dbUrls = WPUrl::where("user_id", $user_id)
            ->where("site_id", $site_id)
            ->orderBy("last_modified", "DESC")
            ->get();
        $dataUrls = array();
        $dataUrls[] = array("url" => "all", "title" => "Все данные");
        foreach ($dbUrls as $url) {
            $dataUrls[] = array(
                "url" => $url->url,
                "title" => $url->title,
            );
        }
        return view("balance.index")
            ->with("urls", $dataUrls)
            ->with("sites", $loadedSites)
            ->with("site_id", $site_id);
    }

    public function urlInfo(Request $request)
    {
        $request->validate([
            'url' => 'required',
        ]);
        $user_id = Auth::user()->id;
        $site_id = $request->session()->get("wp_site_id", 1);
        $siteBinding = WPBinding::where("user_id", $user_id)
            ->where("site_id", $site_id)->get()->first();
        if ($siteBinding == null) {
            return response()->json(array("error" => "Google Analytics profile not binded!"));
        }
        $ga_site_id = $siteBinding["ga_site"];
        $optionsManager = new OptionsManager();
        $optionsManager->setUser($user_id);
        $wpConnKey = $optionsManager->getValue("wpconnector_key");
        $wpConnUrl = env("WP_CONNECTOR_URL");
        if ($wpConnKey == null || $wpConnUrl == null) {
            return response()->json(array("error" => "WPConnector is not configured!"));
        }
        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setUser($user_id);
        $clickHouse->setSite($ga_site_id);

        $url = $request->get("url");

        if ($url !== "all") {
            $urlData = WPUrl::where("url", $url)
                ->where("user_id", $user_id)
                ->where("site_id", $site_id)
                ->get()->first();
            $totalRevenue = $clickHouse->getUrlRevenue($url);
            $avgRevenue = $clickHouse->getAvgRevenue($url);
            $data = array(
                "id" => $urlData->id,
                "url" => $urlData->url,
                "title" => $urlData->title,
                "price" => $urlData->price,
                "revenue" => $totalRevenue,
                "avg_revenue" => $avgRevenue,
            );
        } else {
            $urlData = WPUrl::selectRaw("SUM(price) as price")
                ->where("user_id", $user_id)
                ->where("site_id", $site_id)
                ->get()->first();
            $totalRevenue = $clickHouse->getTotalRevenue();
            $avgRevenue = $clickHouse->getTotalAvgRevenue();
            $data = array(
                "id" => "-1",
                "url" => "Все данные",
                "title" => "Всего",
                "price" => $urlData->price,
                "revenue" => $totalRevenue,
                "avg_revenue" => $avgRevenue,
            );
        }
        return response()->json($data);
    }

    public function updateItem(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'price' => 'required|string'
        ]);
        $url_id = $request->get("id");
        $price = $request->get("price");
        $wpUrl = WPUrl::find($url_id);
        if ($wpUrl == null) {
            return response()->json(array("error" => "Can not find url with id $url_id"));
        }
        $wpUrl->price = $price;
        $wpUrl->save();
        return response()->json(array("success" => true));
    }

    public function import()
    {
        return view('balance.import');
    }

    public function upload(Request $request)
    {
        $user_id = Auth::user()->id;
        $site_id = $request->session()->get("wp_site_id", 1);

        $file = $request->post_file;
        $fileName = "uploaded-csv-" . time() . "." . $file->getClientOriginalExtension();

        $tmpDir = "/tmp";
        $filePath = $tmpDir . "/" . $fileName;
        $file->move($tmpDir, $fileName);

        $csvFile = fopen($filePath, 'r');

        $count = 0;

        while (($data = fgetcsv($csvFile, 1000, ",")) !== false) {
            $url = trim($data[0]);
            $price = trim($data[1]);
            $price = str_replace(",", ".", $price);

            if (strlen($url) > 0) {
                $wpUrl = WPUrl::where("url", $url)
                    ->where("user_id", $user_id)
                    ->get()->first();
                if ($wpUrl !== null) {
                    $count++;
                    $wpUrl->price = round($price, 2);
                    $wpUrl->save();
                }
            }
        }

        return back()->withSuccess("Импортировано $count строк данных");
    }
}
