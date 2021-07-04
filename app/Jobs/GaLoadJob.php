<?php

namespace App\Jobs;

use App\Models\GoogleAnalyticsTask;
use App\Services\GoogleAnalyticsLoadService;
use App\Services\Logger\GaLoadLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\GoogleAnalyticsSite;
use App\OptionsManager;
use App\GoogleDataLoader;
use App\ClickHouseViews;
use App\Services\Logger\LoadLogger;

class GaLoadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    public $timeout = 0;
    public GoogleAnalyticsTask $gaTask;
    private LoadLogger $loadLogger;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GoogleAnalyticsTask $gaTask)
    {
        $this->gaTask = $gaTask->withoutRelations();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(GoogleAnalyticsLoadService $service)
    {
        $service->loadTaskData($this->gaTask);
    }

}
