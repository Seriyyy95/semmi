<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\GATask;
use App\GoogleAnalyticsSite;
use App\OptionsManager;
use App\GoogleDataLoader;
use App\ClickHouseViews;

class GaLoadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    public $timout = 0;
    public $gaTask;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GATask $gaTask)
    {
        $this->gaTask = $gaTask;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->gaTask->status != "disabled") {
            $date = $this->gaTask->date;
            $user_id = $this->gaTask->user_id;
            $site_id = $this->gaTask->site_id;

            $optionsManager = new OptionsManager();
            $optionsManager->setUser($user_id);

            $gloader = new GoogleDataLoader($optionsManager);
            $gloader->setUser($user_id);

            $clickHouse = ClickHouseViews::getInstance();
            $clickHouse->setUser($user_id);
            $clickHouse->setSite($site_id);

            $gaSite = GoogleAnalyticsSite::findOrFail($this->gaTask->site_id);

            $resultData = $gloader->getAPI()->getAnalyticsData($gaSite->profile_id, $date, $gaSite->domain);
            \Log::info(print_r($resultData, true));
            $count = count($resultData);
            $clickHouse->index($resultData);
            $this->gaTask->offset = $count;
            $this->gaTask->save();
            $this->gaTask->status = "finished";
            $this->gaTask->save();
            $this->updateParsent($gaSite);
        }
    }

    public function updateParsent($gscSite)
    {
        $finalized = GaTask::where("user_id", $gscSite->user_id)
            ->where("site_id", $gscSite->id)
            ->where("status", "!=", "active")
            ->where("status", "!=", "disabled")
            ->where("id", ">", $gscSite->last_task_id)
            ->count();
        \Log::info("Finalized: $finalized");
        $max = GaTask::selectRaw("MAX(id) as id")
            ->where("user_id", $gscSite->user_id)
            ->where("site_id", $gscSite->id)
            ->first()->id;
        $total = $max - $gscSite->last_task_id;
        \Log::info("total: $total");
        $gscSite->parsent = $finalized * 100 / $total;
        $gscSite->save();
    }


    public function failed()
    {
        $this->gaTask->status = "failed";
        $this->gaTask->save();
    }
}
