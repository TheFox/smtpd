<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;
use TheFox\Smtp\Event;

class TestObj
{
    /**
     * @param Event $event
     * @param string $from
     * @param string $rcpt
     * @param mixed $mail
     * @return int
     */
    public function test1($event, $from, $rcpt, $mail)
    {
        #fwrite(STDOUT, 'my function: '.$event->getTrigger()."\n");
        return 43;
    }

    /**
     * @param Event $event
     * @param \Closure $method
     * @param array $credentials
     * @return bool
     */
    public function test2($event, $method, $credentials)
    {
        #fwrite(STDOUT, 'my function: '.$event->getTrigger()."\n");
        return true;
    }
}
