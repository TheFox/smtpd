<?php

namespace TheFox\Network;

abstract class AbstractSocket
{
    /**
     * @var null|resource
     */
    private $handle;

    /**
     * @param resource $handle
     */
    public function setHandle($handle)
    {
        $this->handle = $handle;
    }

    /**
     * @return null|resource
     */
    public function getHandle()
    {
        return $this->handle;
    }

    #abstract public function create();

    abstract public function bind($ip, $port);

    abstract public function listen();

    abstract public function connect($ip, $port);

    abstract public function accept();

    abstract public function select(&$readHandles, &$writeHandles, &$exceptHandles);

    abstract public function getPeerName(&$ip, &$port);

    abstract public function lastError();

    abstract public function strError();

    abstract public function clearError();

    abstract public function read();

    abstract public function write($data);

    abstract public function shutdown();

    abstract public function close();
}
