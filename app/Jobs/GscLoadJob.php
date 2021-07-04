<?php

namespace App\Jobs;

use App\Services\GscLoadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\GscTask;

class GscLoadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 10;
    public $timeout = 0;
    public GscTask $gscTask;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GscTask $gscTask)
    {
        $this->gscTask = $gscTask->withoutRelations();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(GscLoadService $service)
    {
        $service->loadTaskData($this->gscTask);
    }

}
