<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Dto\AddDatesDto;
use App\Models\GoogleSite;
use App\Models\Task;
use App\Repositories\TasksRepository;
use App\Services\Logger\LoadLogger;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * Class LoadService
 * @package App\Services
 */
abstract class LoadService
{

    const ALL_AVAILABLE = -1;

    use DispatchesJobs;

    protected TasksRepository $tasksRepo;
    protected LoadLogger $logger;
    protected string $jobName;

    /**
     * LoadService constructor.
     * @param TasksRepository $tasksRepo
     */
    public function __construct(TasksRepository $tasksRepo, string $jobName, LoadLogger $logger){
       $this->tasksRepo = $tasksRepo;
       $this->jobName = $jobName;
       $this->logger = $logger;
    }

    /**
     * @param string $first
     * @param string $last
     * @param int $count
     * @return array
     */
    protected function datesRange(string $first, string $last, int $count = 50) : array
    {
        $period = CarbonPeriod::create($first, $last);
        $dates = array();
        $counter = 0;

        if($period->count() <= 1){
            return array();
        }

        foreach ($period as $date) {
            $counter++;
            //Пропустить первую дату
            if($counter == 1){
                continue;
            }
            if($count !== LoadService::ALL_AVAILABLE && $counter >= $count){
                break;
            }else{
                $dates[] = $date->format('Y-m-d');
            }
        }
        return $dates;
    }

    /**
     * @param GoogleSite $site
     * @return array
     */
    public function getNextDates(GoogleSite $site, int $limit) : array
    {
        $lastTask = $this->tasksRepo->getLastTask($site);
        if ($lastTask != null && $lastTask->date != null) {
            $dates = $this->datesRange($lastTask->date, $site->end_date, $limit);
        } else {
            $dates = $this->datesRange($site->start_date, $site->end_date, $limit);
        }
        return $dates;
    }

    /**
     * @param GoogleSite $site
     * @param int|null $lastTaskId
     * @return AddDatesDto
     */
    public function addNextDates(
        GoogleSite $site,
        ?int $lastTaskId = null,
        ?int $limit = 50
    ) : AddDatesDto
    {
        $jobName = $this->jobName;
        $count = 0;

        if(null === $lastTaskId){
            $site->parsent = 0;
            $site->saveOrFail();
        }

        $dates = $this->getNextDates($site, $limit);

        if (count($dates) > 0) {

            if (null !== $lastTaskId) {
                $this->logger->write("В задание $lastTaskId добавлено " . count($dates) . " дат");
            } else {
                $this->logger->write("Новое задание создано, добавлено " . count($dates) . " дат");
            }
            foreach ($dates as $date) {
                $count++;
                $task = $this->tasksRepo->addTask($site, $date);

                if ($lastTaskId == null) {
                    $lastTaskId = $task->id;
                    $site->last_task_id = $lastTaskId;
                    $site->saveOrFail();
                }

                $job = (new $jobName($task))
                    ->onConnection('database')
                    ->onQueue("gsc_data");
                $this->dispatch($job);
            }
       }

        return new AddDatesDto($count, $lastTaskId);
    }

    public function loadTaskData(Task $task)
    {
        try {
            if ($task->status !== Task::STATUS_DISABLED) {
                $this->doLoadTaskData($task);
            } else {
                $this->logger->write("Загрузка данных сайта {$task->site->domain} для даты " . $task->date . " пропущена");
            }
        } catch (\Throwable $exception) {
            $this->logger->write("При выполнении задачи {$task->id} произошла ошибка: " . $exception->getMessage(), $exception);
            $task->status = Task::STATUS_FAILED;
            $task->save();
        }
    }

    protected abstract function doLoadTaskData(Task $task);
}
