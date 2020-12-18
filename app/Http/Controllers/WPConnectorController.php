<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Seriyyy95\WPConnector\Connector as WPConnector;

use App\GoogleAnalyticsSite;
use App\OptionsManager;
use App\WPBinding;
use App\WPUrl;
use App\WpSite;
use App\Jobs\WpLoadJob;

class WPConnectorController extends Controller
{
    public function index()
    {
        $user_id = Auth::user()->id;
        $optionsManager = new OptionsManager();
        $optionsManager->setUser($user_id);
        $wpConnKey = $optionsManager->getValue("wpconnector_key");
        $wpConnUrl = env("WP_CONNECTOR_URL");
        if ($wpConnKey != null && $wpConnUrl != null) {
            $wpConnector = new WPConnector();
            $wpConnector->setDomain($wpConnUrl);
            $wpConnector->setApiKey($wpConnKey);
            $sites = $wpConnector->sites();
        } else {
            $sites = array();
        }
        foreach ($sites as $wpId => $site) {
            $count = WpSite::where("user_id", $user_id)->where("domain", $site)->count();
            if ($count == 0) {
                $wpSite = new WpSite();
                $wpSite->user_id = $user_id;
                $wpSite->domain = $site;
                $wpSite->site_id = $wpId;
                $wpSite->save();
            }
        }
        $gaSites = GoogleAnalyticsSite::where("user_id", $user_id)->get();
        $wpSites = WpSite::where("user_id", $user_id)->get();
        return view("wpconnector")
            ->with("gaSites", $gaSites)
            ->with("sites", $wpSites)
            ->with("wpconnectorKey", $wpConnKey);
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_id' => 'required|integer',
        ]);

        $user_id = Auth::user()->id;
        $site_id = $request->get("site_id");
        $wpSite = WpSite::findOrFail($site_id);
        $wpId = $wpSite->site_id;

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
        $urls = $wpConnector->site($wpId)->listPosts();

        foreach ($urls as $data) {
            $urlParts = parse_url(trim($data["post_url"]));
            $domain = $urlParts["scheme"] . "://". $urlParts["host"]. "/";
            if (substr($data["post_url"], -1) == '/') {
                $link = substr($data["post_url"], 0, -1);
            } else {
                $link = $data["post_url"];
            }

            $oldUrl = WPUrl::where("url", $link)
                ->where("user_id", $user_id)
                ->where("site_id", $site_id)
                ->get()->first();

            if ($oldUrl == null) {
                $newUrl = new WPUrl();
                $newUrl->url = $link;
                $newUrl->title = $data["title"];
                $newUrl->domain = $domain;
                $newUrl->publish_date = $data["publish_date"];
                $newUrl->last_modified = $data["last_modified"];
                $newUrl->site_id = $site_id;
                $newUrl->user_id = $user_id;
                $newUrl->status = "active";
                $newUrl->save();
                $wpSite->count++;
                $wpSite->save();
                WpLoadJob::dispatch($newUrl, $wpSite, $data["id"])
                    ->onConnection('database')
                    ->onQueue("gsc_data");
            } else {
                $oldUrl->status = "active";
                $oldUrl->save();
                WpLoadJob::dispatch($oldUrl, $wpSite, $data["id"])
                    ->onConnection('database')
                    ->onQueue("gsc_data");
            }
        }
        return back()->withSuccess("Ссылки сайта добавлены в очередь на обработку");
    }

    public function bind(Request $request)
    {
        $request->validate([
            'ga_site' => 'required|integer',
            'site_id' => 'required|integer',
        ]);

        $user_id = Auth::user()->id;
        $ga_site = $request->get("ga_site");
        $site_id = $request->get("site_id");

        $wpSite = WpSite::where("user_id", $user_id)
            ->where("site_id", $site_id)
            ->get()->first();
        if ($wpSite == null) {
            return back()->withFail("Такого сайта не существует");
        }
        $wpSite->ga_site_id = $ga_site;
        $wpSite->save();
        return back()->withSuccess("Связь успешно добавлена!");
    }

    public function savePrice(Request $request)
    {
        $request->validate([
            'price' => 'required|numeric',
            'site_id' => 'required|integer',
        ]);

        $user_id = Auth::user()->id;
        $price = $request->get("price");
        $site_id = $request->get("site_id");

        $wpSite = WpSite::where("user_id", $user_id)
            ->where("site_id", $site_id)
            ->get()->first();
        if ($wpSite == null) {
            return back()->withFail("Такого сайта не существует");
        }

        $wpSite->price = $price;
        $wpSite->save();
        return back()->withSuccess("Цена успешно добавлена!");
    }

    public function save(Request $request)
    {
        $user_id = Auth::user()->id;
        $optionsManager = new OptionsManager();
        $optionsManager->setUser($user_id);
        $wpConnKey = $request->get("wpconnector_key");
        $optionsManager->setValue("wpconnector_key", $wpConnKey);
        return back()->withSuccess("Настройки сохранены успешно");
    }

    public function status(Request $request)
    {
        $user_id = Auth::user()->id;
        $site_id = $request->get("site_id");
        $tasksCount = WPUrl::where("user_id", $user_id)
            ->where("site_id", $site_id)
            ->where("status", "active")
            ->count();
        if ($tasksCount > 0) {
            return response()->json(array("status" => "progress"));
        } else {
            return response()->json(array("status" => "finished"));
        }
    }
}
