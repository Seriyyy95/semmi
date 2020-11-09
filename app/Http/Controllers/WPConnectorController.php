<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Seriyyy95\WPConnector\Connector as WPConnector;

use App\GoogleAnalyticsSite;
use App\OptionsManager;
use App\WPBinding;
use App\WPUrl;

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
        $gaSites = GoogleAnalyticsSite::where("user_id", $user_id)->get();
        $bindings = WPBinding::where("user_id", $user_id)->get()->pluck("ga_site", "site_id")->toArray();
        return view("wpconnector")
            ->with("gaSites", $gaSites)
            ->with("bindings", $bindings)
            ->with("sites", $sites)
            ->with("wpconnectorKey", $wpConnKey);
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_id' => 'required|integer',
        ]);

        $user_id = Auth::user()->id;
        $site_id = $request->get("site_id");
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
        $urls = $wpConnector->site($site_id)->listPosts();
        foreach ($urls as $url) {
            $count = WPUrl::where("url", $url["post_url"])
                ->where("user_id", $user_id)
                ->where("site_id", $site_id)
                ->count();
            if ($count == 0) {
                $urlParts = parse_url(trim($url["post_url"]));
                $domain = $urlParts["scheme"] . "://". $urlParts["host"]. "/";
                if (substr($url["post_url"], -1) == '/') {
                    $link = substr($url["post_url"], 0, -1);
                } else {
                    $link = $url["post_url"];
                }

                $newUrl = new WPUrl();
                $newUrl->url = $link;
                $newUrl->title = $url["title"];
                $newUrl->domain = $domain;
                $newUrl->publish_date = $url["publish_date"];
                $newUrl->last_modified = $url["last_modified"];
                $newUrl->site_id = $site_id;
                $newUrl->user_id = $user_id;
                $newUrl->save();
            }
        }
        return back()->withSuccess("Ссылки сайта обновлено успешно");
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

        WPBinding::where("user_id", $user_id)
            ->where("site_id", $site_id)
            ->delete();
        $newBinding = new WPBinding();
        $newBinding->site_id = $site_id;
        $newBinding->ga_site = $ga_site;
        $newBinding->user_id = $user_id;
        $newBinding->save();
        return back()->withSuccess("Связь успешно добавлена!");
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
}
