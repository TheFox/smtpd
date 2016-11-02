<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;

class BasicTest extends PHPUnit_Framework_TestCase{
	
	public function testTest(){
		$this->assertTrue(defined('TEST'));
		$this->assertFalse(defined('NO_TEST'));
	}
	
}
