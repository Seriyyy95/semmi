<?php

namespace App\Services;

use App\ClickHousePositions;
use App\GoogleDataLoader;
use App\Jobs\GscLoadJob;
use App\Models\GoogleGscSite;
use App\Models\Task;
use App\OptionsManager;
use App\Repositories\GscTaskRepository;
use App\Services\Clients\GoogleClient;
use App\Services\Logger\GscLoadLogger;
use ClickHouseDB\Client;
use Illuminate\Support\Str;

class GscLoadService extends LoadService
{

    protected GoogleClient $client;

    /**
     * GscLoadService constructor.
     * @param GscTaskRepository $tasksRepo
     */
    public function __construct(GscTaskRepository $tasksRepo, GscLoadLogger $logger, GoogleClient $client)
    {
        parent::__construct($tasksRepo, GscLoadJob::class, $logger);
        $this->client = $client;
    }

    protected function doLoadTaskData(Task $task)
    {
        $date = $task->date;
        $site_id = $task->site_id;
        $currentRow = $task->offset;

        $gscSite = GoogleGscSite::findOrFail($task->site_id);

        $clickHouse = ClickHousePositions::getInstance();
        $clickHouse->setSite($site_id);

        do {
            $results = $this->client->getGSCData($gscSite->domain, $date, $currentRow);
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
            $currentRow += count($results);
            $task->offset = $currentRow;
            $task->saveOrFail();
        } while ($count >= 4999);

        $task->status = Task::STATUS_FINISHED;
        $task->saveOrFail();
        $stats = $this->tasksRepo->updateTasksGroupPercent($gscSite);

        $this->logger->write("Загрузка данных сайта {$gscSite->domain} для даты {$task->date} завершена");
        $this->logger->write("Всего задач в очереди: {$stats->total}, завершено: {$stats->finalized}, процент: {$stats->percent}");

    }

    protected function clearUrl($url)
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
        if (Str::endsWith($url, "amp")) {
            $url = rtrim($url, "amp");
        }
        return $url;
    }

    protected function clearKeyword($keyword)
    {
        return mb_strtolower(preg_replace("/[^,\-\s\d\p{Cyrillic}\p{Latin}]/ui", '', $keyword));
    }

}
