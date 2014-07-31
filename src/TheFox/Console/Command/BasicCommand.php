<?php

namespace TheFox\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Liip\ProcessManager\ProcessManager;
use Liip\ProcessManager\PidFile;

use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;

class BasicCommand extends Command{
	
	public $log;
	public $exit = 0;
	private $pidFile;
	private $settings;
	
	public function setSettings($settings){
		$this->settings = $settings;
	}
	
	public function getSettings(){
		return $this->settings;
	}
	
	public function executePre(InputInterface $input){
		$this->log = new Logger('application');
		$this->log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
		$this->log->pushHandler(new StreamHandler('log/application.log', Logger::DEBUG));
		
		if($input->hasOption('shutdown') && $input->getOption('shutdown')){
			if(file_exists($this->getPidfilePath())){
				$pid = file_get_contents($this->getPidfilePath());
				$this->log->info('kill '.$pid);
				posix_kill($pid, SIGTERM);
			}
			exit();
		}
		elseif($input->hasOption('daemon') && $input->getOption('daemon')){
			#$this->log->info('daemon');
			
			if(function_exists('pcntl_fork')){
				#$this->log->info('fork');
				$pid = pcntl_fork();
				if($pid < 0 || $pid){
					#$this->log->info('fork exit: '.$pid);
					exit();
				}
				#$this->log->info('fork ok: '.$pid);
				
				#$this->log->info('setsid');
				$sid = posix_setsid();
				#$this->log->info('sid: '.$sid);
				
				#$this->log->info('ignore signals');
				$this->signalHandlerSetup();
				
				$pid = pcntl_fork();
				if($pid < 0 || $pid){
					#$this->log->info('fork again exit: '.$pid);
					exit();
				}
				#$this->log->info('fork again ok: '.$pid);
				
				umask(0);
				
				$this->stdStreamsSetup();
			}
		}
		else{
			$this->signalHandlerSetup();
		}
		
		$this->pidFile = new PidFile(new ProcessManager(), $this->getPidfilePath());
		$this->pidFile->acquireLock();
		$this->pidFile->setPid(getmypid());
	}
	
	public function executePost(){
		$this->pidFile->releaseLock();
		#$this->log->info('exit');
	}
	
	public function signalHandlerSetup(){
		if(function_exists('pcntl_signal')){
			#$this->log->info('pcntl_signal setup');
			
			declare(ticks = 1);
			pcntl_signal(SIGTERM, array($this, 'signalHandler'));
			pcntl_signal(SIGINT, array($this, 'signalHandler'));
			pcntl_signal(SIGHUP, array($this, 'signalHandler'));
			
			#$this->log->info('pcntl_signal ok');
		}
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
		
		if($this->exit >= 2){
			exit(1);
		}
	}
	
	private function stdStreamsSetup(){
		global $STDIN, $STDOUT, $STDERR;
		
		#$this->log->info('stdStreamsSetup');
		
		fclose(STDIN);
		fclose(STDOUT);
		fclose(STDERR);
		$STDIN = fopen('/dev/null', 'r');
		$STDOUT = fopen('/dev/null', 'wb');
		$STDERR = fopen('/dev/null', 'wb');
		
		#$this->log->info('stdStreamsSetup done');
	}
	
}
