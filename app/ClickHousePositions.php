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
        $params = array(
            "database" => $this->database,
            "site_id" => $this->site_id,
        );
        $query = "SELECT url, SUM(impressions) as total_impressions FROM {database}.positions WHERE AND site_id={site_id} GROUP BY url ORDER BY total_impressions DESC";
        $result = $this->db->select($query, $params);
        return $result->rows();
    }

    public function getAllUrlKeywords($url)
    {
        $params = array(
            "database" => $this->database,
            "site_id" => $this->site_id,
            "url" => $url,
        );

        $query = "SELECT keyword, SUM(impressions) as total_impressions, SUM(clicks) as total_clicks, ROUND(AVG(avg_position),2) as avg_position FROM {database}.positions WHERE site_id={site_id} AND url='{url}' GROUP BY keyword ORDER BY total_impressions DESC";
        $result = $this->db->select($query, $params);
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

        $params = array(
            "database" => $this->database,
            "table" => $this->table,
            "site_id" => $this->site_id,
            "aggFunc" => $aggFunc,
            "groupFunc" => $groupFunc,
            "field" => $field,
            "url" => $url,
        );

        $query = "SELECT {groupFunc}(date) as row_date, {aggFunc}({field}) as row_value FROM {database}.{table} WHERE site_id={site_id} AND url='{url}' GROUP BY row_date";
        $result = $this->db->select($query, $params);
        return $result->rows();
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
        $params = array(
            "database" => $this->database,
            "table" => $this->table,
            "site_id" => $this->site_id,
            "groupFunc" => $groupFunc,
            "field" => $field,
            "url" => $url,
            "interval" => $interval,
        );

        $query = "SELECT row_date, avgIf(cv, cv > -1) as row_value FROM (SELECT keyword,{groupFunc}(date) as row_date, AVG({field}) as avg_value, varSamp({field}) as stdev_value, IF(stdev_value > 0, (stdev_value/avg_value*100), -1) as cv FROM {database}.{table} WHERE site_id={site_id} AND url='{url}' GROUP BY keyword,row_date) GROUP BY row_date";
        $result = $this->db->select($query, $params);
        return $result->rows();
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
        $params = array(
            "database" => $this->database,
            "table" => $this->table,
            "site_id" => $this->site_id,
            "groupFunc" => $groupFunc,
            "field" => $field,
            "interval" => $interval,
            "aggFunc" => $aggFunc,
            "keyword" => $keyword
        );

        $query = "SELECT {groupFunc}(date) as row_date, {aggFunc}({field}) as row_value FROM {database}.{table} WHERE site_id={site_id} AND keyword='{keyword}' GROUP BY row_date";
        $result = $this->db->select($query, $params);
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
        $params = array(
            "database" => $this->database,
            "table" => $this->table,
            "site_id" => $this->site_id,
            "groupFunc" => $groupFunc,
            "field" => $field,
            "interval" => $interval,
            "keyword" => $keyword
        );

        $query = "SELECT {groupFunc}(date) as row_date, AVG({field}) as avg_value, varSamp({field}) as stdev_value FROM {database}.{table} WHERE site_id={site_id} AND keyword='{keyword}' GROUP BY row_date";
        $result = $this->db->select($query, $params);
        return $result->rows();
    }


    public function getHistoryData($periods, $url, $field="impressions", $function="sum")
    {
        $periodsData = array();
        $counter = 0;
        foreach ($periods as $period) {
            $periodsData[] = "{function}If({field}, date > '{$period['start_date']}' and date < '{$period['end_date']}') as row_$counter";
            $counter++;
        }
        $periodsString = implode(", ", $periodsData);
        $params = array(
            "database" => $this->database,
            "table" => $this->table,
            "site_id" => $this->site_id,
            "field" => $field,
            "function" => $function,
        );

        $query = "SELECT url,keyword,$periodsString, count({field}) as total FROM {database}.positions WHERE url='{url}' GROUP BY url,keyword ORDER BY url, total DESC LIMIT 100";
        $summaryQuery = "SELECT url,$periodsString FROM {database}.positions WHERE url='{url}' GROUP BY url LIMIT 1";
        $result = $this->db->select($query, $params);
        $summary = $this->db->select($summaryQuery, $params)->fetchOne();
        $summary["keyword"] = "Общее";
        $data = $result->rows();
        array_unshift($data, $summary);
        return $data;
    }

    public function getKeywordsChangesData(string $url, array $firstPeriod, array $secondPeriod, string $field)
    {
        $params = array(
            "database" => $this->database,
            "table" => $this->table,
            "site_id" => $this->site_id,
            "field" => $field,
            "url" => $url,
            "first_period_start_date" => $firstPeriod["startDate"],
            "first_period_end_date" => $firstPeriod["endDate"],
            "second_period_start_date" => $secondPeriod["startDate"],
            "second_period_end_date" => $secondPeriod["endDate"],
        );

        $query = "SELECT keyword, sumIf({field}, date > '{first_period_start_date}' and date < '{first_period_end_date}') as data, sumIf({field}, date > '{second_period_start_date}' and date < '{second_period_end_date}') as previous_data, minus(previous_data, data) as result FROM {database}.positions WHERE site_id={site_id} AND url='{url}' GROUP BY keyword ORDER BY data DESC";
        $result = $this->db->select($query, $params);
        return $result->rows();
    }

    protected function createTablesIfNotExist()
    {
        $params = array(
            "database" => $this->database,
            "table" => $this->table,
        );

        $this->db->write("
            CREATE TABLE IF NOT EXISTS {database}.{table} (
                id UInt64,
                date Date,
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
        ", $params);
    }
}
