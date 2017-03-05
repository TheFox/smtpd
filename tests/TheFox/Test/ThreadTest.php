<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use TheFox\Smtp\Thread;

class ThreadTest extends PHPUnit_Framework_TestCase{
	
	public function testThread(){
		$thread = new Thread();
		
		$thread->setExit();
		$this->assertEquals(1, $thread->getExit());
		
		$thread->setExit(2);
		$this->assertEquals(2, $thread->getExit());
	}
	
}
