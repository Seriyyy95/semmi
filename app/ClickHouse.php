<?php

namespace App;

use ClickHouseDB\Client;

class ClickHouse
{
    private $db;
    private $user_id;
    private $site_id;
    private static $instance = null;

    private function __construct()
    {
        $config = [
            'host' => env("CLICKHOUSE_HOST"),
            'port' => env("CLICKHOUSE_PORT"),
            'username' => env("CLICKHOUSE_USER"),
            'password' => env("CLICKHOUSE_PASSWORD")
        ];
        $this->database = env('CLICKHOUSE_DATABASE');
        $this->db = new Client($config);
        $this->createDatabaseIfNotExist();
        $this->createTablesIfNotExist();
    }

    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setUser(int $user_id)
    {
        $this->user_id = $user_id;
    }

    public function setSite(int $site_id)
    {
        $this->site_id = $site_id;
    }

    public function index(array $data){
        if(count($data) == 0){
            return;
        }
        $keys = array_keys($data[0]);
        $keys[] = "site_id";
        $keys[] = "user_id";
        $keysString = implode(",", $keys);
        $valuesArray = array();
        foreach($data as $row){
            $row["site_id"] = $this->site_id;
            $row["user_id"] = $this->user_id;
            $valuesArray[] .= "('" . implode("','", array_values($row)) . "') ";
        }
        $valuesString = implode(", ", $valuesArray);
        $this->db->write("INSERT INTO {$this->database}.positions ($keysString) VALUES $valuesString");
    }

    public function getUrls(){
        $query = "SELECT url, SUM(impressions) as total_impressions FROM {$this->database}.positions WHERE user_id={$this->user_id} AND site_id={$this->site_id} GROUP BY url ORDER BY total_impressions DESC";
        $result = $this->db->select($query);
        return $result->rows();
    }

    public function getPositionsHistory($periods, $url, $field="impressions", $function="sum"){
        $periodsData = array();
        $counter = 0;
        foreach($periods as $period){
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

    public function getChangesData($field, $firstPeriod, $secondPeriod){
        $query = "SELECT url, sumIf($field, date > '{$firstPeriod["startDate"]}' and date < '{$firstPeriod["endDate"]}') as data, sumIf($field, date > '{$secondPeriod["startDate"]}' and date < '{$secondPeriod["endDate"]}') as previous_data, minus(previous_data, data) as result FROM {$this->database}.positions WHERE user_id={$this->user_id} AND site_id={$this->site_id} GROUP BY url ORDER BY data DESC";
        $result = $this->db->select($query);
        $data = $result->rows();
        return $data;
    }

    public function getKeywordsChangesData($url, $firstPeriod, $secondPeriod, $field){
        $query = "SELECT keyword, sumIf($field, date > '{$firstPeriod["startDate"]}' and date < '{$firstPeriod["endDate"]}') as data, sumIf($field, date > '{$secondPeriod["startDate"]}' and date < '{$secondPeriod["endDate"]}') as previous_data, minus(data, previous_data) as result FROM {$this->database}.positions WHERE user_id={$this->user_id} AND site_id={$this->site_id} AND url='$url' GROUP BY keyword ORDER BY data DESC";
        $result = $this->db->select($query);
        $data = $result->rows();
        return $data;
    }


    public function delete(){
    $this->db->write("ALTER TABLE {$this->database}.positions DELETE WHERE site_id={$this->site_id}");
    }

    public function deleteOlderThan(string $date){
        $this->db->write("ALTER TABLE {$this->database}.positions DELETE WHERE date >= '$date' AND site_id={$this->site_id}");
    }

    public function getMinDate(){
        $result = $this->db->select("SELECT MIN(date) as date FROM {$this->database}.positions WHERE site_id={$this->site_id} AND user_id={$this->user_id}");
        return $result->fetchOne()["date"];
    }

    public function getMaxDate(){
        $result = $this->db->select("SELECT MAX(date) as date FROM {$this->database}.positions WHERE site_id={$this->site_id} AND user_id={$this->user_id}");
        return $result->fetchOne()["date"];
    }


    private function createDatabaseIfNotExist()
    {
        $this->db->write("CREATE DATABASE IF NOT EXISTS " . $this->database);
    }

    private function createTablesIfNotExist()
    {
        $this->db->write("
            CREATE TABLE IF NOT EXISTS {$this->database}.positions (
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
