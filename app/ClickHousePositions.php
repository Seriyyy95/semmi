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

    public function getAllUrlKeywords($url)
    {
        $query = "SELECT keyword, SUM(impressions) as total_impressions, SUM(clicks) as total_clicks, ROUND(AVG(avg_position),2) as avg_position FROM {$this->database}.positions WHERE user_id={$this->user_id} AND site_id={$this->site_id} AND url='$url' GROUP BY keyword ORDER BY total_impressions DESC";
        $result = $this->db->select($query);
        return $result->rows();
    }

    public function getUrlGraphData($url, $interval="month", $field, $aggFunc)
    {
        if ($interval == "week") {
            $groupFunc = "toStartOfWeek";
        } elseif ($interval == "month") {
            $groupFunc = "toStartOfMonth";
        } elseif ($interval == "quarter") {
            $groupFunc = "toStartOfQuarter";
        } else {
            throw new \Exception("Invalid interval: $interval");
        }
        $query = "SELECT $groupFunc(date) as row_date, $aggFunc($field) as row_value FROM {$this->database}.{$this->table} WHERE user_id={$this->user_id} AND site_id={$this->site_id} AND url='$url' GROUP BY row_date";
        $result = $this->db->select($query);
        $rows = $result->rows();
        return $rows;
    }

    public function getUrlCVData($url, $interval="month", $field)
    {
        if ($interval == "week") {
            $groupFunc = "toStartOfWeek";
        } elseif ($interval == "month") {
            $groupFunc = "toStartOfMonth";
        } elseif ($interval == "quarter") {
            $groupFunc = "toStartOfQuarter";
        } else {
            throw new \Exception("Invalid interval: $interval");
        }
        $query = "SELECT row_date, avgIf(cv, cv > -1) as row_value FROM (SELECT keyword,$groupFunc(date) as row_date, AVG($field) as avg_value, varSamp($field) as stdev_value, IF(stdev_value > 0, (stdev_value/avg_value*100), -1) as cv FROM {$this->database}.{$this->table} WHERE user_id={$this->user_id} AND site_id={$this->site_id} AND url='$url' GROUP BY keyword,row_date) GROUP BY row_date";
        $result = $this->db->select($query);
        $rows = $result->rows();
        return $rows;
    }

    public function getKeywordGraphData($keyword, $interval="month", $field, $aggFunc)
    {
        if ($interval == "week") {
            $groupFunc = "toStartOfWeek";
        } elseif ($interval == "month") {
            $groupFunc = "toStartOfMonth";
        } elseif ($interval == "quarter") {
            $groupFunc = "toStartOfQuarter";
        } else {
            throw new \Exception("Invalid interval: $interval");
        }
        $query = "SELECT $groupFunc(date) as row_date, $aggFunc($field) as row_value FROM {$this->database}.{$this->table} WHERE user_id={$this->user_id} AND site_id={$this->site_id} AND keyword='$keyword' GROUP BY row_date";
        $result = $this->db->select($query);
        $rows = $result->rows();
        return $rows;
    }

    public function getKeywordCVData($keyword, $interval="month", $field)
    {
        if ($interval == "week") {
            $groupFunc = "toStartOfWeek";
        } elseif ($interval == "month") {
            $groupFunc = "toStartOfMonth";
        } elseif ($interval == "quarter") {
            $groupFunc = "toStartOfQuarter";
        } else {
            throw new \Exception("Invalid interval: $interval");
        }
        $query = "SELECT $groupFunc(date) as row_date, AVG($field) as avg_value, varSamp($field) as stdev_value FROM {$this->database}.{$this->table} WHERE user_id={$this->user_id} AND site_id={$this->site_id} AND keyword='$keyword' GROUP BY row_date";
        $result = $this->db->select($query);
        $rows = $result->rows();
        return $rows;
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
