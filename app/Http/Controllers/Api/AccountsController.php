<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleAnalyticsLoadService;
use App\Services\LoadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class GaAccountsController
 * @package App\Http\Controllers\Api
 */
class AccountsController extends Controller
{

    protected string $modelName;
    protected LoadService $service;

    public function __construct(string $siteModel, LoadService $service)
    {
       $this->middleware('auth:api');

       $this->modelName = $siteModel;
       $this->service = $service;
    }

    /**
     * @param Request $request
     * @param GoogleAnalyticsLoadService $service
     * @param int $id
     * @return JsonResponse
     */
    public function load(
        Request $request,
        int $id
    ) : JsonResponse {
        $modelName = $this->modelName;

        $site = $modelName::findOrFail($id);

        if ($request->has("last_task_id") && $request->get("last_task_id") > 0) {
            $lastTaskId = (int) $request->get("last_task_id");
        } else {
            $lastTaskId = null;
        }

        $result = $this->service->addNextDates($site, $lastTaskId);

        return new JsonResponse(array(
            "last_task_id" => $result->lastTaskId,
            "count" => $result->count,
        ), 200);
    }

    public function status($id)
    {
        $modelName = $this->modelName;

        $site = $modelName::findOrFail($id);

        $result = array(
            "parsent" => $site->parsent,
            "site_id" => $id
        );
        return new JsonResponse($result, 200);
    }

    public function autoload(int $id, string $state)
    {
        $modelName = $this->modelName;

        $site = $modelName::findOrFail($id);
        if ($state == "enabled") {
            $site->autoload = 1;
        } elseif ($state == "disabled") {
            $site->autoload = 0;
        } else {
            throw new \Exception("Invalid state exception");
        }
        $site->saveOrFail();

        return new JsonResponse(array("status" => "success"), 200);
    }
}
