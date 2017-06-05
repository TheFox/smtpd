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

    abstract public function bind(string $ip, int $port): bool;

    abstract public function listen(): bool;

    abstract public function connect(string $ip, int $port): bool;

    abstract public function accept();

    abstract public function select(array &$readHandles, array &$writeHandles, array &$exceptHandles);

    abstract public function getPeerName(string &$ip, int &$port);

    abstract public function lastError();

    abstract public function strError();

    abstract public function clearError();

    abstract public function read(): string;

    abstract public function write(string $data): int;

    abstract public function shutdown();

    abstract public function close();
}
