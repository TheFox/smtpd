<?php

/**
 * Pseudo Thread
 */

namespace TheFox\Smtp;

class Thread
{
    /**
     * @var int
     */
    private $exit = 0;

    /**
     * @param int $exit
     */
    public function setExit($exit = 1)
    {
        $this->exit = $exit;
    }

    /**
     * @return int
     */
    public function getExit()
    {
        return (int)$this->exit;
    }
}
