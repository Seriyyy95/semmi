<?php

namespace App;

use ClickHouseDB\Client;

class ClickHousePositions extends ClickHouse
{
    protected static $instance = null;

    private function __construct()
    {
        parent::__construct("positions");
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
        $query = "SELECT url, SUM(impressions) as total_impressions FROM {$this->database}.positions WHERE user_id={$this->user_id} AND site_id={$this->site_id} GROUP BY url ORDER BY total_impressions DESC";
        $result = $this->db->select($query);
        return $result->rows();
    }

    public function getHistoryData($periods, $url, $field="impressions", $function="sum")
    {
        $periodsData = array();
        $counter = 0;
        foreach ($periods as $period) {
            $periodsData[] = "{$function}If($field, date > '{$period['start_date']}' and date < '{$period['end_date']}') as row_$counter";
            $counter++;
        }
        $periodsString = implode(", ", $periodsData);
        $query = "SELECT url,keyword,$periodsString, count($field) as total FROM {$this->database}.positions WHERE url='$url' GROUP BY url,keyword ORDER BY url, total DESC LIMIT 100";
        $summaryQuery = "SELECT url,$periodsString FROM {$this->database}.positions WHERE url='$url' GROUP BY url LIMIT 1";
        $result = $this->db->select($query);
        $summary = $this->db->select($summaryQuery)->fetchOne();
        $summary["keyword"] = "Общее";
        $data = $result->rows();
        array_unshift($data, $summary);
        return $data;
    }

    public function getChangesData($field, $firstPeriod, $secondPeriod)
    {
        $query = "SELECT url, sumIf($field, date > '{$firstPeriod["startDate"]}' and date < '{$firstPeriod["endDate"]}') as data, sumIf($field, date > '{$secondPeriod["startDate"]}' and date < '{$secondPeriod["endDate"]}') as previous_data, minus(previous_data, data) as result FROM {$this->database}.positions WHERE user_id={$this->user_id} AND site_id={$this->site_id} GROUP BY url ORDER BY data DESC";
        $result = $this->db->select($query);
        $data = $result->rows();
        return $data;
    }

    public function getKeywordsChangesData($url, $firstPeriod, $secondPeriod, $field)
    {
        $query = "SELECT keyword, sumIf($field, date > '{$firstPeriod["startDate"]}' and date < '{$firstPeriod["endDate"]}') as data, sumIf($field, date > '{$secondPeriod["startDate"]}' and date < '{$secondPeriod["endDate"]}') as previous_data, minus(previous_data, data) as result FROM {$this->database}.positions WHERE user_id={$this->user_id} AND site_id={$this->site_id} AND url='$url' GROUP BY keyword ORDER BY data DESC";
        $result = $this->db->select($query);
        $data = $result->rows();
        return $data;
    }

    protected function createTablesIfNotExist()
    {
        $this->db->write("
            CREATE TABLE IF NOT EXISTS {$this->database}.{$this->table} (
                id UInt64,
                date Date,
                user_id Int32,
                site_id Int32,
                url String,
                domain String,
                keyword String,
                impressions Int32,
                clicks Int32,
                avg_position Float32,
                avg_ctr Float32
            )
            ENGINE = MergeTree()
            ORDER BY date
        ");
    }
}
