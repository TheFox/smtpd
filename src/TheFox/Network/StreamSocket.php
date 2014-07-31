<?php

namespace TheFox\Network;

use RuntimeException;

class StreamSocket extends AbstractSocket{
	
	private $ip = '';
	private $port = 0;
	
	public function bind($ip, $port){
		$this->ip = $ip;
		$this->port = $port;
		return true;
	}
	
	public function listen(){
		$handle = @stream_socket_server('tcp://'.$this->ip.':'.$this->port, $errno, $errstr);
		if($handle !== false){
			$this->setHandle($handle);
			return true;
		}
		else{
			throw new RuntimeException($errstr, $errno);
		}
	}
	
	public function connect($ip, $port){
		#print __CLASS__.'->'.__FUNCTION__.': "'.$ip.'", "'.$port.'"'."\n";
		
		$handle = @stream_socket_client('tcp://'.$ip.':'.$port, $errno, $errstr, 2);
		if($handle !== false){
			$this->setHandle($handle);
			return true;
		}
		else{
			throw new RuntimeException($errstr, $errno);
		}
	}
	
	public function accept(){
		$handle = @stream_socket_accept($this->getHandle(), 2);
		if($handle !== false){
			$class = __CLASS__;
			$socket = new $class();
			$socket->setHandle($handle);
		}
		return $socket;
	}
	
	public function select(&$readHandles, &$writeHandles, &$exceptHandles){
		return @stream_select($readHandles, $writeHandles, $exceptHandles, 0);
	}
	
	public function getPeerName(&$ip, &$port){
		$ip = 'N/A';
		$port = -1;
		$name = stream_socket_get_name($this->getHandle(), true);
		$pos = strpos($name, ':');
		if($pos === false){
			$ip = $name;
		}
		else{
			$ip = substr($name, 0, $pos);
			$port = substr($name, $pos + 1);
		}
		#print __CLASS__.'->'.__FUNCTION__.': '.$name.', "'.$ip.'", "'.$port.'"'."\n";
	}
	
	public function lastError(){
		
	}
	
	public function strError(){
		
	}
	
	public function clearError(){
		
	}
	
	public function read(){
		return stream_socket_recvfrom($this->getHandle(), 2048);
	}
	
	public function write($data){
		$rv = @stream_socket_sendto($this->getHandle(), $data);
		
		#print __CLASS__.'->'.__FUNCTION__.': '.$rv.', "'.substr($data, 0, -1).'"'."\n";
		return $rv;
	}
	
	public function shutdown(){
		stream_socket_shutdown($this->getHandle(), STREAM_SHUT_RDWR);
	}
	
	public function close(){
		
	}
	
}
