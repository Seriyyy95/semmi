<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Seriyyy95\WPConnector\Connector as WPConnector;

use App\ClickHouse;
use App\Models\GoogleAnalyticsSite;
use App\Models\GoogleGscSite;

class RequestController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
        $this->middleware("demo");
    }

    public function index(Request $request)
    {
        $gaSites = GoogleAnalyticsSite::all();
        $gscSites = GoogleGscSite::all();

        return view("request.index")
            ->with("gaSites", $gaSites)
            ->with("gscSites", $gscSites);
    }

}
