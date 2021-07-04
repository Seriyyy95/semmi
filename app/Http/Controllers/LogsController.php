<?php

namespace App\Http\Controllers;

use App\Services\Logger\LoadLogger;

/**
 * Class LogsController
 * @package App\Http\Controllers
 */
class LogsController extends Controller
{

    /**
     * LogsController constructor.
     */
    public function __construct()
    {
        $this->middleware("auth");
    }

    /**
     * @param $source
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index($source)
    {
        $logger = new LoadLogger($source);
        $logs = $logger->list();
        return view("logs.index")
            ->with("logs", $logs);
    }
}
