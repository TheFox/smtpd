<?php

use TheFox\Logger\Logger;
use TheFox\Smtp\Server;

class ServerTest extends PHPUnit_Framework_TestCase{
	
	public function testBasic(){
		$server = new Server('', 0);
		$this->assertTrue($server->getLog() === null);
		
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$this->assertTrue($server->getLog() !== null);
	}
	
}
