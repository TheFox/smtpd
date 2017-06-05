<?php

namespace TheFox\Test;

use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    public function testTest()
    {
        $this->assertTrue(defined('TEST'));
        $this->assertFalse(defined('NO_TEST'));
    }
}
