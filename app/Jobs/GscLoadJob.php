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
use App\LoadLogger;

class GscLoadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    public $timout = 0;
    public $gscTask;
    private $loadLogger;

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
        $date = $this->gscTask->date;
        $user_id = $this->gscTask->user_id;
        $site_id = $this->gscTask->site_id;
        $currentRow = $this->gscTask->offset;
        $gscSite = GoogleGscSite::findOrFail($this->gscTask->site_id);

        $this->loadLogger = new LoadLogger($user_id, "gsc");

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
            $stats = $this->updateParsent($gscSite);

            $this->loadLogger->write("Загрузка данных сайта {$gscSite->domain} для даты {$this->gscTask->date} завершена");
            $this->loadLogger->write("Всего задач в очереди: {$stats['total']}, завершено: {$stats['finalized']}, процент: {$stats['parsent']}");
        } else {
            $this->loadLogger->write("Загрузка данных сайта {$gscSite->domain} для даты " . $this->gscTask->date . " пропущена");
        }
    }

    public function updateParsent($gscSite)
    {
        $finalized = GscTask::where("user_id", $gscSite->user_id)
            ->where("site_id", $gscSite->id)
            ->where("status", "!=", "active")
            ->where("status", "!=", "disabled")
            ->where("id", ">", $gscSite->last_task_id)
            ->count();
        $max = GscTask::selectRaw("MAX(id) as id")
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
        $this->gscTask->status = "failed";
        $this->gscTask->save();
    }

    public function clearUrl($url)
    {
        $scheme = parse_url($url);
        $url = $scheme["scheme"] . "://" . $scheme["host"] . $scheme["path"];
        if (strpos($url, "/?s=")) {
            $url = $scheme["host"] . "://search";
        } elseif (strpos($url, ".jpg") || strpos($url, ".jpeg") || strpos($url, ".png") || strpos($url, ".gif")) {
            $url = $scheme["host"] . "://attachment";
        } elseif (strpos($url, " ")) {
            $url = substr($url, 0, strpos($url, " "));
        }
        $url = rtrim($url, "/");
        if ($this->endsWith($url, "amp")) {
            $url = rtrim($url, "amp");
        }
        return $url;
    }

    public function clearKeyword($keyword)
    {
        return mb_strtolower(preg_replace("/[^,\-\s\d\p{Cyrillic}\p{Latin}]/ui", '', $keyword));
    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
}
