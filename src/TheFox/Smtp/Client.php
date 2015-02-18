<?php

namespace TheFox\Smtp;

#use Exception;
#use RuntimeException;
#use InvalidArgumentException;
#use DateTime;

use Zend\Mail\Message;

use TheFox\Network\AbstractSocket;
use TheFox\Smtp\StringParser;

class Client{
	
	const MSG_SEPARATOR = "\r\n";
	
	private $id = 0;
	private $status = array();
	
	private $server = null;
	private $socket = null;
	private $ip = '';
	private $port = 0;
	private $recvBufferTmp = '';
	private $from = '';
	private $rcpt = array();
	private $mail = '';
	
	public function __construct(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->status['hasHello'] = false;
		$this->status['hasMail'] = false;
		$this->status['hasShutdown'] = false;
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function getStatus($name){
		if(array_key_exists($name, $this->status)){
			return $this->status[$name];
		}
		return null;
	}
	
	public function setStatus($name, $value){
		$this->status[$name] = $value;
	}
	
	public function setServer(Server $server){
		$this->server = $server;
	}
	
	public function getServer(){
		return $this->server;
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function setSocket(AbstractSocket $socket){
		$this->socket = $socket;
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function getSocket(){
		return $this->socket;
	}
	
	public function setIp($ip){
		$this->ip = $ip;
	}
	
	public function getIp(){
		if(!$this->ip){
			$this->setIpPort();
		}
		return $this->ip;
	}
	
	public function setPort($port){
		$this->port = $port;
	}
	
	public function getPort(){
		if(!$this->port){
			$this->setIpPort();
		}
		return $this->port;
	}
	
	public function setIpPort($ip = '', $port = 0){
		// @codeCoverageIgnoreStart
		if(!TEST){
			$this->getSocket()->getPeerName($ip, $port);
		}
		// @codeCoverageIgnoreEnd
		
		$this->setIp($ip);
		$this->setPort($port);
	}
	
	public function getIpPort(){
		return $this->getIp().':'.$this->getPort();
	}
	
	public function getLog(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		if($this->getServer()){
			return $this->getServer()->getLog();
		}
		
		return null;
	}
	
	private function log($level, $msg){
		#print __CLASS__.'->'.__FUNCTION__.': '.$level.', '.$msg."\n";
		#fwrite(STDOUT, "log: $level, $msg\n");
		
		if($this->getLog()){
			if(method_exists($this->getLog(), $level)){
				$this->getLog()->$level($msg);
			}
		}
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function dataRecv(){
		$data = $this->getSocket()->read();
		
		#print __CLASS__.'->'.__FUNCTION__.': "'.$data.'"'."\n";
		do{
			$separatorPos = strpos($data, static::MSG_SEPARATOR);
			if($separatorPos === false){
				$this->recvBufferTmp .= $data;
				$data = '';
				
				$this->log('debug', 'client '.$this->id.': collect data');
			}
			else{
				$msg = $this->recvBufferTmp.substr($data, 0, $separatorPos);
				$this->recvBufferTmp = '';
				
				$this->msgHandle($msg);
				
				$data = substr($data, $separatorPos + strlen(static::MSG_SEPARATOR));
				
				#print __CLASS__.'->'.__FUNCTION__.': rest data "'.$data.'"'."\n";
			}
		}while($data);
	}
	
	public function msgHandle($msgRaw){
		#$this->log('debug', 'client '.$this->id.' raw: /'.$msgRaw.'/');
		
		$rv = '';
		
		$str = new StringParser($msgRaw);
		$args = $str->parse();
		#ve($args);
		
		$command = array_shift($args);
		$commandcmp = strtolower($command);
		
		
		if($commandcmp == 'helo'){
			#$this->log('debug', 'client '.$this->id.' helo');
			$this->setStatus('hasHello', true);
			
			return $this->sendOk('localhost.localdomain');
		}
		elseif($commandcmp == 'ehlo'){
			#$this->log('debug', 'client '.$this->id.' helo');
			
			return $this->sendCommandNotImplemented();
		}
		elseif($commandcmp == 'mail'){
			#$this->log('debug', 'client '.$this->id.' mail');
			
			#ve($args);
			
			if($this->getStatus('hasHello')){
				if(isset($args[0]) && $args[0]){
					$this->setStatus('hasMail', true);
					$from = $args[0];
					if(substr(strtolower($from), 0, 6) == 'from:<'){
						$from = substr(substr($from, 6), 0, -1);
					}
					#$this->log('debug', 'client '.$this->id.' from: /'.$from.'/');
					$this->from = $from;
					$this->mail = '';
					return $this->sendOk();
				}
				else{
					return $this->sendSyntaxErrorInParameters();
				}
			}
			else{
				return $this->sendSyntaxErrorCommandUnrecognized();
			}
		}
		elseif($commandcmp == 'rcpt'){
			#$this->log('debug', 'client '.$this->id.' rcpt');
			
			#ve($args);
			
			if($this->getStatus('hasHello')){
				if(isset($args[0]) && $args[0]){
					$this->setStatus('hasMail', true);
					$rcpt = $args[0];
					if(substr(strtolower($rcpt), 0, 4) == 'to:<'){
						$rcpt = substr(substr($rcpt, 4), 0, -1);
						$this->rcpt[] = $rcpt;
					}
					#$this->log('debug', 'client '.$this->id.' rcpt: /'.$rcpt.'/');
					return $this->sendOk();
				}
				else{
					return $this->sendSyntaxErrorInParameters();
				}
			}
			else{
				return $this->sendSyntaxErrorCommandUnrecognized();
			}
		}
		elseif($commandcmp == 'data'){
			#$this->log('debug', 'client '.$this->id.' data');
			
			if($this->getStatus('hasHello')){
				$this->setStatus('hasData', true);
				return $this->sendDataResponse();
			}
			else{
				return $this->sendSyntaxErrorCommandUnrecognized();
			}
		}
		elseif($commandcmp == 'noop'){
			return $this->sendOk();
		}
		elseif($commandcmp == 'quit'){
			$rv .= $this->sendQuit();
			$this->shutdown();
		}
		else{
			if($this->getStatus('hasData')){
				if($msgRaw == '.'){
					
					$this->mail = substr($this->mail, 0, -strlen(static::MSG_SEPARATOR));
					
					$zmail = Message::fromString($this->mail);
					
					$this->getServer()->mailNew($this->from, $this->rcpt, $zmail);
					$this->from = '';
					$this->rcpt = array();
					$this->mail = '';
					
					return $this->sendOk();
				}
				else{
					$this->mail .= $msgRaw.static::MSG_SEPARATOR;
				}
			}
			else{
				$this->log('debug', 'client '.$this->id.' not implemented: /'.$command.'/ - /'.join('/ /', $args).'/');
				return $this->sendSyntaxErrorCommandUnrecognized();
			}
		}
		
		return $rv;
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	private function dataSend($msg){
		$output = $msg.static::MSG_SEPARATOR;
		if($this->getSocket()){
			$tmp = $msg;
			$tmp = str_replace("\r", '', $tmp);
			$tmp = str_replace("\n", '\\n', $tmp);
			$this->log('debug', 'client '.$this->id.' data send: "'.$tmp.'"');
			$this->getSocket()->write($output);
		}
		return $output;
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function sendReady(){
		return $this->dataSend('220 localhost.localdomain SMTP Service Ready');
	}
	
	public function sendQuit(){
		return $this->dataSend('221 localhost.localdomain Service closing transmission channel');
	}
	
	private function sendOk($text = 'OK'){
		return $this->dataSend('250 '.$text);
	}
	
	private function sendDataResponse(){
		return $this->dataSend('354 Start mail input; end with <CRLF>.<CRLF>');
	}
	
	private function sendSyntaxErrorCommandUnrecognized(){
		return $this->dataSend('500 Syntax error, command unrecognized');
	}
	
	private function sendSyntaxErrorInParameters(){
		return $this->dataSend('501 Syntax error in parameters or arguments');
	}
	
	private function sendCommandNotImplemented(){
		return $this->dataSend('502 Command not implemented');
	}
	
	public function shutdown(){
		if(!$this->getStatus('hasShutdown')){
			$this->setStatus('hasShutdown', true);
			
			// @codeCoverageIgnoreStart
			if($this->getSocket()){
				$this->getSocket()->shutdown();
				$this->getSocket()->close();
			}
			// @codeCoverageIgnoreEnd
		}
	}
	
}
