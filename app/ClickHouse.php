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

    abstract public function getUrls();
    abstract protected function createTablesIfNotExist();

    public function index(array $data)
    {
        if (count($data) == 0) {
            return;
        }
        $keys = array_keys($data[0]);
        $keys[] = "site_id";
        $keys[] = "user_id";
        $keysString = implode(",", $keys);
        $valuesArray = array();
        foreach ($data as $row) {
            $row["site_id"] = $this->site_id;
            $row["user_id"] = $this->user_id;
            $valuesArray[] .= "('" . implode("','", array_values($row)) . "') ";
        }
        $valuesString = implode(", ", $valuesArray);
        $this->db->write("INSERT INTO {$this->database}.{$this->table} ($keysString) VALUES $valuesString");
    }

    public function getMaxValue($field, $interval, $aggFunc){
        if($interval == "week"){
            $groupFunc = "toStartOfWeek";
        }elseif($interval == "month"){
            $groupFunc = "toStartOfMonth";
        }elseif($interval == "quarter"){
            $groupFunc = "toStartOfQuarter";
        }else{
            throw new \Exception("Invalid interval: $interval");
        }
        $query = "SELECT MAX(data) as max_value FROM (SELECT url,$groupFunc(date) as group_date, $aggFunc($field) as data FROM {$this->database}.{$this->table} WHERE user_id={$this->user_id} AND site_id={$this->site_id} GROUP BY url,group_date)";
        $result = $this->db->select($query);
        $rows = $result->rows();
        if (count($rows) > 0) {
            return $rows[0]["max_value"];
        }else{
            return 0;
        }

    }


    public function setUser(int $user_id)
    {
        $this->user_id = $user_id;
    }

    public function setSite(int $site_id)
    {
        $this->site_id = $site_id;
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
