<?php


namespace App\Services;

use App\ClickHouseViews;
use App\GoogleDataLoader;
use App\Jobs\GaLoadJob;
use App\Models\GoogleAnalyticsSite;
use App\Models\GoogleAnalyticsTask;
use App\Models\Task;
use App\OptionsManager;
use App\Repositories\GoogleAnalyticsTasksRepository;
use App\Services\Clients\GoogleClient;
use App\Services\Logger\GaLoadLogger;
use Throwable;

class GoogleAnalyticsLoadService extends LoadService
{

    protected GoogleClient $client;

    public function __construct(GoogleAnalyticsTasksRepository $tasksRepo, GaLoadLogger $logger, GoogleClient $client)
    {
        parent::__construct($tasksRepo, GaLoadJob::class, $logger);

        $this->client = $client;
    }


    protected function doLoadTaskData(Task $gaTask)
    {
        $site_id = $gaTask->site_id;
        $gaSite = GoogleAnalyticsSite::findOrFail($gaTask->site_id);

        $date = $gaTask->date;

        $clickHouse = ClickHouseViews::getInstance();
        $clickHouse->setSite($site_id);

        $resultData = $this->client->getAnalyticsData($gaSite->profile_id, $date, $gaSite->domain);

        $count = count($resultData);

        $clickHouse->index($resultData);
        $gaTask->offset = $count;
        $gaTask->status = Task::STATUS_FINISHED;
        $gaTask->save();
        $stats = $this->tasksRepo->updateTasksGroupPercent($gaSite);
        $this->logger->write("Загрузка данных сайта {$gaSite->domain} для даты {$gaTask->date} завершена");
        $this->logger->write("Всего задач в очереди: {$stats->total}, завершено: {$stats->finalized}, процент: {$stats->percent}");
    }
}
