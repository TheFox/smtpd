<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;

class TestObj
{
    public function test1($event, $from, $rcpt, $mail)
    {
        #fwrite(STDOUT, 'my function: '.$event->getTrigger()."\n");
        return 43;
    }

    public function test2($event, $method, $credentials)
    {
        #fwrite(STDOUT, 'my function: '.$event->getTrigger()."\n");
        return true;
    }
}
