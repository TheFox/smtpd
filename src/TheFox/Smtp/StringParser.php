<?php

/**
 * Parse a raw Client packet.
 */

namespace TheFox\Smtp;

class StringParser
{
    /**
     * @var string
     */
    private $str = '';

    private $len = 0;

    /**
     * @var int
     */
    private $argsMax;

    /**
     * @var int
     */
    private $argsId = -1;

    /**
     * @var array
     */
    private $args = [];

    /**
     * @var int
     */
    private $argsLen = 0;

    /**
     * StringParser constructor.
     * @param string $str
     * @param null|int $argsMax
     */
    public function __construct(string $str, $argsMax = null)
    {
        $this->str = $str;
        $this->str = trim($this->str);
        $this->len = strlen($this->str);
        $this->argsMax = $argsMax;
    }

    private function reset()
    {
        $this->argsId = -1;
        $this->args = [];
        $this->argsLen = 0;
    }

    private function fixPrev()
    {
        if ($this->argsId >= 0) {
            if ($this->args[$this->argsId]
                && $this->args[$this->argsId][0] == '"'
                && substr($this->args[$this->argsId], -1) == '"'
            ) {
                $tmp = substr(substr($this->args[$this->argsId], 1), 0, -1);
                if (strpos($tmp, '"') === false) {
                    $this->args[$this->argsId] = $tmp;
                    $this->argsLen = count($this->args);
                }
            }
        }
    }

    /**
     * @param string $char
     */
    private function charNew(string $char = '')
    {
        if ($this->argsMax === null || $this->argsLen < $this->argsMax) {
            $this->fixPrev();
            $this->argsId++;
            $this->args[$this->argsId] = $char;
            $this->argsLen = count($this->args);
        }
    }

    /**
     * @param string $char
     */
    private function charAppend(string $char)
    {
        if ($this->argsId != -1) {
            $this->args[$this->argsId] .= $char;
        }
    }

    /**
     * @return array
     */
    public function parse(): array
    {
        $this->reset();

        $str = $this->str;
        $in = false;
        //$prevChar = ' ';
        $endChar = '';

        for ($pos = 0; $pos < $this->len; $pos++) {
            $char = $str[$pos];
            $nextChar = ($pos < $this->len - 1) ? $str[$pos + 1] : '';

            if ($in) {
                if ($char == $endChar) {
                    if ($pos == $this->len - 1 || $this->argsMax === null || $this->argsLen < $this->argsMax) {
                        if ($char == '"') {
                            $this->charAppend($char);
                        }
                        $in = false;
                    } else {
                        $this->charAppend($char);
                    }
                } else {
                    $this->charAppend($char);
                }
            } else {
                if ($this->argsMax === null || $this->argsLen < $this->argsMax) {
                    if ($char == '"') {
                        $this->charNew($char);
                        $endChar = '"';
                        $in = true;
                    } elseif ($char == ' ') {
                        if ($nextChar != ' ' && $nextChar != '"') {
                            $this->charNew();
                            $endChar = ' ';
                            $in = true;
                        }
                    } else {
                        $this->charNew($char);
                        $endChar = ' ';
                        $in = true;
                    }
                }
                /*else{
                    fwrite(STDOUT, ' -> char append'."\n");
                    $this->charAppend($char);
                }*/
            }

            //$prevChar = $char;
        }

        $this->fixPrev();

        return $this->args;
    }
}
