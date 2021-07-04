<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Dto\PercentDataDto;
use App\Models\GoogleAnalyticsTask;
use App\Models\GoogleSite;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TasksRepository
 * @package App\Repositories
 */
class TasksRepository
{

    private string $modelName;

    /**
     * TasksRepository constructor.
     * @param string $modelName
     */
    public function __construct(string $modelName){
        $this->modelName = $modelName;
    }

    public function getLastTask(GoogleSite $site) : Model
    {
        $model = $this->modelName;
        return $model::selectRaw("MAX(date) as date")
            ->where("site_id", $site->id)
            ->where("status", "!=", Task::STATUS_DISABLED)
            ->limit(1)
            ->first();

    }

    public function disableLastTasksGroup(GoogleSite $site) : bool{
        $model = $this->modelName;

        if(null !== $site->last_task_id) {
            $model::where("site_id", $site->id)
                ->where("id", ">=", $site->last_task_id)
                ->where("status", "active")
                ->update(array("status" => "disabled"));
        }else{
            $this->disableAllSiteTasks($site);
        }

        return true;
    }

    public function disableAllSiteTasks(GoogleSite $site) : bool
    {
        $model = $this->modelName;
        $model::where("site_id", $site->id)
            ->update(array("status" => "disabled"));

        return true;
    }

    public function getFirstTask(GoogleSite $site){
        $model = $this->modelName;
        return $model::selectRaw("MIN(date) as date")
            ->where("site_id", $site->id)
            ->where("status", "!=", Task::STATUS_DISABLED)
            ->limit(1)
            ->first();

    }

    public function addTask(GoogleSite $site, string $date){
        $model = $this->modelName;
        $task = new $model();
        $task->date = $date;
        $task->site()->associate($site);
        $task->saveOrFail();

        return $task;
    }

    public function updateTasksGroupPercent(GoogleSite $site) : PercentDataDto
    {
        $modelName = $this->modelName;

        $finalized = $modelName::where("site_id", $site->id)
            ->where("status", "!=", "active")
            ->where("status", "!=", "disabled")
            ->where("id", ">", $site->last_task_id)
            ->count();
        $max = $modelName::selectRaw("MAX(id) as id")
            ->where("site_id", $site->id)
            ->first()->id;
        $total = (int) $max - (int) $site->last_task_id;
        $site->parsent = $finalized * 100 / $total;
        $site->saveOrFail();

        $result = new PercentDataDto();
        $result->total = $total;
        $result->finalized = $finalized;
        $result->percent = round($site->parsent, 2);

        return $result;
    }

}
