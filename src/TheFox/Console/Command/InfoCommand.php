<?php

namespace TheFox\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use TheFox\Smtp\Smtpd;

/**
 * @codeCoverageIgnore
 */
class InfoCommand extends BasicCommand{
	
	public function getLogfilePath(){
		return 'log/info.log';
	}
	
	public function getPidfilePath(){
		return 'pid/info.pid';
	}
	
	protected function configure(){
		$this->setName('info');
		$this->setDescription('Show infos.');
		$this->addOption('name', null, InputOption::VALUE_NONE, 'Prints the name of this application.');
		$this->addOption('name_lc', null, InputOption::VALUE_NONE, 'Prints the lower-case name of this application.');
		$this->addOption('version_number', null, InputOption::VALUE_NONE, 'Prints the version of this application.');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output){
		#$this->executePre($input, $output);
		
		if($input->hasOption('name') && $input->getOption('name')){
			print Smtpd::NAME;
		}
		elseif($input->hasOption('name_lc') && $input->getOption('name_lc')){
			print strtolower(Smtpd::NAME);
		}
		elseif($input->hasOption('version_number') && $input->getOption('version_number')){
			print Smtpd::VERSION;
		}
		
		#$this->executePost();
	}
	
	public function signalHandler($signal){
		$this->exit++;
		
		switch($signal){
			case SIGTERM:
				$this->log->notice('signal: SIGTERM');
				break;
			case SIGINT:
				print PHP_EOL;
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
	
}
