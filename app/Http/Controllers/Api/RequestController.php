<?php


namespace App\Http\Controllers\Api;


use App\ClickHouse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestController extends Controller
{

    public function __construct(){
       $this->middleware('api:auth');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function execute(Request $request) : JsonResponse
    {
        $request->validate([
            'query' => 'required',
        ]);
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
