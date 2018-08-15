<?php

/**
 * Main Server
 * Handles Sockets and Clients.
 */

namespace TheFox\Smtp;

use Exception;
use RuntimeException;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Zend\Mail\Message;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TheFox\Network\AbstractSocket;
use TheFox\Network\Socket;

class Server extends Thread
{
    use LoggerAwareTrait;
    
    const LOOP_USLEEP = 10000;

    /**
     * @var AbstractSocket
     */
    private $socket;

    /**
     * @var bool
     */
    private $isListening = false;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     * @deprecated
     */
    private $ip;

    /**
     * @var int
     * @deprecated
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
     * @var Event[]
     */
    private $events = [];

    /**
     * @var string
     * @deprecated
     */
    private $hostname;

    /**
     * Server constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'ip' => '127.0.0.1',
            'port' => 20025,
            'hostname' => 'localhost.localdomain',
            'logger' => new NullLogger(),
        ]);
        $this->options = $resolver->resolve($options);

        $this->logger = $this->options['logger'];

        $this->setIp($this->options['ip']);
        $this->setPort($this->options['port']);
        $this->setHostname($this->options['hostname']);

        $this->logger->info('start');
        $this->logger->info('ip = "' . $this->options['ip'] . '"');
        $this->logger->info('port = "' . $this->options['port'] . '"');
        $this->logger->info('hostname = "' . $this->options['hostname'] . '"');
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

        $handlesChanged = $this->socket->select($readHandles, $writeHandles, $exceptHandles);

        if ($handlesChanged) {
            foreach ($readHandles as $readableHandle) {
                if ($this->isListening && $readableHandle == $this->socket->getHandle()) {
                    // Server
                    $socket = $this->socket->accept();
                    if ($socket) {
                        $client = $this->newClient($socket);
                        $client->sendReady();
                        //$this->logger->debug('new client: '.$client->getId().', '.$client->getIpPort());
                    }
                } else {
                    // Client
                    $client = $this->getClientByHandle($readableHandle);
                    if ($client) {
                        if (feof($client->getSocket()->getHandle())) {
                            $this->removeClient($client);
                        } else {
                            //$this->logger->debug('old client: '.$client->getId().', '.$client->getIpPort());
                            $client->dataRecv();

                            if ($client->getStatus('hasShutdown')) {
                                $this->removeClient($client);
                            }
                        }
                    }
                    //$this->logger->debug('old client: '.$client->getId().', '.$client->getIpPort());
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

        $options = [
            'hostname' => $this->getHostname(),
            'logger' => $this->logger,
        ];
        $client = new Client($options);
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
     * @param string $rcpt
     * @return bool
     */
    public function newRcpt(string $rcpt)
    {
        foreach ($this->events as $eventId => $event) {
            if ($event->getTrigger() == Event::TRIGGER_NEW_RCPT && !$event->execute([$rcpt])) {
                return false;
            }
        }
        return true;
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
