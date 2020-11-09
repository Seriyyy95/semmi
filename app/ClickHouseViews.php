<?php

namespace App;

use ClickHouseDB\Client;

class ClickHouseViews extends ClickHouse
{
    protected static $instance = null;

    private function __construct()
    {
        parent::__construct("views");
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getUrls()
    {
        $query = "SELECT url, SUM(pageviews) as total_pageviews FROM {$this->database}.{$this->table} WHERE user_id={$this->user_id} AND site_id={$this->site_id} GROUP BY url HAVING total_pageviews > 100 ORDER BY total_pageviews DESC";
        $result = $this->db->select($query);
        return $result->rows();
    }


    public function getHistoryData($periods, $url, $field = "pageviews", $function = "sum")
    {
        $periodsData = array();
        $counter = 0;
        foreach ($periods as $period) {
            $periodsData[] = "{$function}If($field, date > '{$period['start_date']}' and date < '{$period['end_date']}') as row_$counter";
            $counter++;
        }
        $periodsString = implode(", ", $periodsData);
        $query = "SELECT url,$periodsString, $function($field) as total FROM {$this->database}.{$this->table} WHERE url='$url' GROUP BY url ORDER BY url DESC LIMIT 250";
        $result = $this->db->select($query);
        $data = $result->rows();
        return $data;
    }

    public function getUrlRevenue($url)
    {
        $query = "SELECT url, SUM(adsenseRevenue) as revenue FROM {$this->database}.{$this->table} WHERE url='$url' AND site_id={$this->site_id} AND user_id={$this->user_id} GROUP BY url";
        $result = $this->db->select($query);
        $data = $result->rows();
        if (count($data) > 0 && $data[0]["revenue"] > 0) {
            return $data[0]["revenue"];
        } else {
            return 0;
        }
    }

    public function getAvgRevenue($url)
    {
        $query = "SELECT AVG(revenue) as avg_revenue FROM (SELECT SUM(adsenseRevenue) as revenue FROM {$this->database}.{$this->table} WHERE url='$url' AND site_id={$this->site_id} AND user_id={$this->user_id} GROUP BY toStartOfMonth(date))";
        $result = $this->db->select($query);
        $data = $result->rows();
        if (count($data) > 0 && $data[0]["avg_revenue"] > 0) {
            return $data[0]["avg_revenue"];
        } else {
            return 0;
        }
    }

    protected function createTablesIfNotExist()
    {
        //        $this->db->write("DROP TABLE IF EXISTS {$this->database}.{$this->table}");
        $this->db->write("
            CREATE TABLE IF NOT EXISTS {$this->database}.{$this->table} (
                id UInt64,
                date Date,
                user_id Int32,
                site_id Int32,
                url String,
                domain String,
                pageviews Int32,
                organicSearches Int32,
                adsenseRevenue Float32
            )
            ENGINE = MergeTree()
            ORDER BY date
        ");
    }
}
