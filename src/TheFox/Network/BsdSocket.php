<?php

namespace TheFox\Network;

use RuntimeException;

class BsdSocket extends AbstractSocket{
	
	public function __construct(){
		$handle = $this->create();
		if($handle){
			$this->setHandle($handle);
		}
	}
	
	/**
	 * Creates a new socket resource.
	 * https://secure.php.net/manual/en/function.socket-create.php
	 */
	public function create(){
		$socket = null;
		
		if( ($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0 ){ 
			$errno = socket_last_error();
			throw new RuntimeException('socket_create: '.socket_strerror($errno), $errno);
		}
		
		$ret = socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
		$ret = socket_get_option($socket, SOL_SOCKET, SO_KEEPALIVE);
		if($ret === false){
			$errno = socket_last_error($socket);
			throw new RuntimeException('socket_get_option SO_KEEPALIVE: '.socket_strerror($errno), $errno);
		}
		
		$ret = socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		$ret = socket_get_option($socket, SOL_SOCKET, SO_REUSEADDR);
		if($ret === false){
			$errno = socket_last_error($socket);
			throw new RuntimeException('socket_get_option SO_REUSEADDR: '.socket_strerror($errno), $errno);
		}
		
		$ret = socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
		$ret = socket_get_option($socket, SOL_SOCKET, SO_RCVTIMEO);
		if($ret === false){
			$errno = socket_last_error($socket);
			throw new RuntimeException('socket_get_option SO_RCVTIMEO: '.socket_strerror($errno), $errno);
		}
		
		$ret = socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 5, 'usec' => 0));
		$ret = socket_get_option($socket, SOL_SOCKET, SO_SNDTIMEO);
		if($ret === false){
			$errno = socket_last_error($socket);
			throw new RuntimeException('socket_get_option SO_SNDTIMEO: '.socket_strerror($errno), $errno);
		}
		
		socket_set_nonblock($socket);
		
		return $socket;
	}
	
	public function bind($ip, $port){
		return socket_bind($this->getHandle(), $ip, $port);
	}
	
	public function listen(){
		return socket_listen($this->getHandle(), 0);
	}
	
	public function connect($ip, $port){
		socket_connect($this->getHandle(), $ip, $port);
	}
	
	public function accept(){
		$handle = socket_accept($this->getHandle());
		if($handle !== false){
			$class = __CLASS__;
			$socket = new $class();
			$socket->setHandle($handle);
			return $socket;
		}
	}
	
	public function select(&$readHandles, &$writeHandles, &$exceptHandles){
		return socket_select($readHandles, $writeHandles, $exceptHandles, 0);
	}
	
	public function getPeerName(&$ip, &$port){
		return socket_getpeername($this->getHandle(), $ip, $port);
	}
	
	public function lastError(){
		return socket_last_error($this->getHandle());
	}
	
	public function strError(){
		return socket_strerror(socket_last_error($this->getHandle()));
	}
	
	public function clearError(){
		socket_clear_error($this->getHandle());
	}
	
	public function read(){
		return socket_read($this->getHandle(), 2048, PHP_BINARY_READ);
	}
	
	public function write($data){
		return socket_write($this->getHandle(), $data, strlen($data));
	}
	
	public function shutdown(){
		socket_shutdown($this->getHandle(), 2);
	}
	
	public function close(){
		socket_close($this->getHandle());
	}
	
}
