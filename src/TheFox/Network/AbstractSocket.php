<?php

namespace TheFox\Network;

/**
 * @codeCoverageIgnore
 */
abstract class AbstractSocket{
	
	private $handle;
	
	public function setHandle($handle){
		$this->handle = $handle;
	}
	
	public function getHandle(){
		return $this->handle;
	}
	
	#abstract public function create();
	
	abstract public function bind($ip, $port);
	
	abstract public function listen();
	
	abstract public function connect($ip, $port);
	
	abstract public function accept();
	
	abstract public function select(&$readHandles, &$writeHandles, &$exceptHandles);
	
	abstract public function getPeerName(&$ip, &$port);
	
	abstract public function lastError();
	
	abstract public function strError();
	
	abstract public function clearError();
	
	abstract public function read();
	
	abstract public function write($data);
	
	abstract public function shutdown();
	
	abstract public function close();
	
}
