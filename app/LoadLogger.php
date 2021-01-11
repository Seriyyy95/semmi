<?php

namespace App;

class LoadLogger
{
    private $user_id;
    private $source;

    public function __construct($user_id, $source)
    {
        $this->user_id = $user_id;
        $this->source = $source;
        $this->clean();
    }

    public function setUser($user_id)
    {
        $this->user_id = $user_id;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function write($message)
    {
        $record = new LogRecord();
        $record->user_id = $this->user_id;
        $record->source = $this->source;
        $record->message = $message;
        $record->save();
    }

    public function clean()
    {
        $ids = LogRecord::select("id")
            ->where("user_id", $this->user_id)
            ->orderBy("id", "DESC")
            ->limit("100")
            ->get()->pluck("id")->toArray();
        LogRecord::whereNotIn("id", $ids)->delete();
    }

    public function list()
    {
        return LogRecord::where("user_id", $this->user_id)
            ->where("source", $this->source)
            ->orderBy("id", "DESC")
            ->limit(1000)
            ->get();
    }
}
