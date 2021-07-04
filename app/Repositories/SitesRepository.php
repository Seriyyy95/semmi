<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\GoogleAnalyticsSite;
use App\Models\GoogleSite;

/**
 * Class SitesRepository
 * @package App\Repositories
 */
class SitesRepository
{

    protected TasksRepository $tasksRepo;

    public function __construct(TasksRepository $tasksRepo){
       $this->tasksRepo = $tasksRepo;
    }

    /**
     * @param GoogleSite $site
     */
    public function updateQueuedDates(GoogleSite $site){
        $lastTask = $this->tasksRepo->getLastTask($site);
        $firstTask = $this->tasksRepo->getFirstTask($site);
        if ($lastTask != null && $lastTask->date != null) {
            $site->last_date = $lastTask->date;
        } else {
            $site->last_date = null;
        }
        if ($firstTask != null && $firstTask->date != null) {
            $site->first_date = $firstTask->date;
        } else {
            $site->first_date = null;
        }
    }
}
