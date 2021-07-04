<?php


namespace App\Repositories;


use App\Models\GoogleGscSite;
use App\Services\Clients\GoogleClient;
use Illuminate\Support\Collection;

class GscSitesRepository extends SitesRepository
{

    protected GoogleClient $client;

    /**
     * GscSitesRepository constructor.
     * @param GoogleClient $client
     * @param GscTaskRepository $tasksRepo
     */
    public function __construct(
        GoogleClient $client,
        GscTaskRepository $tasksRepo
    ){
        parent::__construct($tasksRepo);
        $this->client = $client;
    }

    /**
     * @return Collection
     * @throws \Throwable
     */
    public function updateAndListSites() : Collection{
        $sitesList = $this->client->listGscSites();
        foreach ($sitesList as $site) {
            //Проверяем есть ли уже такой сайт в базе
            $cachedSite = GoogleGscSite::where("domain", $site)->first();
            if ($cachedSite != null) {
                //Если сайт уже есть в базе данных - обновить даты
                $cachedSite->start_date = $site["start_date"];
                $cachedSite->end_date = $site["end_date"];
                $this->updateQueuedDates($cachedSite);
                $cachedSite->saveOrFail();
            }else{
                $newSite = new GoogleGscSite();
                $newSite->fill($site);
                $this->updateQueuedDates($newSite);
                $newSite->saveOrFail();
            }
            //Получаем данные для отображения
        }
        return GoogleGscSite::all();
    }
}
