<?php

namespace App;

use ClickHouseDB\Client;

abstract class ClickHouse
{
    protected $db;
    protected $user_id;
    protected $site_id;
    protected $table;

    protected function __construct(string $table)
    {
        $config = [
            'host' => env("CLICKHOUSE_HOST"),
            'port' => env("CLICKHOUSE_PORT"),
            'username' => env("CLICKHOUSE_USER"),
            'password' => env("CLICKHOUSE_PASSWORD")
        ];
        $this->database = env('CLICKHOUSE_DATABASE');
        $this->db = new Client($config);
        $this->table = $table;
        $this->createDatabaseIfNotExist();
        $this->createTablesIfNotExist();
    }

    abstract public function index(array $data);
    abstract public function getUrls();
    abstract protected function createTablesIfNotExist();


    public function setUser(int $user_id)
    {
        $this->user_id = $user_id;
    }

    public function setSite(int $site_id)
    {
        $this->site_id = $site_id;
    }

    public function getPositionsHistory($periods, $url, $field="impressions", $function="sum")
    {
        $periodsData = array();
        $counter = 0;
        foreach ($periods as $period) {
            $periodsData[] = "{$function}If($field, date > '{$period['start_date']}' and date < '{$period['end_date']}') as row_$counter";
            $counter++;
        }
        $periodsString = implode(", ", $periodsData);
        $query = "SELECT url,keyword,$periodsString, count($field) as total FROM {$this->database}.{$this->table} WHERE url='$url' GROUP BY url,keyword ORDER BY url, total DESC LIMIT 100";
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
        $query = "SELECT url, sumIf($field, date > '{$firstPeriod["startDate"]}' and date < '{$firstPeriod["endDate"]}') as data, sumIf($field, date > '{$secondPeriod["startDate"]}' and date < '{$secondPeriod["endDate"]}') as previous_data, minus(previous_data, data) as result FROM {$this->database}.{$this->table} WHERE user_id={$this->user_id} AND site_id={$this->site_id} GROUP BY url ORDER BY data DESC";
        $result = $this->db->select($query);
        $data = $result->rows();
        return $data;
    }

    public function getKeywordsChangesData($url, $firstPeriod, $secondPeriod, $field)
    {
        $query = "SELECT keyword, sumIf($field, date > '{$firstPeriod["startDate"]}' and date < '{$firstPeriod["endDate"]}') as data, sumIf($field, date > '{$secondPeriod["startDate"]}' and date < '{$secondPeriod["endDate"]}') as previous_data, minus(previous_data, data) as result FROM {$this->database}.{$this->table} WHERE user_id={$this->user_id} AND site_id={$this->site_id} AND url='$url' GROUP BY keyword ORDER BY data DESC";
        $result = $this->db->select($query);
        $data = $result->rows();
        return $data;
    }


    public function delete()
    {
        $this->db->write("ALTER TABLE {$this->database}.{$this->table} DELETE WHERE site_id={$this->site_id}");
    }

    public function deleteOlderThan(string $date)
    {
        $this->db->write("ALTER TABLE {$this->database}.{$this->table} DELETE WHERE date >= '$date' AND site_id={$this->site_id}");
    }

    public function getMinDate()
    {
        $result = $this->db->select("SELECT MIN(date) as date FROM {$this->database}.{$this->table} WHERE site_id={$this->site_id} AND user_id={$this->user_id}");
        return $result->fetchOne()["date"];
    }

    public function getMaxDate()
    {
        $result = $this->db->select("SELECT MAX(date) as date FROM {$this->database}.{$this->table} WHERE site_id={$this->site_id} AND user_id={$this->user_id}");
        return $result->fetchOne()["date"];
    }


    private function createDatabaseIfNotExist()
    {
        $this->db->write("CREATE DATABASE IF NOT EXISTS " . $this->database);
    }
}
