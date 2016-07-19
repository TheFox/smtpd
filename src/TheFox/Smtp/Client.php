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
	private $hostname = '';
	private $credentials = array();
 	private $extended_commands = array('AUTH PLAIN LOGIN', 'STARTTLS', 'HELP');

	public function __construct($hostname = 'localhost.localdomain'){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->hostname = $hostname;
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
	
	public function setCredentials($credentials = array()){
		$this->credentials = $credentials;
	}
	
	public function getCredentials(){
		return $this->credentials;
	}
	
	public function getHostname(){
		return $this->hostname;
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

			return $this->sendOk($this->getHostname());
		}
		elseif($commandcmp == 'ehlo'){
			#$this->log('debug', 'client '.$this->id.' helo');
			$this->setStatus('hasHello', true);
			$msg = '250-'.$this->getHostname().static::MSG_SEPARATOR;
			$count = count($this->extendedCommands) - 1;
			
			for($i = 0; $i < $count; $i++){
				$msg .= '250-'.$this->extendedCommands[$i].static::MSG_SEPARATOR;
			}
			
			$msg .= '250 '.end($this->extendedCommands);

			return $this->dataSend($msg);
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
		elseif($commandcmp == 'auth'){
			$this->setStatus('hasAuth', true);
			
			if(empty($args)){
				return $this->sendSyntaxErrorInParameters();
			}
			
			$authentication = strtolower($args[0]);

			if($authentication == 'plain'){
				$this->setStatus('hasAuthPlain', true);

				if(isset($args[1])){
					$this->setStatus('hasAuthPlainUser', true);
					$this->setCredentials(array($args[1]));

					if($this->authenticate('plain')){
						return $this->sendAuthSuccessResponse();
					}
					
					return $this->sendAuthInvalid();
				}

				return $this->sendAuthPlainResponse();
			}
			elseif($authentication == 'login'){
				$this->setStatus('hasAuthLogin', true);

				return $this->sendAskForUserResponse();
			}
			elseif($authentication == 'cram-md5'){
				return $this->sendCommandNotImplemented();
			}
			else{
				return $this->sendSyntaxErrorInParameters();
			}
		}
		elseif($commandcmp == 'starttls'){
			$this->sendStartTls();
			
			return $this->getSocket()->enableEncryption();
		}
		elseif($commandcmp == 'help'){
			return $this->sendOk('HELO, EHLO, MAIL FROM, RCPT TO, DATA, NOOP, QUIT');
		}
		else{
			if($this->getStatus('hasAuth')){
				if($this->getStatus('hasAuthPlain')){
					$this->setStatus('hasAuthPlainUser', true);
					$this->setCredentials(array($command));

					if($this->authenticate('plain')){
						return $this->sendAuthSuccessResponse();
					}
					
					return $this->sendAuthInvalid();
				}
				elseif($this->getStatus('hasAuthLogin')){
					$credentials = $this->getCredentials();

					if($this->getStatus('hasAuthLoginUser')){
						$credentials['password'] = $command;
						$this->setCredentials($credentials);

						if($this->authenticate('login')){
							return $this->sendAuthSuccessResponse();
						}
						
						return $this->sendAuthInvalid();
					}

					$this->setStatus('hasAuthLoginUser', true);
					$credentials['user'] = $command;
					$this->setCredentials($credentials);

					return $this->sendAskForPasswordResponse();
				}
			}
			elseif($this->getStatus('hasData')){
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
	public function authenticate($method){
		$attempt = $this->getServer()->authenticateUser($method, $this->getCredentials());
		
		$this->setStatus('hasAuth', false);
		$this->setStatus('hasAuth'.ucfirst($method), false);
		$this->setStatus('hasAuth'.ucfirst($method).'User', false);
		
		if(!$attempt){
			return false;
		}
		
		return true;
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function sendReady(){
		return $this->dataSend('220 '.$this->getHostname().' SMTP Service Ready');
	}
	
	public function sendQuit(){
		return $this->dataSend('221 '.$this->getHostname().' Service closing transmission channel');
	}
	
	private function sendOk($text = 'OK'){
		return $this->dataSend('250 '.$text);
	}
	
	private function sendDataResponse(){
		return $this->dataSend('354 Start mail input; end with <CRLF>.<CRLF>');
	}
	
	private function sendAuthPlainResponse(){
		return $this->dataSend('334 ');
	}
	
	private function sendAuthSuccessResponse(){
		return $this->dataSend('235 2.7.0 Authentication successful');
	}
	
	private function sendAskForUserResponse(){
		return $this->dataSend('334 VXNlcm5hbWU6');
	}
	
	private function sendAskForPasswordResponse(){
		return $this->dataSend('334 UGFzc3dvcmQ6');
	}
	
	private function sendStartTls(){
		return $this->dataSend('220 TLS go ahead');
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
	
	private function sendAuthInvalid(){
		return $this->dataSend('535 Authentication credentials invalid');
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
