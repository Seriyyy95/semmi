<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\OptionsManager;
use Seriyyy95\WPConnector\Connector as WPConnector;
use App\WPUrl;

class WpLoadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $wpUrl;
    public $wpSite;
    public $postId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($wpUrl, $wpSite, $postId)
    {
        $this->wpUrl = $wpUrl;
        $this->wpSite = $wpSite;
        $this->postId = $postId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $userId = $this->wpSite->user_id;
        $siteId = $this->wpSite->site_id;
        $optionsManager = new OptionsManager();
        $optionsManager->setUser($userId);
        $wpConnKey = $optionsManager->getValue("wpconnector_key");
        $wpConnUrl = env("WP_CONNECTOR_URL");
        $wpConnector = new WPConnector();
        $wpConnector->setDomain($wpConnUrl);
        $wpConnector->setApiKey($wpConnKey);

        $postStats = $wpConnector->site($siteId)->getPostStats($this->postId);
        $this->wpUrl->post_length = $postStats["post_length"];
        if ($this->wpUrl->manually == 0) {
            $this->wpUrl->price = $postStats["post_length"] * $this->wpSite->price / 1000;
        }
        $this->wpUrl->status = "finished";
        $this->wpUrl->save();
    }

    public function failed()
    {
        $this->wpUrl->status = "finished";
        $this->wpUrl->save();
    }
}
