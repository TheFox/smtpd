<?php

/**
 * Main Server
 * Handles Sockets and Clients.
 */

namespace TheFox\Smtp;

use Exception;
use RuntimeException;
use TheFox\Logger\Logger;
use TheFox\Logger\StreamHandler;
use TheFox\Network\AbstractSocket;
use TheFox\Network\Socket;

class Server extends Thread
{
    const LOOP_USLEEP = 10000;

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var AbstractSocket
     */
    private $socket;

    /**
     * @var bool
     */
    private $isListening = false;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var int
     */
    private $port;

    /**
     * @var int
     */
    private $clientsId = 0;

    /**
     * @var array
     */
    private $clients = [];

    /**
     * @var int
     */
    private $eventsId = 0;

    /**
     * @var array
     */
    private $events = [];

    /**
     * @var string
     */
    private $hostname;

    /**
     * Server constructor.
     * @param string $ip
     * @param int $port
     * @param string $hostname
     */
    public function __construct($ip = '127.0.0.1', $port = 20025, $hostname = 'localhost.localdomain')
    {
        $this->setIp($ip);
        $this->setPort($port);
        $this->setHostname($hostname);
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * @param Logger $log
     */
    public function setLog(Logger $log)
    {
        $this->log = $log;
    }

    /**
     * @return Logger
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    public function init()
    {
        if (!$this->log) {
            $this->log = new Logger('server');
            $this->log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
            if (file_exists('log')) {
                $this->log->pushHandler(new StreamHandler('log/server.log', Logger::DEBUG));
            }
        }

        if (!defined('TEST')) {
            $this->log->info('start');
            $this->log->info('ip = "' . $this->ip . '"');
            $this->log->info('port = "' . $this->port . '"');
        }
    }

    /**
     * @param array $contextOptions
     * @return bool
     */
    public function listen(array $contextOptions)
    {
        if ($this->ip && $this->port) {
            return false;
        }

        $this->socket = new Socket();

        $bind = false;
        try {
            $bind = $this->socket->bind($this->ip, $this->port);
        } catch (Exception $e) {
            $this->log->error($e->getMessage());
        }

        if ($bind) {
            try {
                if ($this->socket->listen($contextOptions)) {
                    $this->log->notice('listen ok');
                    $this->isListening = true;

                    return true;
                }
            } catch (Exception $e) {
                $this->log->error($e->getMessage());
            }
        }

        return false;
    }

    /**
     * Main Function
     * Handles everything, keeps everything up-to-date.
     */
    public function run()
    {
        if (!$this->socket) {
            throw new RuntimeException('Socket not initialized. You need to execute listen().', 1);
        }

        $readHandles = [];
        $writeHandles = null;
        $exceptHandles = null;

        if ($this->isListening) {
            $readHandles[] = $this->socket->getHandle();
        }
        foreach ($this->clients as $clientId => $client) {
            // Collect client handles.
            $readHandles[] = $client->getSocket()->getHandle();
        }
        $readHandlesNum = count($readHandles);

        $handlesChanged = $this->socket->select($readHandles, $writeHandles, $exceptHandles);
        #$this->log->debug('collect readable sockets: '.(int)$handlesChanged.'/'.$readHandlesNum);

        if ($handlesChanged) {
            foreach ($readHandles as $readableHandle) {
                if ($this->isListening && $readableHandle == $this->socket->getHandle()) {
                    // Server
                    $socket = $this->socket->accept();
                    if ($socket) {
                        $client = $this->clientNew($socket);
                        $client->sendReady();

                        #$this->log->debug('new client: '.$client->getId().', '.$client->getIpPort());
                    }
                } else {
                    // Client
                    $client = $this->clientGetByHandle($readableHandle);
                    if ($client) {
                        if (feof($client->getSocket()->getHandle())) {
                            $this->clientRemove($client);
                        } else {
                            #$this->log->debug('old client: '.$client->getId().', '.$client->getIpPort());
                            $client->dataRecv();

                            if ($client->getStatus('hasShutdown')) {
                                $this->clientRemove($client);
                            }
                        }
                    }

                    #$this->log->debug('old client: '.$client->getId().', '.$client->getIpPort());
                }
            }
        }
    }

    /**
     * Main Loop
     */
    public function loop()
    {
        while (!$this->getExit()) {
            $this->run();
            usleep(static::LOOP_USLEEP);
        }

        $this->shutdown();
    }

    /**
     * Shutdown the server.
     * Should be executed before your application exits.
     */
    public function shutdown()
    {
        $this->log->debug('shutdown');

        // Notify all clients.
        foreach ($this->clients as $clientId => $client) {
            $client->sendQuit();
            $this->clientRemove($client);
        }

        $this->log->debug('shutdown done');
    }

    /**
     * Create a new Client for a new incoming socket connection.
     *
     * @return Client
     * @FIXME rename this function to newClient
     */
    public function clientNew($socket)
    {
        $this->clientsId++;

        $client = new Client($this->getHostname());
        $client->setSocket($socket);
        $client->setId($this->clientsId);
        $client->setServer($this);

        $this->clients[$this->clientsId] = $client;

        return $client;
    }

    /**
     * Find a Client by socket handle.
     *
     * @return Client|null
     * @FIXME rename this function to getClientByHandle
     */
    public function clientGetByHandle($handle)
    {
        foreach ($this->clients as $clientId => $client) {
            if ($client->getSocket()->getHandle() == $handle) {
                return $client;
            }
        }
    }

    /**
     * @param Client $client
     * @FIXME rename this function to removeClient
     */
    public function clientRemove(Client $client)
    {
        $this->log->debug('client remove: ' . $client->getId());

        $client->shutdown();

        $clientsId = $client->getId();
        unset($this->clients[$clientsId]);
    }

    /**
     * @param Event $event
     * @FIXME rename this function to addEvent
     */
    public function eventAdd(Event $event)
    {
        $this->eventsId++;
        $this->events[$this->eventsId] = $event;
    }

    /**
     * @param integer $trigger
     * @param array $args
     */
    private function eventExecute($trigger, $args = [])
    {
        foreach ($this->events as $eventId => $event) {
            if ($event->getTrigger() == $trigger) {
                $event->execute($args);
            }
        }
    }

    /**
     * @param string $from
     * @param array $rcpt
     * @param \Zend\Mail\Message $mail
     * @FIXME rename this function to newMail
     */
    public function mailNew($from, $rcpt, $mail)
    {
        $this->eventExecute(Event::TRIGGER_MAIL_NEW, [$from, $rcpt, $mail]);
    }

    /**
     * Execute authentication events.
     * All authentication events must return true for authentication to be successful
     *
     * @param string $method
     * @param array $credentials
     * @return boolean
     */
    public function authenticateUser($method, $credentials = [])
    {
        $authenticated = false;
        $args = [$method, $credentials];

        foreach ($this->events as $eventId => $event) {
            if ($event->getTrigger() == Event::TRIGGER_AUTH_ATTEMPT) {
                if (!$event->execute($args)) {
                    return false;
                }

                $authenticated = true;
            }
        }

        return $authenticated;
    }
}
