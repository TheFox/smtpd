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
use Zend\Mail\Message;

class Server extends Thread
{
    const LOOP_USLEEP = 10000;

    /**
     * @var Logger
     */
    private $logger;

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
     * @var Client[]
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
     * @param string $hostname
     */
    public function setHostname(string $hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return Logger|null
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip)
    {
        $this->ip = $ip;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port)
    {
        $this->port = $port;
    }

    public function init()
    {
        if (!$this->logger) {
            $this->logger = new Logger('server');
            $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
            if (file_exists('log')) {
                $this->logger->pushHandler(new StreamHandler('log/server.log', Logger::DEBUG));
            }
        }

        if (!defined('TEST')) {
            $this->logger->info('start');
            $this->logger->info('ip = "' . $this->ip . '"');
            $this->logger->info('port = "' . $this->port . '"');
        }
    }

    /**
     * @param array $contextOptions
     * @return bool
     */
    public function listen(array $contextOptions): bool
    {
        if (!$this->ip && !$this->port) {
            return false;
        }

        $this->socket = new Socket();

        $bind = false;
        try {
            $bind = $this->socket->bind($this->ip, $this->port);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        if ($bind) {
            try {
                if ($this->socket->listen($contextOptions)) {
                    $this->logger->notice('listen ok');
                    $this->isListening = true;

                    return true;
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
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
        $writeHandles = [];
        $exceptHandles = [];

        if ($this->isListening) {
            $readHandles[] = $this->socket->getHandle();
        }
        foreach ($this->clients as $clientId => $client) {
            // Collect client handles.
            $readHandles[] = $client->getSocket()->getHandle();
        }
        //$readHandlesNum = count($readHandles);

        $handlesChanged = $this->socket->select($readHandles, $writeHandles, $exceptHandles);
        #$this->log->debug('collect readable sockets: '.(int)$handlesChanged.'/'.$readHandlesNum);

        if ($handlesChanged) {
            foreach ($readHandles as $readableHandle) {
                if ($this->isListening && $readableHandle == $this->socket->getHandle()) {
                    // Server
                    $socket = $this->socket->accept();
                    if ($socket) {
                        $client = $this->newClient($socket);
                        $client->sendReady();

                        #$this->log->debug('new client: '.$client->getId().', '.$client->getIpPort());
                    }
                } else {
                    // Client
                    $client = $this->getClientByHandle($readableHandle);
                    if ($client) {
                        if (feof($client->getSocket()->getHandle())) {
                            $this->removeClient($client);
                        } else {
                            #$this->log->debug('old client: '.$client->getId().', '.$client->getIpPort());
                            $client->dataRecv();

                            if ($client->getStatus('hasShutdown')) {
                                $this->removeClient($client);
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
        $this->logger->debug('shutdown');

        // Notify all clients.
        foreach ($this->clients as $clientId => $client) {
            $client->sendQuit();
            $this->removeClient($client);
        }

        $this->logger->debug('shutdown done');
    }

    /**
     * Create a new Client for a new incoming socket connection.
     *
     * @return Client
     */
    public function newClient($socket): Client
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
     * @param resource $handle
     * @return Client|null
     */
    public function getClientByHandle($handle)
    {
        foreach ($this->clients as $clientId => $client) {
            $socket = $client->getSocket();
            if ($socket->getHandle() == $handle) {
                return $client;
            }
        }

        return null;
    }

    /**
     * @param Client $client
     */
    public function removeClient(Client $client)
    {
        $this->logger->debug('client remove: ' . $client->getId());

        $client->shutdown();

        $clientsId = $client->getId();
        unset($this->clients[$clientsId]);
    }

    /**
     * @param Event $event
     */
    public function addEvent(Event $event)
    {
        $this->eventsId++;
        $this->events[$this->eventsId] = $event;
    }

    /**
     * @param integer $trigger
     * @param array $args
     */
    private function eventExecute(int $trigger, array $args = [])
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
     */
    public function newMail(string $from, array $rcpt, Message $mail)
    {
        $this->eventExecute(Event::TRIGGER_NEW_MAIL, [$from, $rcpt, $mail]);
    }

    /**
     * Execute authentication events.
     * All authentication events must return true for authentication to be successful
     *
     * @param string $method
     * @param array $credentials
     * @return boolean
     */
    public function authenticateUser(string $method, array $credentials = []): bool
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
