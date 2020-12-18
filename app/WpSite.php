<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WpSite extends Model
{
    public function hasActiveTasks()
    {
        $tasksCount = WPUrl::where("user_id", $this->user_id)
            ->where("site_id", $this->id)
            ->where("status", "active")
            ->count();
        if ($tasksCount > 0) {
            return true;
        } else {
            return false;
        }
    }
}
