<?php

namespace TheFox\Network;

use RuntimeException;

class StreamSocket extends AbstractSocket
{
    /**
     * @var string
     */
    private $ip = '';

    /**
     * @var int
     */
    private $port = 0;

    /**
     * @param string $ip
     * @param int $port
     * @return bool
     */
    public function bind($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
        return true;
    }

    /**
     * @param array $contextOptions
     * @return bool
     */
    public function listen(array $contextOptions = [])
    {
        $local_socket = 'tcp://' . $this->ip . ':' . $this->port;
        $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
        $context = stream_context_create($contextOptions);
        $handle = @stream_socket_server($local_socket, $errno, $errstr, $flags, $context);

        if ($handle !== false) {
            $this->setHandle($handle);
            return true;
        } else {
            throw new RuntimeException($errstr, $errno);
        }
    }

    /**
     * @param string $ip
     * @param int $port
     * @return bool
     */
    public function connect($ip, $port)
    {
        $handle = @stream_socket_client('tcp://' . $ip . ':' . $port, $errno, $errstr, 2);
        if ($handle !== false) {
            $this->setHandle($handle);
            return true;
        } else {
            throw new RuntimeException($errstr, $errno);
        }
    }

    public function accept()
    {
        $handle = @stream_socket_accept($this->getHandle(), 2);
        if ($handle !== false) {
            $class = __CLASS__;
            $socket = new $class();
            $socket->setHandle($handle);
            return $socket;
        }
    }

    public function enableEncryption()
    {
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_SERVER;

        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_SERVER')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_SERVER;
        }

        stream_set_blocking($this->getHandle(), true);
        $result = @stream_socket_enable_crypto($this->getHandle(), true, $crypto_method);
        stream_set_blocking($this->getHandle(), false);

        if ($result === false) {
            throw new RuntimeException('TLS negotiation has failed');
        }

        return true;
    }

    /**
     * @param array $readHandles
     * @param array $writeHandles
     * @param array $exceptHandles
     * @return int
     */
    public function select(&$readHandles, &$writeHandles, &$exceptHandles)
    {
        return @stream_select($readHandles, $writeHandles, $exceptHandles, 0);
    }

    /**
     * @param string $ip
     * @param int $port
     */
    public function getPeerName(&$ip, &$port)
    {
        $ip = 'N/A';
        $port = -1;
        $name = stream_socket_get_name($this->getHandle(), true);
        $pos = strpos($name, ':');
        if ($pos === false) {
            $ip = $name;
        } else {
            $ip = substr($name, 0, $pos);
            $port = substr($name, $pos + 1);
        }
    }

    public function lastError()
    {
    }

    public function strError()
    {
    }

    public function clearError()
    {
    }

    public function read()
    {
        return fread($this->getHandle(), 2048);
    }

    /**
     * @param string $data
     * @return bool|int
     */
    public function write($data)
    {
        $rv = @fwrite($this->getHandle(), $data);
        return $rv;
    }

    public function shutdown()
    {
        stream_socket_shutdown($this->getHandle(), STREAM_SHUT_RDWR);
    }

    public function close()
    {
    }
}
