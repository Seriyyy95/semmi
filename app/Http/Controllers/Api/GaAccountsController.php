<?php


namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\LoadLogger;
use App\Models\GoogleAnalyticsSite;
use App\Models\GoogleGscSite;
use App\Services\GoogleAnalyticsLoadService;
use App\Services\GscLoadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class GaAccountsController
 * @package App\Http\Controllers\Api
 */
class GaAccountsController extends AccountsController
{

    public function __construct(GoogleAnalyticsLoadService $service){
        parent::__construct(GoogleAnalyticsSite::class, $service);
    }
}
