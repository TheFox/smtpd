<?php

namespace TheFox\Logger;

use DateTime;

class Logger
{
    const DEBUG = 100;
    const INFO = 200;
    const NOTICE = 250;
    const WARNING = 300;
    const ERROR = 400;
    const CRITICAL = 500;
    const ALERT = 550;
    const EMERGENCY = 600;

    /**
     * @var array
     */
    protected static $levels = [
        100 => 'DEBUG',
        200 => 'INFO',
        250 => 'NOTICE',
        300 => 'WARNING',
        400 => 'ERROR',
        500 => 'CRITICAL',
        550 => 'ALERT',
        600 => 'EMERGENCY',
    ];

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $handlers;

    /**
     * Logger constructor.
     * @param string $name
     */
    public function __construct($name = '')
    {
        if (@date_default_timezone_get() == 'UTC') {
            date_default_timezone_set('UTC');
        }

        $this->setName($name);
        $this->handlers = [];
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param StreamHandler $handler
     */
    public function pushHandler(StreamHandler $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * @param int $level
     * @param string $message
     */
    public function addRecord($level, $message)
    {
        $dt = new DateTime();

        $line = '[' . $dt->format('Y-m-d H:i:sO') . '] ' . $this->getName() . '.' . static::$levels[$level] . ': ' . $message . PHP_EOL;

        foreach ($this->handlers as $handler) {
            if ($level >= $handler->getLevel()) {
                file_put_contents($handler->getPath(), $line, FILE_APPEND);
            }
        }
    }

    /**
     * @param string $message
     */
    public function debug($message)
    {
        $this->addRecord(static::DEBUG, $message);
    }

    /**
     * @param string $message
     */
    public function info($message)
    {
        $this->addRecord(static::INFO, $message);
    }

    /**
     * @param string $message
     */
    public function notice($message)
    {
        $this->addRecord(static::NOTICE, $message);
    }

    /**
     * @param string $message
     */
    public function warning($message)
    {
        $this->addRecord(static::WARNING, $message);
    }

    /**
     * @param string $message
     */
    public function error($message)
    {
        $this->addRecord(static::ERROR, $message);
    }

    /**
     * @param string $message
     */
    public function critical($message)
    {
        $this->addRecord(static::CRITICAL, $message);
    }

    /**
     * @param string $message
     */
    public function alert($message)
    {
        $this->addRecord(static::ALERT, $message);
    }

    /**
     * @param string $message
     */
    public function emergency($message)
    {
        $this->addRecord(static::EMERGENCY, $message);
    }

    /**
     * @param int $number
     * @return string
     */
    public static function getLevelNameByNumber($number)
    {
        return static::$levels[$number];
    }
}
