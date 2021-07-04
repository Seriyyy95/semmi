<?php

namespace App\Services\Logger;

use App\Models\LogRecord;

class LoadLogger
{
    private string $source;

    public function __construct($source)
    {
        $this->source = $source;
        $this->clean();
    }

    public function withSource(string $source)
    {
        $this->source = $source;

        return $this;
    }

    public function write($message, $context = null)
    {
        $record = new LogRecord();
        $record->source = $this->source;
        $record->message = $message;

        if($context && $context instanceof \Throwable){
           $data = array(
               'exception' => get_class($context),
               'message' => $context->getMessage(),
               'line' => $context->getLine(),
               'file' => $context->getFile(),
               'trace' => $context->getTrace()
           );
           $record->context = $data;
        }
        $record->saveOrFail();
    }

    public function clean()
    {
        $ids = LogRecord::select("id")
            ->where('source', $this->source)
            ->orderBy("id", "DESC")
            ->limit("100")
            ->get()->pluck("id")->toArray();
        LogRecord::whereNotIn("id", $ids)
            ->where('source', $this->source)
            ->delete();
    }

    public function list()
    {
        return LogRecord::where("source", $this->source)
            ->orderBy("id", "DESC")
            ->limit(1000)
            ->get();
    }
}
