<?php

declare(strict_types=1);

namespace App\Repositories;


use App\Models\GoogleAnalyticsSite;
use App\Services\Clients\GoogleClient;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class GoogleAnalyticsSitesRepository
 * @package App\Repositories
 */
class GoogleAnalyticsSitesRepository extends SitesRepository
{

    protected GoogleClient $client;

    /**
     * GoogleAnalyticsSitesRepository constructor.
     * @param GoogleClient $client
     * @param GoogleAnalyticsTasksRepository $tasksRepo
     */
    public function __construct(
        GoogleClient $client,
        GoogleAnalyticsTasksRepository $tasksRepo
    ){
        parent::__construct($tasksRepo);
        $this->client = $client;
    }

    /**
     * @return GoogleAnalyticsSite[]|\Illuminate\Database\Eloquent\Collection
     * @throws \Throwable
     */
    public function updateAndListSites() : Collection{
        //Получаем список сайтов и дат из гугла
        $sites = $this->client->listAnalyticsProfiles();
        foreach ($sites as $site) {
            $profile_id = $site["profile_id"];
            $cachedSite = GoogleAnalyticsSite::where("profile_id", $profile_id)->first();
            if (null !== $cachedSite) {
                //Если сайт уже есть в базе данных - обновить даты
                $cachedSite->start_date = $site["start_date"];
                $cachedSite->end_date = $site["end_date"];
                $this->updateQueuedDates($cachedSite);
                $cachedSite->saveOrFail();
            } else {
                //Еслиего нет - добавить новый
                $newSite = new GoogleAnalyticsSite();
                $newSite->fill($site);
                $this->updateQueuedDates($newSite);
                $newSite->saveOrFail();
            }
        }
        //Получить список сайтов из базы данных
        return GoogleAnalyticsSite::all();
    }


}
