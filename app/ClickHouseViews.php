<?php

namespace App;

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
        $params = array(
            "database" => $this->database,
            "table" => $this->table,
            "site_id" => $this->site_id,
        );
        $query = "SELECT url, SUM(pageviews) as total_pageviews FROM {database}.{table} WHERE site_id={site_id} GROUP BY url HAVING total_pageviews > 100 ORDER BY total_pageviews DESC";
        $result = $this->db->select($query, $params);
        return $result->rows();
    }

    public function getFirstUrlDate($url)
    {
        $params = array(
            "database" => $this->database,
            "table" => $this->table,
            "site_id" => $this->site_id,
            "url" => $url,
        );

        $query = "SELECT MIN(date) as date FROM {database}.{table} WHERE url='{url}' AND site_id={site_id}";
        $result = $this->db->select($query, $params);
        $data = $result->rows();
        if (count($data) > 0 && $data[0]["date"] > 0) {
            return $data[0]["date"];
        } else {
            return 0;
        }
    }

    public function getHistoryData($periods, $url, $field = "pageviews", $function = "sum")
    {
        $params = array(
            "database" => $this->database,
            "table" => $this->table,
            "site_id" => $this->site_id,
            "url" => $url,
            "field" => $field,
            "function" => $function,
        );

        $periodsData = array();
        $counter = 0;
        foreach ($periods as $period) {
            $periodsData[] = "{function}If({field}, date > '{$period['start_date']}' and date < '{$period['end_date']}') as row_$counter";
            $counter++;
        }
        $periodsString = implode(", ", $periodsData);
        $query = "SELECT $periodsString, {function}({field}) as total FROM {database}.{table} WHERE url='{url}'";
        $result = $this->db->select($query, $params);
        $data = $result->rows();
        if (count($data) > 0) {
            return $data[0];
        } else {
            return array("total" => 0);
        }
    }

    public function getChangesData($field, $firstPeriod, $secondPeriod)
    {
        $params = array(
            "database" => $this->database,
            "table" => $this->table,
            "site_id" => $this->site_id,
            "field" => $field,
            "first_period_start_date" => $firstPeriod["startDate"],
            "first_period_end_date" => $firstPeriod["endDate"],
            "second_period_start_date" => $secondPeriod["startDate"],
            "second_period_end_date" => $secondPeriod["endDate"],
        );

        $query = "SELECT url, sumIf({field}, date > '{first_period_start_date}' and date < '{first_period_end_date}') as data, sumIf({field}, date > '{second_period_start_date}' and date < '{second_period_end_date}') as previous_data, minus(previous_data, data) as result FROM {database}.{table} WHERE site_id={site_id} GROUP BY url ORDER BY data DESC";
        $result = $this->db->select($query, $params);
        return $result->rows();
    }

    protected function createTablesIfNotExist()
    {
        //        $this->db->write("DROP TABLE IF EXISTS {$this->database}.{$this->table}");
        $params = array(
            "table" => $this->table,
            "database" => $this->database,
        );
        $this->db->write("
            CREATE TABLE IF NOT EXISTS {database}.{table} (
                id UInt64,
                date Date,
                site_id Int32,
                url String,
                domain String,
                pageviews Int32,
                organicSearches Int32,
                adsenseRevenue Float32
            )
            ENGINE = MergeTree()
            ORDER BY date
        ", $params);
    }
}
