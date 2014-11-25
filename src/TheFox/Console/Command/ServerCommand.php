<?php

namespace TheFox\Console\Command;

use Exception;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use TheFox\Smtp\Server;

/**
 * @codeCoverageIgnore
 */
class ServerCommand extends BasicCommand{
	
	private $server;
	
	public function getPidfilePath(){
		return 'pid/server.pid';
	}
	
	protected function configure(){
		$this->setName('server');
		$this->setDescription('Run SMTP server.');
		$this->addOption('daemon', 'd', InputOption::VALUE_NONE,
			'Run in daemon mode.');
		$this->addOption('address', 'a', InputOption::VALUE_REQUIRED,
			'The address of the network interface. Default = 127.0.0.1');
		$this->addOption('port', 'p', InputOption::VALUE_REQUIRED,
			'The port of the network interface. Default = 20025');
		$this->addOption('shutdown', 's', InputOption::VALUE_NONE, 'Shutdown.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output){
		$this->executePre($input, $output);
		
		$address = '127.0.0.1';
		if($input->getOption('address')){
			$address = $input->getOption('address');
		}
		
		$port = 20025;
		if($input->getOption('port')){
			$port = (int)$input->getOption('port');
		}
		
		$this->log->info('server');
		$this->server = new Server($address, $port);
		
		try{
			$this->server->init();
		}
		catch(Exception $e){
			$this->log->error('init: '.$e->getMessage());
			exit(1);
		}
		
		try{
			$this->server->listen();
		}
		catch(Exception $e){
			$this->log->error('listen: '.$e->getMessage());
			exit(1);
		}
		
		try{
			$this->server->loop();
		}
		catch(Exception $e){
			$this->log->error('loop: '.$e->getMessage());
			exit(1);
		}
		
		$this->executePost();
		$this->log->info('exit');
	}
	
	public function signalHandler($signal){
		$this->exit++;
		
		switch($signal){
			case SIGTERM:
				$this->log->notice('signal: SIGTERM');
				break;
			case SIGINT:
				print "\n";
				$this->log->notice('signal: SIGINT');
				break;
			case SIGHUP:
				$this->log->notice('signal: SIGHUP');
				break;
			case SIGQUIT:
				$this->log->notice('signal: SIGQUIT');
				break;
			case SIGKILL:
				$this->log->notice('signal: SIGKILL');
				break;
			case SIGUSR1:
				$this->log->notice('signal: SIGUSR1');
				break;
			default:
				$this->log->notice('signal: N/A');
		}
		
		$this->log->notice('main abort ['.$this->exit.']');
		
		if($this->server){
			$this->server->setExit($this->exit);
		}
		if($this->exit >= 2){
			exit(1);
		}
	}
	
}
