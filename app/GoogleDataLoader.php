<?php

namespace App;

use Google\Service;
use App\OptionsManager;
use Closure;

class GoogleDataLoader
{
    private $api;
    private $user_id;
    private $mode;
    private $optionsManager;

    public function __construct(OptionsManager $optionsManager)
    {
        $this->optionsManager = $optionsManager;
    }

    /*Инициализация GoogleAPI. Читает параметры mode, name и config из базы данных
    и создает объект GoogleAPI.
    Если режим QAUTH читает и инициализирует необходимые токены
    */
    protected function initAPI()
    {
        $this->mode = $this->getMode();
        $config = $this->getConfigFile();
        $this->api = new GoogleAPI($this->mode, $config);
        if ($this->mode == GoogleAPI::MODE_QAUTH) {
            if ($this->optionsManager->hasValue("google_token")) {
                $accessToken = $this->optionsManager->getObject("google_token");
                if (isset($accessToken["access_token"])) {
                    $result = $this->api->setAccessTokens($accessToken);
                    if ($result["created"] != false) {
                        $this->optionsManager->saveObject("google_token", $result);
                    }
                    $this->api->initWebmasters();
                    $this->api->initAnalytics();
                }
            }
        } else {
            $this->api->initWebmasters();
            $this->api->initAnalytics();
        }
    }

    public function saveAccessToken($tokenData)
    {
        $this->optionsManager->saveObject("google_token", $tokenData);
    }

    public function setUser($user_id)
    {
        $this->user_id = $user_id;
        $this->optionsManager->setUser($user_id);
        $this->initAPI();
    }

    public function getAPI()
    {
        if ($this->api != null && $this->hasConfigFile()) {
            return $this->api;
        } else {
            throw new GoogleNeedAuthException("API not ready!");
        }
    }


    public function getEmptyAPI()
    {
        if ($this->api != null) {
            return $this->api;
        } else {
            throw new \Exception("API not initialized!");
        }
    }

    public function hasConfigFile()
    {
        $mode = $this->getMode();
        if ($mode == GoogleAPI::MODE_QAUTH) {
            if ($this->optionsManager->hasValue("google_token")) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($this->optionsManager->hasValue("google_api_config")) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function getParams()
    {
        return array(
            "mode" => $this->mode,
        );
    }

    public function setMode($mode)
    {
        $this->optionsManager->setValue("google_api_mode", $mode);
        $this->mode = $mode;
    }

    public function getMode()
    {
        $modeValue = $this->optionsManager->getValue("google_api_mode");
        if ($modeValue == null) {
            $this->setMode(GoogleAPI::MODE_SERVICE);
            return $this->mode;
        } else {
            return $modeValue;
        }
    }

    public function getConfigFile()
    {
        $configPath = $this->optionsManager->getValue("google_api_config");
        if (file_exists($configPath)) {
            return $configPath;
        } else {
            throw new GoogleNeedConfigFileException();
        }
    }

    public function setConfigFile($configPath)
    {
        try {
            $oldConfigFile = $this->getConfigFile();
            $this->removeConfigFile($oldConfigFile);
        } catch (\Exception $e) {
        }
        if (file_exists($configPath)) {
            $this->optionsManager->setValue("google_api_config", $configPath);
        } else {
            throw new \Exception("Файл не существует " . $configPath);
        }
    }

    public function removeConfigFile($configPath)
    {
        if (file_exists($configPath)) {
            unlink($configPath);
        }
    }

    public function listGSCSites(Closure $closure = null)
    {
        $sitesList = $this->getAPI()->listSites();
        foreach ($sitesList as $site) {
            //Проверяем есть ли уже такой сайт в базе
            $cachedSites = GoogleGscSite::where("user_id", $this->user_id)->where("domain", $site)->get();
            //Если сайта нет или нужно обновить даты, обновляем даты
            $dates = $this->getAPI()->getAvailableDates($site);
            if (count($cachedSites) == 0) {
                $newSite = new GoogleGscSite();
                $newSite->user_id = $this->user_id;
                $newSite->domain = $site;
                $newSite->start_date = $dates["start_date"];
                $newSite->end_date = $dates["end_date"];
                if ($closure != null) {
                    $closure($newSite);
                }
                $newSite->save();
            } else {
                $cachedSite = $cachedSites[0];
                //Если сайт уже есть в базе данных - обновить даты
                $cachedSite->start_date = $dates["start_date"];
                $cachedSite->end_date = $dates["end_date"];
                if ($closure != null) {
                    $closure($cachedSite);
                }
                $cachedSite->save();
            }
            //Получаем данные для отображения
        }
        $storedSites = GoogleGscSite::where("user_id", $this->user_id)->get();
        return $storedSites;
    }

    public function listAnalyticsSites($closure = null)
    {
        //Получаем количество сохраненных сайтов
        $countSites = GoogleAnalyticsSite::where("user_id", $this->user_id)->count();
        //Получаем список сайтов и дат из гугла
        $sites = $this->getAPI()->listAnalyticsProfiles();
        foreach ($sites as $site) {
            $profile_id = $site["profile_id"];
            $cachedSites = GoogleAnalyticsSite::where("user_id", $this->user_id)->where("profile_id", $profile_id)->get();
            if (count($cachedSites) > 0) {
                $cachedSite = $cachedSites[0];
                //Если сайт уже есть в базе данных - обновить даты
                $cachedSite->start_date = $site["start_date"];
                $cachedSite->end_date = $site["end_date"];
                if ($closure != null) {
                    $closure($cachedSite);
                }
                $cachedSite->save();
            } else {
                //Еслиего нет - добавить новый
                $newSite = new GoogleAnalyticsSite();
                $newSite->user_id = $this->user_id;
                $newSite->profile_id = $site["profile_id"];
                $newSite->profile_name = $site["profile_name"];
                $newSite->domain = $site["domain"];
                $newSite->start_date = $site["start_date"];
                $newSite->end_date = $site["end_date"];
                if ($closure != null) {
                    $closure($newSite);
                }
                $newSite->save();
            }
        }
        //Получить список сайтов из базы данных
        $storedSites = GoogleAnalyticsSite::where("user_id", $this->user_id)->get();
        return $storedSites;
    }

    public function listCachedGSCSites()
    {
        $cachedSites = GoogleGscSite::where("user_id", $this->user_id)->get();
        return $cachedSites;
    }

    public function listCachedAnalyticsSites()
    {
        $cachedSites = GoogleAnalyticsSite::where("user_id", $this->user_id)->get();
        return $cachedSites;
    }

    public function setAnalyticsLoadedDates($profile, $first_date, $last_date, $error=true)
    {
        $sites = GoogleAnalyticsSite::where("user_id", $this->user_id)->where("profile_id", $profile)->get();
        if (count($sites) > 0) {
            $site = $sites[0];
//        if(strlen($site->first_date) == 0){
            $site->first_date = $first_date;
            //       }
            $site->last_date = $last_date;
            $site->save();
        } elseif ($error) {
            throw new \Exception("Profile not found");
        }
    }

    public function setGSCLoadedDates($site, $first_date, $last_date, $error=true)
    {
        $sites = GoogleGscSite::where("user_id", $this->user_id)->where("domain", $site)->get();
        if (count($sites) > 0) {
            $site = $sites[0];
//        if(strlen($site->first_date) == 0){
            $site->first_date = $first_date;
            //       }
            $site->last_date = $last_date;
            $site->save();
        } elseif ($error) {
            throw new \Exception("Site not found");
        }
    }
    //Возвращает список пользователей, у которых есть сайты в автозагрузке, формат array("user_id"=>"value")
    public function getGSCAutoloadingUsers()
    {
        $usersData = GoogleGscSite::select("user_id")->where("autoload", "1")->distinct()->get();
        ;
        return $usersData;
    }

    public function getAnalyticsAutoloadingUsers()
    {
        $usersData = GoogleAnalyticsSite::select("user_id")->where("autoload", "1")->distinct()->get();
        ;
        return $usersData;
    }
}
