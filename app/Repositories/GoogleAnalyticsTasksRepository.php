<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\GoogleAnalyticsTask;

/**
 * Class GoogleAnalyticsTasksRepository
 * @package App\Repositories
 */
class GoogleAnalyticsTasksRepository extends TasksRepository
{

    /**
     * GoogleAnalyticsTasksRepository constructor.
     */
    public function __construct(){
        parent::__construct(GoogleAnalyticsTask::class);
    }
}
