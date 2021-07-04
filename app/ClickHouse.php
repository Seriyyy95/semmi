<?php

namespace App;

use ClickHouseDB\Client;

abstract class ClickHouse
{
    protected Client $db;
    protected int $site_id;
    protected string $table;
    protected string $database;

    protected function __construct(string $table)
    {
        $config = [
            'host' => \config('services.clickhouse.host'),
            'port' => \config('services.clickhouse.port'),
            'username' => \config('services.clickhouse.username'),
            'password' => \config('services.clickhouse.password'),
        ];
        $this->database = \config('services.clickhouse.database');
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
        $valuesArray = array();
        $keysArray = array();
        foreach ($data as $row) {
            $row["site_id"] = $this->site_id;
            $valuesArray[] = array_values($row);
            if (count($keysArray) == 0) {
                $keysArray = array_keys($row);
            }
        }
        $stats = $this->db->insert(
            "{$this->database}.{$this->table}",
            $valuesArray,
            $keysArray
        );
        return $stats;
    }

    public function getMaxValue($field, $interval, $aggFunc)
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
            "table" => "{$this->database}.{$this->table}",
            "groupFunc" => $groupFunc,
            "aggFunc" => $aggFunc,
            "field" => $field,
            "site_id" => $this->site_id,
        );
        $query = "SELECT MAX(data) as max_value FROM (SELECT url,{groupFunc}(date) as group_date, {aggFunc}({field}) as data FROM {table} WHERE site_id={site_id} GROUP BY url,group_date)";

        $result = $this->db->select($query, $params);

        $rows = $result->rows();
        if (count($rows) > 0) {
            return $rows[0]["max_value"];
        } else {
            return 0;
        }
    }

    public function listTables()
    {
        $query = "SHOW TABLES";
        $result = $this->db->select($query);
        return $result->rows();
    }

    public static function execute($query)
    {
        $config = [
            'host' => \config('services.clickhouse.host'),
            'port' => \config('services.clickhouse.port'),
            'username' => \config('services.clickhouse.username'),
            'password' => \config('services.clickhouse.password'),
        ];
        $database = \config('services.clickhouse.database');

        $db = new Client($config);
        $db->database($database);
        $result = $db->select($query);
        return $result->rows();
    }

    public function setSite(int $site_id)
    {
        $this->site_id = $site_id;
    }

    public function delete()
    {
        $params = array(
            "table" => "{$this->database}.{$this->table}",
            "site_id" => $this->site_id,
        );
        $this->db->write("ALTER TABLE {table} DELETE WHERE site_id={site_id}", $params);
    }

    public function deleteOlderThan(string $date)
    {
        $params = array(
            "table" => "{$this->database}.{$this->table}",
            "site_id" => $this->site_id,
            "date" => $date
        );

        $this->db->write("ALTER TABLE {table} DELETE WHERE date >= '{date}' AND site_id={site_id}", $params);
    }

    public function getMinDate()
    {
        $params = array(
            "table" => "{$this->database}.{$this->table}",
            "site_id" => $this->site_id,
        );
        $result = $this->db->select("SELECT MIN(date) as date FROM {table} WHERE site_id={site_id}", $params);
        return $result->fetchOne()["date"];
    }

    public function getMaxDate()
    {
        $params = array(
            "table" => "{$this->database}.{$this->table}",
            "site_id" => $this->site_id,
        );

        $result = $this->db->select("SELECT MAX(date) as date FROM {table} WHERE site_id={site_id}", $params);
        return $result->fetchOne()["date"];
    }


    private function createDatabaseIfNotExist()
    {
        $params = array(
            "database" => $this->database
        );
        $this->db->write("CREATE DATABASE IF NOT EXISTS {database}", $params);
    }
}
