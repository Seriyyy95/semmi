<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GoogleAnalyticsSite;

class StatsController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
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
}
