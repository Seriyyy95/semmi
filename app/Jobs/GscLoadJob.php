<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\GscTask;
use App\GoogleGscSite;
use App\OptionsManager;
use App\GoogleDataLoader;
use App\ClickHousePositions;

class GscLoadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    public $timout = 0;
    public $gscTask;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GscTask $gscTask)
    {
        $this->gscTask = $gscTask;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->gscTask->status != "disabled") {
            $date = $this->gscTask->date;
            $user_id = $this->gscTask->user_id;
            $site_id = $this->gscTask->site_id;
            $currentRow = $this->gscTask->offset;

            $optionsManager = new OptionsManager();
            $optionsManager->setUser($user_id);

            $gloader = new GoogleDataLoader($optionsManager);
            $gloader->setUser($user_id);

            $clickHouse = ClickHousePositions::getInstance();
            $clickHouse->setUser($user_id);
            $clickHouse->setSite($site_id);

            $gscSite = GoogleGscSite::findOrFail($this->gscTask->site_id);

            do {
                $results = $gloader->getAPI()->getGSCData($gscSite->domain, $date, $currentRow);
                $count = count($results);
                $resultData = array();
                foreach ($results as $result) {
                    if (strlen($result->keys[2]) < 150) {
                        $resultData[] = array(
                            "date" => $result->keys[0],
                            "url" => $this->clearUrl($result->keys[1]),
                            "domain" => $gscSite->domain,
                            "keyword" => $this->clearKeyword($result->keys[2]),
                            "avg_position" => round($result->position, 2),
                            "avg_ctr" => round($result->ctr, 2),
                            "impressions" => $result->impressions,
                            "clicks" => $result->clicks,
                        );
                    }
                }
                $clickHouse->index($resultData);
                $currentRow+=count($results);
                $this->gscTask->offset = $currentRow;
                $this->gscTask->save();
            } while ($count >= 4999);
            $this->gscTask->status = "finished";
            $this->gscTask->save();
            $this->updateParsent($gscSite);
        }
    }

    public function updateParsent($gscSite)
    {
        $active = GscTask::where("user_id", $gscSite->user_id)
            ->where("site_id", $gscSite->id)
            ->where("status", "!=", "active")
            ->where("status", "!=", "disabled")
            ->where("id", ">", $gscSite->last_task_id)
            ->count();
        \Log::info("active: $active");
        $max = GscTask::selectRaw("MAX(id) as id")
            ->where("user_id", $gscSite->user_id)
            ->where("site_id", $gscSite->id)
            ->first()->id;
        $total = $max - $gscSite->last_task_id;
        \Log::info("total: $total");
        $gscSite->parsent = $active * 100 / $total;
        $gscSite->save();
    }


    public function failed()
    {
        $this->gscTask->status = "failed";
        $this->gscTask->save();
    }

    public function clearUrl($url)
    {
        $scheme = parse_url($url);
        $url = $scheme["scheme"] . "://" . $scheme["host"] . $scheme["path"];
        $url = rtrim($url, "/");
        $url = rtrim($url, "amp/");
        $url = rtrim($url, "amp");
        return $url;
    }

    public function clearKeyword($keyword)
    {
        return mb_strtolower(preg_replace("/[^,\-\s\d\p{Cyrillic}\p{Latin}]/ui", '', $keyword));
    }
}
