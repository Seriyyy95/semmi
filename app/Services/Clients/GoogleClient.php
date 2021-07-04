<?php


namespace App\Services\Clients;

use App\Models\Dto\AvailableDatesDto;
use Google\Client;
use Google\Service\Analytics;
use Google\Service\AnalyticsReporting;
use Google\Service\Webmasters;
use Illuminate\Support\Str;

class GoogleClient
{

    protected Client $client;
    protected Analytics $analytics;
    protected AnalyticsReporting $analyticsReporting;
    protected Webmasters $webmasters;

    public function __construct(Client $client){
        $this->client = $client;
        $this->analytics = new Analytics($client);
        $this->analyticsReporting = new AnalyticsReporting($client);
        $this->webmasters = new Webmasters($client);
    }

    /**
     * @return array
     */
    public function listGscSites()
    {
        $siteList = $this->webmasters->sites->listSites()->siteEntry;
        $sites = array();
        foreach ($siteList as $site) {
            if ($site->permissionLevel != "siteUnverifiedUser") {
                $availDates = $this->getAvailableGscDates($site->siteUrl);
                $sites[] = [
                    'domain' => $site->siteUrl,
                    'start_date' => $availDates->startDate,
                    'end_date' => $availDates->endDate,
                ];
            }
        }
        return $sites;
    }

    /**
     * @param string $siteUrl
     * @return AvailableDatesDto
     */
    public function getAvailableGscDates(string $siteUrl) : AvailableDatesDto
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

        $dates = new AvailableDatesDto();
        $dates->startDate = $rows[0]->keys[0];
        $dates->endDate = end($rows)->keys[0];

        return $dates;
    }


    /**
     * @return array
     */
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
    public function getGSCData($siteUrl, $date, $startRow = 0)
    {
        $dimensions = array('date', 'page', 'query');
        $options['start_date'] = $date;
        $options['end_date'] = $date;
        $query = new Webmasters\SearchAnalyticsQueryRequest();
        $query->setStartDate($options['start_date']);
        $query->setEndDate($options['end_date']);
        $query->setDimensions($dimensions);
        $query->setRowLimit(5000);
        $query->setStartRow($startRow);

        $result = $this->webmasters->searchanalytics->query($siteUrl, $query);
        $rows = $result->getRows();
        return $rows;
    }

    public function getAnalyticsData($view, $date, $site) : array
    {
        $dateRange = new AnalyticsReporting\DateRange();
        $dateRange->setStartDate($date);
        $dateRange->setEndDate($date);

        $organic = new AnalyticsReporting\Metric();
        $organic->setExpression("ga:organicSearches");
        $organic->setAlias("organicSearches");
        $pageviews = new AnalyticsReporting\Metric();
        $pageviews->setExpression("ga:pageviews");
        $pageviews->setAlias("pageviews");
        $adsenseRevenue = new AnalyticsReporting\Metric();
        $adsenseRevenue->setExpression("ga:adsenseRevenue");
        $adsenseRevenue->setAlias("adsenseRevenue");
        $pageDimension = new AnalyticsReporting\Dimension();
        $pageDimension->setName("ga:pagePath");

        $dateDimension = new AnalyticsReporting\Dimension();
        $dateDimension->setName("ga:date");

        $titleDimension = new AnalyticsReporting\Dimension();
        $titleDimension->setName("ga:pageTitle");

        $request = new AnalyticsReporting\ReportRequest();
        $request->setViewId("ga:" . $view);
        $request->setPageSize(10000);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($organic, $pageviews, $adsenseRevenue));
        $request->setDimensions(array($dateDimension, $pageDimension));

        $body = new AnalyticsReporting\GetReportsRequest();
        $body->setReportRequests(array( $request));

        $resultData = array();

        do {
            $startTime = time();
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
                            $scheme = parse_url($url);
                            $url = $scheme["scheme"] . "://" . $scheme["host"] . $scheme["path"];
                            $url = rtrim($url, "/");
                            if (Str::endsWith($url, "amp") || Str::endsWith($url, "amp/")) {
                                $url = rtrim($url, "amp/");
                                $url = rtrim($url, "amp");
                            }

                            if (strlen($url) < 255) {
                                $resultArray["url"] = $url;
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
                        }
                    }
                    $resultData[] = $resultArray;
                }
            }
            $request->setPageToken($reports[0]->getNextPageToken());
            $endTime = time();
            $duration = $endTime - $startTime;
            if ($duration < 2) {
                sleep(2);
            }
        } while ($reports[0]->getNextPageToken() != '');
        return $resultData;
    }

}
