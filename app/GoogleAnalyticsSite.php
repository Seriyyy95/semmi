<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GoogleAnalyticsSite extends GoogleSite
{
    public function hasActiveTasks()
    {
        $tasks = GATask::where("site_id", $this->id)
            ->where("user_id", $this->user_id)
            ->where("status", "active")
            ->count();
        if ($tasks > 0) {
            return true;
        } else {
            return false;
        }
    }
}
