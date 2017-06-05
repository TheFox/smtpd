<?php

namespace TheFox\Test;

use PHPUnit\Framework\TestCase;
use TheFox\Smtp\StringParser;

class StringParserTest extends TestCase
{
    public function testBasic1()
    {
        $str = '-ABC.';
        $this->assertEquals('-', $str[0]);
        $this->assertEquals('A', $str[1]);
        $this->assertEquals('.', $str[4]);
    }

    public function testBasic2()
    {
        $str = 'arg1 arg2 arg3 "arg4" ';
        $this->assertEquals(22, strlen($str));
        $this->assertEquals(21, strlen(trim($str)));
    }

    public function providerParse()
    {
        $rv = [];

        $expect = ['arg1', 'arg2', 'arg3', 'arg4'];
        $rv[] = ['arg1 arg2 arg3 arg4', $expect, null];

        $expect = ['arg1', 'arg2', 'arg3 arg4'];
        $rv[] = ['arg1 arg2 arg3 arg4', $expect, 3];
        $rv[] = ['arg1  arg2 arg3 arg4', $expect, 3];
        $rv[] = ['arg1 arg2  arg3 arg4', $expect, 3];
        $rv[] = ['arg1  arg2  arg3 arg4', $expect, 3];

        $expect = ['arg1', 'arg2', 'arg3  arg4'];
        $rv[] = ['arg1 arg2 arg3  arg4', $expect, 3];
        $rv[] = ['arg1  arg2  arg3  arg4', $expect, 3];

        $expect = ['arg1', 'arg2', 'arg3', 'arg4'];
        $rv[] = ['arg1 arg2 arg3 "arg4"', $expect, 4];
        $rv[] = ['arg1 arg2 "arg3" arg4', $expect, 4];
        $rv[] = ['arg1 arg2  "arg3" arg4', $expect, 4];
        $rv[] = ['arg1 arg2 "arg3"  arg4', $expect, 4];
        $rv[] = ['arg1 arg2  "arg3"  arg4', $expect, 4];
        $rv[] = ['arg1 arg2 arg3 "arg4"', $expect, 4];
        $rv[] = ['arg1 arg2 arg3 "arg4" ', $expect, 4];
        $rv[] = ['arg1 arg2 arg3  "arg4" ', $expect, 4];
        $rv[] = ['arg1 arg2 "arg3" "arg4"', $expect, 4];

        $expect = ['arg1', 'arg2', 'arg3  arg4', 'arg5'];
        $rv[] = ['arg1  arg2  "arg3  arg4" arg5', $expect, 5];

        $expect = ['arg1', 'arg2', 'arg3', 'arg4', 'arg5'];
        $rv[] = ['arg1 arg2 arg3 arg4 arg5', $expect, 10];

        $expect = ['arg1', 'arg2', 'arg3 arg4'];
        $rv[] = ['arg1 arg2 "arg3 arg4"', $expect, 3];
        $rv[] = ['arg1 arg2 "arg3 arg4" ', $expect, 3];
        $rv[] = ['arg1 arg2  "arg3 arg4" ', $expect, 3];
        $rv[] = ['arg1 arg2  "arg3 arg4"', $expect, 3];

        $expect = ['arg1', 'arg2', '0'];
        $rv[] = ['arg1 arg2 0', $expect, 3];

        $expect = ['arg1', 'arg2', 0];
        $rv[] = ['arg1 arg2 0', $expect, 3];

        $expect = ['arg1', 'arg2', '000'];
        $rv[] = ['arg1 arg2 000', $expect, 3];

        $expect = ['arg1', 'arg2', '123'];
        $rv[] = ['arg1 arg2 123', $expect, 3];

        $expect = ['arg1', 'arg2', '0123'];
        $rv[] = ['arg1 arg2 0123', $expect, 3];

        $expect = ['arg1', 'arg2', 'arg3 (arg4 "arg5 arg6") arg7'];
        $rv[] = ['arg1 arg2 arg3 (arg4 "arg5 arg6") arg7', $expect, 3];

        $expect = ['arg1', 'arg2', 'arg3 ("arg5 arg6" arg4) arg7'];
        $rv[] = ['arg1 arg2 arg3 ("arg5 arg6" arg4) arg7', $expect, 3];

        $expect = ['arg1', 'arg2', 'A"arg3"E'];
        $rv[] = ['arg1 arg2 A"arg3"E', $expect, 3];


        $expect = ['arg1', '', 'arg2'];
        $rv[] = ['arg1 "" arg2', $expect, 3];

        $expect = ['arg1', 'arg2', '', 'arg4'];
        $rv[] = ['arg1 arg2 "" arg4', $expect, null];

        $expect = ['arg1', 'arg2', '"" arg4'];
        $rv[] = ['arg1 arg2 "" arg4', $expect, 3];

        $expect = ['arg1', 'arg2', '"" "arg4"'];
        $rv[] = ['arg1 arg2 "" "arg4"', $expect, 3];

        $expect = ['', 'arg4'];
        $rv[] = ['"" arg4', $expect, null];

        $expect = ['"" arg4'];
        $rv[] = ['"" arg4', $expect, 1];

        $expect = ['arg1', 'arg2', 'arg3', 'arg4'];
        $rv[] = ['arg1 arg2 "arg3" arg4', $expect, null];

        $expect = ['arg1', 'arg2', '"arg3" arg4'];
        $rv[] = ['arg1 arg2 "arg3" arg4', $expect, 3];

        $expect = ['arg1', 'arg2', ' arg3', 'arg4'];
        $rv[] = ['arg1 arg2 " arg3" arg4', $expect, null];

        $expect = ['arg1', 'arg2', 'arg3 ', 'arg4'];
        $rv[] = ['arg1 arg2 "arg3 " arg4', $expect, null];

        $expect = ['arg1', 'arg2', ' arg3 ', 'arg4'];
        $rv[] = ['arg1 arg2 " arg3 " arg4', $expect, null];

        return $rv;
    }

    /**
     * @dataProvider providerParse
     * @group large
     * @param string $msgRaw
     * @param mixed $expect
     * @param null|int $argsMax
     */
    public function testParse1(string $msgRaw, $expect, $argsMax = null)
    {
        $str = new StringParser($msgRaw, $argsMax);
        $this->assertEquals($expect, $str->parse());
    }

    public function testParse2()
    {
        $str = new StringParser('arg1 arg2 arg3', 10);
        $str->parse();
        $this->assertTrue(true);
    }
}
