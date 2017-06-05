<?php

namespace TheFox\Logger;

class StreamHandler
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $level;

    /**
     * StreamHandler constructor.
     * @param string $path
     * @param int $level
     */
    public function __construct($path, $level)
    {
        $this->setPath($path);
        $this->setLevel($level);
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }
}
