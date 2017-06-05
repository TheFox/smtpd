<?php

namespace TheFox\Logger;

class StreamHandler
{
    private $path;
    private $level;

    public function __construct($path, $level)
    {
        $this->setPath($path);
        $this->setLevel($level);
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }
}
