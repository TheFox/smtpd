<?php

namespace TheFox\Smtp;

use Exception;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;
use TheFox\Network\Socket;

class Server extends Thread{
	
	const LOOP_USLEEP = 10000;
	
	private $log;
	private $socket;
	private $isListening = false;
	private $ip;
	private $port;
	private $clientsId = 0;
	private $clients = array();
	private $eventsId = 0;
	private $events = array();
	private $hostname;
	
	public function __construct($ip = '127.0.0.1', $port = 20025, $hostname = 'localhost.localdomain'){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		
		$this->setIp($ip);
		$this->setPort($port);
		$this->setHostname($hostname);
	}
	
	public function getHostname(){
		return $this->hostname;
	}
	
	public function setHostname($hostname){
		$this->hostname = $hostname;
	}
	
	public function setLog($log){
		$this->log = $log;
	}
	
	public function getLog(){
		return $this->log;
	}
	
	public function setIp($ip){
		$this->ip = $ip;
	}
	
	public function setPort($port){
		$this->port = $port;
	}
	
	public function init(){
		if(!$this->log){
			$this->log = new Logger('server');
			$this->log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
			if(file_exists('log')){
				$this->log->pushHandler(new StreamHandler('log/server.log', Logger::DEBUG));
			}
		}
		// @codeCoverageIgnoreStart
		if(!TEST){
			$this->log->info('start');
			$this->log->info('ip = "'.$this->ip.'"');
			$this->log->info('port = "'.$this->port.'"');
		}
		// @codeCoverageIgnoreEnd
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function listen($contextOptions){
		if($this->ip && $this->port){
			#$this->log->notice('listen on '.$this->ip.':'.$this->port);
			
			$this->socket = new Socket();
			
			$bind = false;
			try{
				$bind = $this->socket->bind($this->ip, $this->port);
			}
			catch(Exception $e){
				$this->log->error($e->getMessage());
			}
			
			if($bind){
				try{
					if($this->socket->listen($contextOptions)){
						$this->log->notice('listen ok');
						$this->isListening = true;
						
						return true;
					}
				}
				catch(Exception $e){
					$this->log->error($e->getMessage());
				}
			}
			
		}
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function run(){
		#print __CLASS__.'->'.__FUNCTION__.''."\n";
		#print __CLASS__.'->'.__FUNCTION__.': client '.count($this->clients)."\n";
		
		if(!$this->socket){
			throw new RuntimeException('Socket not initialized. You need to execute listen().', 1);
		}
		
		$readHandles = array();
		$writeHandles = null;
		$exceptHandles = null;
		
		if($this->isListening){
			$readHandles[] = $this->socket->getHandle();
		}
		foreach($this->clients as $clientId => $client){
			// Collect client handles.
			$readHandles[] = $client->getSocket()->getHandle();
			
			// Run client.
			#print __CLASS__.'->'.__FUNCTION__.': client run'."\n";
			#$client->run();
		}
		$readHandlesNum = count($readHandles);
		
		$handlesChanged = $this->socket->select($readHandles, $writeHandles, $exceptHandles);
		#$this->log->debug('collect readable sockets: '.(int)$handlesChanged.'/'.$readHandlesNum);
		
		if($handlesChanged){
			foreach($readHandles as $readableHandle){
				if($this->isListening && $readableHandle == $this->socket->getHandle()){
					// Server
					$socket = $this->socket->accept();
					if($socket){
						$client = $this->clientNew($socket);
						$client->sendReady();
						
						#$this->log->debug('new client: '.$client->getId().', '.$client->getIpPort());
					}
				}
				else{
					// Client
					$client = $this->clientGetByHandle($readableHandle);
					if($client){
						if(feof($client->getSocket()->getHandle())){
							$this->clientRemove($client);
						}
						else{
							#$this->log->debug('old client: '.$client->getId().', '.$client->getIpPort());
							$client->dataRecv();
							
							if($client->getStatus('hasShutdown')){
								$this->clientRemove($client);
							}
						}
					}
					
					#$this->log->debug('old client: '.$client->getId().', '.$client->getIpPort());
				}
			}
		}
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function loop(){
		while(!$this->getExit()){
			$this->run();
			usleep(static::LOOP_USLEEP);
		}
		
		$this->shutdown();
	}
	
	/**
	 * @codeCoverageIgnore
	 */
	public function shutdown(){
		$this->log->debug('shutdown');
		
		// Notify all clients.
		foreach($this->clients as $clientId => $client){
			$client->sendQuit();
			$this->clientRemove($client);
		}
		
		$this->log->debug('shutdown done');
	}
	
	public function clientNew($socket){
		$this->clientsId++;
		#print __CLASS__.'->'.__FUNCTION__.' ID: '.$this->clientsId."\n";
		
		$client = new Client($this->getHostname());
		$client->setSocket($socket);
		$client->setId($this->clientsId);
		$client->setServer($this);
		
		$this->clients[$this->clientsId] = $client;
		#print __CLASS__.'->'.__FUNCTION__.' clients: '.count($this->clients)."\n";
		
		return $client;
	}
	
	public function clientGetByHandle($handle){
		$rv = null;
		
		foreach($this->clients as $clientId => $client){
			if($client->getSocket()->getHandle() == $handle){
				$rv = $client;
				break;
			}
		}
		
		return $rv;
	}
	
	public function clientRemove(Client $client){
		$this->log->debug('client remove: '.$client->getId());
		
		$client->shutdown();
		
		$clientsId = $client->getId();
		unset($this->clients[$clientsId]);
	}
	
	public function eventAdd(Event $event){
		#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.''."\n");
		
		$this->eventsId++;
		$this->events[$this->eventsId] = $event;
	}
	
	private function eventExecute($trigger, $args = array()){
		#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.''."\n");
		
		foreach($this->events as $eventId => $event){
			#fwrite(STDOUT, __CLASS__.'->'.__FUNCTION__.' event: '.$eventId."\n");
			if($event->getTrigger() == $trigger){
				$event->execute($args);
			}
		}
	}
	
	public function mailNew($from, $rcpt, $mail){
		#$this->log->debug('mailNew: /'.$from.'/ /'.join(', ', $rcpt).'/');
		#$this->log->debug('mail:');
		#$this->log->debug("\n".$mail);
		
		$this->eventExecute(Event::TRIGGER_MAIL_NEW, array($from, $rcpt, $mail));
	}
	
	/**
	 * Execute authentication events
	 * 
	 * All authentication events must return true for authentication to be successful
	 * 
	 */
	public function authenticateUser($method, $credentials = array()){
		$authenticated = false;
		$args = array($method, $credentials);
		
		foreach($this->events as $eventId => $event){
			if($event->getTrigger() == Event::TRIGGER_AUTH_ATTEMPT){
				if($event->execute($args)){
					$authenticated = true;
				}
				else{
					$authenticated = false;
				}
			}
		}
		
		return $authenticated;
	}
	
}
