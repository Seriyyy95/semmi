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
use App\LoadLogger;

class GaLoadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    public $timout = 0;
    public $gaTask;
    private $loadLogger = null;

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
        $user_id = $this->gaTask->user_id;
        $site_id = $this->gaTask->site_id;
        $gaSite = GoogleAnalyticsSite::findOrFail($this->gaTask->site_id);

        $this->loadLogger = new LoadLogger($user_id, "ga");

        if ($this->gaTask->status != "disabled") {
            $date = $this->gaTask->date;
            $optionsManager = new OptionsManager();
            $optionsManager->setUser($user_id);

            $gloader = new GoogleDataLoader($optionsManager);
            $gloader->setUser($user_id);

            $clickHouse = ClickHouseViews::getInstance();
            $clickHouse->setUser($user_id);
            $clickHouse->setSite($site_id);


            $resultData = $gloader->getAPI()->getAnalyticsData($gaSite->profile_id, $date, $gaSite->domain);
            $count = count($resultData);
            $clickHouse->index($resultData);
            $this->gaTask->offset = $count;
            $this->gaTask->save();
            $this->gaTask->status = "finished";
            $this->gaTask->save();
            $stats = $this->updateParsent($gaSite);
            $this->loadLogger->write("Загрузка данных сайта {$gaSite->domain} для даты {$this->gaTask->date} завершена");
            $this->loadLogger->write("Всего задач в очереди: {$stats['total']}, завершено: {$stats['finalized']}, процент: {$stats['parsent']}");
        } else {
            $this->loadLogger->write("Загрузка данных сайта {$gaSite->domain} для даты " . $this->gaTask->date . " пропущена");
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
        $max = GaTask::selectRaw("MAX(id) as id")
            ->where("user_id", $gscSite->user_id)
            ->where("site_id", $gscSite->id)
            ->first()->id;
        $total = $max - $gscSite->last_task_id;
        $gscSite->parsent = $finalized * 100 / $total;
        $gscSite->save();

        return array(
            "total" => $total,
            "finalized" => $finalized,
            "parsent" => round($gscSite->parsent, 2),
        );
    }


    public function failed(\Exception $e)
    {
        $this->loadLogger->write("При выполнении задачи {$this->gaTask->id} произошла ошибка: " . $e->getMessage());
        $this->gaTask->status = "failed";
        $this->gaTask->save();
    }
}
