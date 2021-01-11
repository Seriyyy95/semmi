<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\LoadLogger;

class LogsController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    public function index($source)
    {
        $user_id = Auth::user()->id;
        $logger = new LoadLogger($user_id, $source);
        $logs = $logger->list();
        return view("logs.index")
            ->with("logs", $logs);
    }
}
