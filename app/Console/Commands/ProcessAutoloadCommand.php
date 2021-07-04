<?php

namespace App\Console\Commands;

use App\Models\GoogleGscSite;
use App\Models\GoogleAnalyticsSite;
use App\Repositories\GoogleAnalyticsTasksRepository;
use App\Repositories\GscTaskRepository;
use App\Services\GoogleAnalyticsLoadService;
use App\Services\GscLoadService;
use App\Services\LoadService;
use Illuminate\Console\Command;

class ProcessAutoloadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:autoload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        GoogleAnalyticsLoadService $analyticsService,
        GscLoadService $gscService,
    )
    {
        $this->info('Adding new dates for sites with autoloading...');
        $gscSites = GoogleGscSite::where("autoload", 1)->get();
        foreach($gscSites as $site){
           $result = $gscService->addNextDates($site, null, LoadService::ALL_AVAILABLE);
           $this->info("Added {$result->count} dates for gsc site {$site->domain}.");
        }
        $analyticsSites = GoogleAnalyticsSite::whereAutoload('1')->get();
        foreach($analyticsSites as $site){
            $result = $analyticsService->addNextDates($site, null, LoadService::ALL_AVAILABLE);
            $this->info("Added {$result->count} dates for analytics site {$site->domain}.");
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
