<?php

namespace TheFox\Logger;

use DateTime;

class Logger{
	
	const DEBUG 		= 100;
	const INFO 			= 200;
	const NOTICE 		= 250;
	const WARNING 		= 300;
	const ERROR 		= 400;
	const CRITICAL 		= 500;
	const ALERT 		= 550;
	const EMERGENCY 	= 600;
	
	protected static $levels = array(
		100 => 'DEBUG',
		200 => 'INFO',
		250 => 'NOTICE',
		300 => 'WARNING',
		400 => 'ERROR',
		500 => 'CRITICAL',
		550 => 'ALERT',
		600 => 'EMERGENCY',
	);
	
	private $name;
	private $handlers;
	
	public function __construct($name = ''){
		if(@date_default_timezone_get() == 'UTC') date_default_timezone_set('UTC');
		
		$this->setName($name);
		$this->handlers = array();
	}
	
	public function setName($name){
		$this->name = $name;
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function pushHandler($handler){
		$this->handlers[] = $handler;
	}
	
	public function addRecord($level, $message){
		$dt = new DateTime();
		
		$line = '['.$dt->format('Y-m-d H:i:sO').'] '.$this->getName().'.'.static::$levels[$level].': '.$message.PHP_EOL;
		
		foreach($this->handlers as $handler){
			if($level >= $handler->getLevel()){
				file_put_contents($handler->getPath(), $line, FILE_APPEND);
			}
		}
	}
	
	public function debug($message){
		$this->addRecord(static::DEBUG, $message);
	}
	
	public function info($message){
		$this->addRecord(static::INFO, $message);
	}
	
	public function notice($message){
		$this->addRecord(static::NOTICE, $message);
	}
	
	public function warning($message){
		$this->addRecord(static::WARNING, $message);
	}
	
	public function error($message){
		$this->addRecord(static::ERROR, $message);
	}
	
	public function critical($message){
		$this->addRecord(static::CRITICAL, $message);
	}
	
	public function alert($message){
		$this->addRecord(static::ALERT, $message);
	}
	
	public function emergency($message){
		$this->addRecord(static::EMERGENCY, $message);
	}
	
	public static function getLevelNameByNumber($number){
		return static::$levels[$number];
	}
	
}
