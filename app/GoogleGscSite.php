<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoogleGscSite extends GoogleSite
{

    public function __construct(){
        parent::__construct();
    }

    public function hasActiveTasks(){
        $tasks = GscTask::where("site_id", $this->id)
            ->where("user_id", $this->user_id)
            ->where("status", "active")
            ->count();
        if($tasks > 0){
            return true;
        }else{
            return false;
        }
    }

}
