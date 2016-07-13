<?php

namespace TheFox\Smtp;

class Event{
	
	const TRIGGER_MAIL_NEW = 1000;
	const TRIGGER_AUTH_ATTEMPT = 9000;
	
	private $trigger = null;
	private $object = null;
	private $function = null;
	private $returnValue = null;
	
	public function __construct($trigger = null, $object = null, $function = null){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		$this->trigger = $trigger;
		$this->object = $object;
		$this->function = $function;
	}
	
	public function getTrigger(){
		return $this->trigger;
	}
	
	public function getReturnValue(){
		return $this->returnValue;
	}
	
	public function execute($args = array()){
		#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.''."\n");
		
		$object = $this->object;
		$function = $this->function;
		
		array_unshift($args, $this);
		
		if($object){
			$this->returnValue = call_user_func_array(array($object, $function), $args);
		}
		else{
			$this->returnValue = call_user_func_array($function, $args);
		}
		
		return $this->returnValue;
	}
	
}
