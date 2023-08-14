<?php

namespace App\Logging;

// use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;

class MySQLLoggingHandler extends AbstractProcessingHandler
{
    protected $table;
    protected $connection;

    public function __construct($level = Logger::DEBUG, $bubble = true)
    {
        $this->table      = env('DB_LOG_TABLE');
        $this->connection = env('SECOND_DB_DRIVER');

        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $data = array(
            'message'       => $record['message'],
            'context'       => json_encode($record['context']),
            'level'         => $record['level'],
            'level_name'    => $record['level_name'],
            'channel'       => $record['channel'],
            'record_datetime' => $record['datetime']->format('Y-m-d H:i:s'),
            'extra'         => json_encode($record['extra']),
            'formatted'     => $record['formatted'],
            'remote_addr'   => $_SERVER['REMOTE_ADDR'],
            'user_agent'    => $_SERVER['HTTP_USER_AGENT'],
            'created_at'    => date("Y-m-d H:i:s"),
        );

        DB::connection($this->connection)->table($this->table)->insert($data);
    }
}
