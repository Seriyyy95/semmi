<?php

namespace App;

use Google\Service;

class GoogleAPI
{
    const MODE_QAUTH = 1;
    const MODE_SERVICE = 2;

    protected $client;
    protected $token;
    protected $webmasters;
    protected $analytics;
    protected $refreshToken;
    protected $mode;

    public function __construct($mode = GoogleAPI::MODE_QAUTH, $config, $name = "semmi.ru")
    {
        $this->mode = $mode;
        $this->client = new \Google_Client();
        $this->client->setApplicationName($name);
        $this->client->setAuthConfig($config);
        if($mode == GoogleAPI::MODE_QAUTH){
          $this->client->setAccessType("offline");
          $this->client->setApprovalPrompt ("force");
        }
        $this->client->setIncludeGrantedScopes(true);
        $this->client->addScope('https://www.googleapis.com/auth/webmasters');
        $this->client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
        $this->client->addScope('https://www.googleapis.com/auth/analytics.readonly');
    }

    public function getRedirectUri($callback)
    {
        $this->client->setRedirectUri($callback);
        return $this->client->createAuthUrl();
    }

    public function featchTokens($code)
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }

    public function isAuthorized()
    {
        if ($this->mode == GoogleAPI::MODE_QAUTH) {
            return true;
        } elseif ($this->mode == GoogleAPI::MODE_SERVICE) {
            return true;
        }
    }

    public function setAccessTokens($token)
    {
        $this->refreshToken = $token["refresh_token"];
        $this->client->setAccessToken($token);
        if ($this->client->isAccessTokenExpired()) {
            $this->client->refreshToken($this->refreshToken);
            $newToken = $this->client->getAccessToken();
            return array(
                "access_token" => $newToken["access_token"],
                "expires_in" => $newToken["expires_in"],
                "created" => $newToken["created"],
                "refresh_token" => $this->refreshToken,
            );
        } else {
            return array(
                "created" => false,
            );
        }
    }

    public function revalidateAccessTokens()
    {
        error_log(print_r("Old expired: " . $this->client->isAccessTokenExpired(), true));
        if ($this->client->isAccessTokenExpired()) {
            $this->client->refreshToken($this->refreshToken);
            $newToken = $this->client->getAccessToken();
            return array(
                "access_token" => $newToken["access_token"],
                "expires_in" => $newToken["expires_in"],
                "created" => $newToken["created"],
            );
        } else {
            return 0;
        }
    }

    public function initWebmasters()
    {
        $this->webmasters = new \Google_Service_Webmasters($this->client);
    }

    public function initAnalytics()
    {
        $this->analytics = new \Google_Service_Analytics($this->client);
        $this->analyticsReporting = new \Google_Service_AnalyticsReporting($this->client);
    }

    public function listSites()
    {
        $site_list = $this->webmasters->sites->listSites()->siteEntry;
        $sites = array();
        foreach ($site_list as $site) {
            if ($site->permissionLevel != "siteUnverifiedUser") {
                $sites[] = $site->siteUrl;
            }
        }
        return $sites;
    }

    public function getAvailableDates($siteUrl)
    {
        $dimensions = array('date');
        $options['start_date'] = date('Y-m-d', strtotime("-700 days"));
        $options['end_date'] = date('Y-m-d', strtotime("-3 days"));
        $query = new \Google_Service_Webmasters_SearchAnalyticsQueryRequest();
        $query->setStartDate($options['start_date']);
        $query->setEndDate($options['end_date']);
        $query->setDimensions($dimensions);
        $query->setRowLimit(1000);
        $query->setStartRow(0);
        $data = $this->webmasters->searchanalytics->query($siteUrl, $query);
        $rows = $data->getRows();
        $result = array(
            "start_date" => $rows[0]->keys[0],
            "end_date" => end($rows)->keys[0],
        );
        return $result;
    }

    public function getGSCData($siteUrl, $date, $startRow = 0)
    {
        $dimensions = array('date', 'page', 'query');
        $options['start_date'] = $date;
        $options['end_date'] = $date;
        $query = new \Google_Service_Webmasters_SearchAnalyticsQueryRequest();
        $query->setStartDate($options['start_date']);
        $query->setEndDate($options['end_date']);
        $query->setDimensions($dimensions);
        $query->setRowLimit(5000);
        $query->setStartRow($startRow);

        $result = $this->webmasters->searchanalytics->query($siteUrl, $query);
        $rows = $result->getRows();
        return $rows;
    }

    public function getAnalyticsData($view, $date, $site)
    {
        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($date);
        $dateRange->setEndDate($date);

        $organic = new \Google_Service_AnalyticsReporting_Metric();
        $organic->setExpression("ga:organicSearches");
        $organic->setAlias("organicSearches");
        $bounce = new \Google_Service_AnalyticsReporting_Metric();
        $bounce->setExpression("ga:bounceRate");
        $bounce->setAlias("bounceRate");
        $entrances = new \Google_Service_AnalyticsReporting_Metric();
        $entrances->setExpression("ga:entrances");
        $entrances->setAlias("entrances");
        $pageviews = new \Google_Service_AnalyticsReporting_Metric();
        $pageviews->setExpression("ga:pageviews");
        $pageviews->setAlias("pageviews");
        $exits = new \Google_Service_AnalyticsReporting_Metric();
        $exits->setExpression("ga:exits");
        $exits->setAlias("exits");
        $uniquePageviews = new \Google_Service_AnalyticsReporting_Metric();
        $uniquePageviews->setExpression("ga:uniquePageviews");
        $uniquePageviews->setAlias("uniquePageviews");
        $timeOnPage = new \Google_Service_AnalyticsReporting_Metric();
        $timeOnPage->setExpression("ga:timeOnPage");
        $timeOnPage->setAlias("timeOnPage");
        $adsenseRevenue = new \Google_Service_AnalyticsReporting_Metric();
        $adsenseRevenue->setExpression("ga:adsenseRevenue");
        $adsenseRevenue->setAlias("adsenseRevenue");
        $adsenseAdsViewed = new \Google_Service_AnalyticsReporting_Metric();
        $adsenseAdsViewed->setExpression("ga:adsenseAdsViewed");
        $adsenseAdsViewed->setAlias("adsenseAdsViewed");
        $adsenseAdsClicks = new \Google_Service_AnalyticsReporting_Metric();
        $adsenseAdsClicks->setExpression("ga:adsenseAdsClicks");
        $adsenseAdsClicks->setAlias("adsenseAdsClicks");

        $pageDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $pageDimension->setName("ga:pagePath");

        $dateDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $dateDimension->setName("ga:date");

        $sourceDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $sourceDimension->setName("ga:source");

        $titleDimension = new \Google_Service_AnalyticsReporting_Dimension();
        $titleDimension->setName("ga:pageTitle");

        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId("ga:" . $view);
        $request->setPageSize(10000);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($organic, $bounce, $entrances, $pageviews, $exits, $uniquePageviews, $timeOnPage, $adsenseRevenue, $adsenseAdsViewed, $adsenseAdsClicks));
        $request->setDimensions(array($dateDimension, $pageDimension));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array( $request));

        $resultData = array();

        do {
            $reports = $this->analyticsReporting->reports->batchGet($body);

            foreach ($reports as $report) {
                $rows = $report->getData()->getRows();
                $header = $report->getColumnHeader();
                $dimensionHeaders = $header->getDimensions();
                $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
                foreach ($rows as $row) {
                    $dimensions = $row->getDimensions();
                    $metricsSets = $row->getMetrics();
                    $resultArray = array();
                    for ($i=0; $i < count($dimensions); $i++) {
                        $dimensionName = str_replace("ga:", "", $dimensionHeaders[$i]);
                        if ($dimensionName == "date") {
                            $resultArray[$dimensionName] = $date;
                        } elseif ($dimensionName == "pagePath") {
                            $url = substr($site, 0, -1) . $dimensions[$i];
                            if (strpos($url, "/?s=")) {
                                $url = $site . "search";
                            } elseif (strpos($url, ".jpg") || strpos($url, ".jpeg") || strpos($url, ".png") || strpos($url, ".gif")) {
                                $url = $site . "attachment";
                            } elseif (strpos($url, " ")) {
                                $url = substr($url, 0, strpos($url, " "));
                            }
                            $url = clear_url($url);
                            if (strlen($url) < 120) {
                                $resultArray[$dimensionName] = $url;
                            }
                        } else {
                            $resultArray[$dimensionName] = $dimensions[$i];
                        }
                    }
                    foreach ($metricsSets as $metricSet) {
                        $metrics = $metricSet->getValues();
                        for ($i=0; $i < count($metrics); $i++) {
                            if (is_numeric($metrics[$i])) {
                                //Экспоненту в нормальное число
                                $resultArray[$metricHeaders[$i]->name] = floatval($metrics[$i]);
                            } else {
                                $resultArray[$metricHeaders[$i]->name] = $metrics[$i];
                            }
                            $resultArray["domain"] = $site;
                            $resultArray["profile"] = $view;
                        }
                    }
                    $resultData[] = $resultArray;
                }
            }
            $request->setPageToken($reports[0]->getNextPageToken());
        } while ($reports[0]->getNextPageToken() != '');
        return $resultData;
    }

    public function listAnalyticsProfiles()
    {
        $allProfiles = array();
        $accounts = $this->analytics->management_accounts->listManagementAccounts();
        if (count($accounts->getItems()) > 0) {
            $accountsList = $accounts->getItems();
            foreach ($accountsList as $account) {
                // Получаем список профилей для каждого аккаунта.
                $properties = $this->analytics->management_webproperties
                    ->listManagementWebproperties($account->getId());
                if (count($properties->getItems()) > 0) {
                    $propertiesList = $properties->getItems();
                    foreach ($propertiesList as $property) {
                        // Получаем список представлений для каждого профиля
                        $profiles = $this->analytics->management_profiles
                            ->listManagementProfiles($account->getId(), $property->getId());
                        if (count($profiles->getItems()) > 0) {
                            $profilesList = $profiles->getItems();
                            foreach ($profilesList as $profile) {
                                $createdDate = date("Y-m-d", strtotime($profile->getCreated()));
                                $nowDate = date("Y-m-d", strtotime("yesterday"));
                                $allProfiles[] = array(
                                   "account_id" => $profile->getAccountId(),
                                   "domain" => $property->getWebsiteUrl() . "/",
                                   "profile_id" => $profile->getId(),
                                   "profile_name" => $profile->getName(),
                                   "start_date" => $createdDate,
                                   "end_date" => $nowDate,
                               );
                            }
                        }
                    }
                }
            }
        }
        return $allProfiles;
    }
}
