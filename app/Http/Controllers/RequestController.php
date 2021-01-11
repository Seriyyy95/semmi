<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Seriyyy95\WPConnector\Connector as WPConnector;

use App\ClickHouse;
use App\GoogleAnalyticsSite;
use App\GoogleGscSite;

class RequestController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
        $this->middleware("demo");
    }

    public function index(Request $request)
    {
        $user_id = Auth::user()->id;
        $gaSites = GoogleAnalyticsSite::where("user_id", $user_id)->get();
        $gscSites = GoogleGscSite::where("user_id", $user_id)->get();

        return view("request.index")
            ->with("gaSites", $gaSites)
            ->with("gscSites", $gscSites);
    }

    public function execute(Request $request)
    {
        $request->validate([
            'query' => 'required',
        ]);
        $user_id = Auth::user();
        $query = $request->get("query");
        try {
            $rows = ClickHouse::execute($query);
            return response()->json(array(
                "data" => $rows,
                "error" => "",
            ));
        } catch (\Exception $e) {
            return response()->json(array(
                "data" => array(),
                "error" => $e->getMessage(),
            ));
        }
    }
}
