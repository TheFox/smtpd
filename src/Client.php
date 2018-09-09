<?php

namespace TheFox\Smtp;

use RuntimeException;
use PHPUnit_Framework_MockObject_MockObject;
use TheFox\Network\AbstractSocket;
use Zend\Mail\Message;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;

class Client
{
    use LoggerAwareTrait;

    const MSG_SEPARATOR = "\r\n";

    /**
     * @var int
     */
    private $id = 0;

    /**
     * @var array
     */
    private $status;

    /**
     * @var Server
     */
    private $server;

    /**
     * @var AbstractSocket
     */
    private $socket;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $ip = '';

    /**
     * @var int
     */
    private $port = 0;

    /**
     * @var string
     */
    private $recvBufferTmp = '';

    /**
     * @var string
     */
    private $from = '';

    /**
     * @var array
     */
    private $rcpt = [];

    /**
     * @var string
     */
    private $mail = '';

    /**
     * @var string
     * @deprecated
     */
    private $hostname = '';

    /**
     * @var array
     */
    private $credentials = [];

    /**
     * @var array
     */
    private $extendedCommands = [
        'AUTH PLAIN LOGIN',
        'STARTTLS',
        'HELP',
    ];

    /**
     * Client constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'hostname' => 'localhost.localdomain',
            'logger' => new NullLogger(),
        ]);
        $this->options = $resolver->resolve($options);

        $this->logger = $this->options['logger'];
        $this->hostname = $this->options['hostname'];

        $this->status = [];
        $this->status['hasHello'] = false;
        $this->status['hasMail'] = false;
        $this->status['hasShutdown'] = false;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getStatus(string $name)
    {
        if (array_key_exists($name, $this->status)) {
            return $this->status[$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setStatus(string $name, $value)
    {
        $this->status[$name] = $value;
    }

    /**
     * @param Server $server
     */
    public function setServer(Server $server)
    {
        $this->server = $server;
    }

    /**
     * @return Server|null
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param AbstractSocket|PHPUnit_Framework_MockObject_MockObject $socket
     */
    public function setSocket(AbstractSocket $socket)
    {
        $this->socket = $socket;
    }

    /**
     * @return AbstractSocket|null
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        if (!$this->ip) {
            $this->setIpPort();
        }
        return $this->ip;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port)
    {
        $this->port = $port;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        if (!$this->port) {
            $this->setIpPort();
        }
        return $this->port;
    }

    /**
     * @param string $ip
     * @param int $port
     */
    public function setIpPort(string $ip = '', int $port = 0)
    {
        // @codeCoverageIgnoreStart
        if (!defined('TEST')) {
            $this->getSocket()->getPeerName($ip, $port);
        }
        // @codeCoverageIgnoreEnd

        $this->setIp($ip);
        $this->setPort($port);
    }

    /**
     * @return string
     */
    public function getIpPort(): string
    {
        return $this->getIp() . ':' . $this->getPort();
    }

    /**
     * @param array $credentials
     */
    public function setCredentials(array $credentials = [])
    {
        $this->credentials = $credentials;
    }

    /**
     * @return array
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * @return string
     */
    public function getHostname(): string
    {
        return $this->options['hostname'];
    }

    public function dataRecv()
    {
        $data = $this->getSocket()->read();

        do {
            $separatorPos = strpos($data, static::MSG_SEPARATOR);
            $separatorLen = strlen(static::MSG_SEPARATOR);

            // handle separators broken over multiple recvs
            if (($separatorPos === false) && $this->recvBufferTmp) {
                $tmp = substr($this->recvBufferTmp, -$separatorLen) . substr($data, 0, $separatorLen);
                $separatorPos = strpos($tmp, static::MSG_SEPARATOR) - $separatorLen;
            }
            if ($separatorPos === false) {
                $this->recvBufferTmp .= $data;

                $this->logger->debug('client ' . $this->id . ': collect data');
                break;
            } else {
                $msg = substr(
                    $this->recvBufferTmp . $data,
                    0,
                    strlen($this->recvBufferTmp) + $separatorPos
                );
                $this->recvBufferTmp = '';

                $this->handleMessage($msg);

                $data = substr($data, $separatorPos + $separatorLen);
            }
        } while ($data);
    }

    /**
     * @param string $msgRaw
     * @return string
     */
    public function handleMessage(string $msgRaw): string
    {
        $str = new StringParser($msgRaw);
        $args = $str->parse();

        $command = array_shift($args);
        $commandCmp = strtolower($command);

        if ($commandCmp == 'helo') {
            $this->setStatus('hasHello', true);

            return $this->sendOk($this->getHostname());
        } elseif ($commandCmp == 'ehlo') {
            $this->setStatus('hasHello', true);
            $response = '250-' . $this->getHostname() . static::MSG_SEPARATOR;
            $count = count($this->extendedCommands) - 1;

            for ($i = 0; $i < $count; $i++) {
                $response .= '250-' . $this->extendedCommands[$i] . static::MSG_SEPARATOR;
            }

            $response .= '250 ' . end($this->extendedCommands);

            return $this->dataSend($response);
        } elseif ($commandCmp == 'mail') {
            if ($this->getStatus('hasHello')) {
                if (isset($args[0]) && $args[0]) {
                    $this->setStatus('hasMail', true);
                    $from = $args[0];
                    if (substr(strtolower($from), 0, 6) == 'from:<') {
                        $from = substr(substr($from, 6), 0, -1);
                    }
                    $this->from = $from;
                    $this->mail = '';
                    return $this->sendOk();
                } else {
                    return $this->sendSyntaxErrorInParameters();
                }
            } else {
                return $this->sendSyntaxErrorCommandUnrecognized();
            }
        } elseif ($commandCmp == 'rcpt') {
            if ($this->getStatus('hasHello')) {
                if (isset($args[0]) && $args[0]) {
                    $this->setStatus('hasMail', true);
                    $rcpt = $args[0];
                    if (substr(strtolower($rcpt), 0, 4) == 'to:<') {
                        $rcpt = substr(substr($rcpt, 4), 0, -1);

                        $server = $this->getServer();
                        if (!$server->newRcpt($rcpt)) {
                            return $this->sendUserUnknown();
                        }
                        $this->rcpt[] = $rcpt;
                    }
                    return $this->sendOk();
                } else {
                    return $this->sendSyntaxErrorInParameters();
                }
            } else {
                return $this->sendSyntaxErrorCommandUnrecognized();
            }
        } elseif ($commandCmp == 'data') {
            if ($this->getStatus('hasHello')) {
                $this->setStatus('hasData', true);
                return $this->sendDataResponse();
            } else {
                return $this->sendSyntaxErrorCommandUnrecognized();
            }
        } elseif ($commandCmp == 'noop') {
            return $this->sendOk();
        } elseif ($commandCmp == 'quit') {
            $response = $this->sendQuit();
            $this->shutdown();
            return $response;
        } elseif ($commandCmp == 'auth') {
            $this->setStatus('hasAuth', true);

            if (empty($args)) {
                return $this->sendSyntaxErrorInParameters();
            }

            $authentication = strtolower($args[0]);

            if ($authentication == 'plain') {
                $this->setStatus('hasAuthPlain', true);

                if (isset($args[1])) {
                    $this->setStatus('hasAuthPlainUser', true);
                    $this->setCredentials([$args[1]]);

                    if ($this->authenticate('plain')) {
                        return $this->sendAuthSuccessResponse();
                    }

                    return $this->sendAuthInvalid();
                }

                return $this->sendAuthPlainResponse();
            } elseif ($authentication == 'login') {
                $this->setStatus('hasAuthLogin', true);

                return $this->sendAskForUserResponse();
            } elseif ($authentication == 'cram-md5') {
                return $this->sendCommandNotImplemented();
            } else {
                return $this->sendSyntaxErrorInParameters();
            }
        } elseif ($commandCmp == 'starttls') {
            if (!empty($args)) {
                return $this->sendSyntaxErrorInParameters();
            }

            $this->sendReadyStartTls();

            try {
                $socket = $this->getSocket();
                $socket->enableEncryption();
            } catch (RuntimeException $e) {
                return $this->sendTemporaryErrorStartTls();
            }
        } elseif ($commandCmp == 'help') {
            return $this->sendOk('HELO, EHLO, MAIL FROM, RCPT TO, DATA, NOOP, QUIT');
        } else {
            if ($this->getStatus('hasAuth')) {
                if ($this->getStatus('hasAuthPlain')) {
                    $this->setStatus('hasAuthPlainUser', true);
                    $this->setCredentials([$command]);

                    if ($this->authenticate('plain')) {
                        return $this->sendAuthSuccessResponse();
                    }

                    return $this->sendAuthInvalid();
                } elseif ($this->getStatus('hasAuthLogin')) {
                    $credentials = $this->getCredentials();

                    if ($this->getStatus('hasAuthLoginUser')) {
                        $credentials['password'] = $command;
                        $this->setCredentials($credentials);

                        if ($this->authenticate('login')) {
                            return $this->sendAuthSuccessResponse();
                        }

                        return $this->sendAuthInvalid();
                    }

                    $this->setStatus('hasAuthLoginUser', true);
                    $credentials['user'] = $command;
                    $this->setCredentials($credentials);

                    return $this->sendAskForPasswordResponse();
                }
            } elseif ($this->getStatus('hasData')) {
                if ($msgRaw == '.') {
                    $this->mail = substr($this->mail, 0, -strlen(static::MSG_SEPARATOR));

                    $zmail = Message::fromString($this->mail);

                    $server = $this->getServer();
                    $server->newMail($this->from, $this->rcpt, $zmail);

                    $this->from = '';
                    $this->rcpt = [];
                    $this->mail = '';

                    return $this->sendOk();
                } else {
                    $this->mail .= $msgRaw . static::MSG_SEPARATOR;
                }
            } else {
                $tmp = [$this->id, $command, join('/ /', $args)];
                $this->logger->debug(vsprintf('client %d not implemented: /%s/ - /%s/', $tmp));
                return $this->sendSyntaxErrorCommandUnrecognized();
            }
        }

        return '';
    }

    /**
     * @param string $msg
     * @return string
     */
    private function dataSend(string $msg): string
    {
        $output = $msg . static::MSG_SEPARATOR;
        if ($this->getSocket()) {
            $tmp = $msg;
            $tmp = str_replace("\r", '', $tmp);
            $tmp = str_replace("\n", '\\n', $tmp);
            $this->logger->debug('client ' . $this->id . ' data send: "' . $tmp . '"');
            $this->getSocket()->write($output);
        }
        return $output;
    }

    /**
     * @param string $method
     * @return boolean
     */
    public function authenticate(string $method): bool
    {
        $attempt = $this->getServer()->authenticateUser($method, $this->getCredentials());

        $this->setStatus('hasAuth', false);
        $this->setStatus('hasAuth' . ucfirst($method), false);
        $this->setStatus('hasAuth' . ucfirst($method) . 'User', false);

        if (!$attempt) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function sendReady(): string
    {
        return $this->dataSend('220 ' . $this->getHostname() . ' SMTP Service Ready');
    }

    /**
     * @return string
     */
    private function sendReadyStartTls(): string
    {
        return $this->dataSend('220 Ready to start TLS');
    }

    /**
     * @return string
     */
    public function sendQuit(): string
    {
        return $this->dataSend('221 ' . $this->getHostname() . ' Service closing transmission channel');
    }

    /**
     * @param string $text
     * @return string
     */
    private function sendOk(string $text = 'OK'): string
    {
        return $this->dataSend('250 ' . $text);
    }

    /**
     * @return string
     */
    private function sendDataResponse(): string
    {
        return $this->dataSend('354 Start mail input; end with <CRLF>.<CRLF>');
    }

    /**
     * @return string
     */
    private function sendAuthPlainResponse(): string
    {
        return $this->dataSend('334 ');
    }

    /**
     * @return string
     */
    private function sendAuthSuccessResponse(): string
    {
        return $this->dataSend('235 2.7.0 Authentication successful');
    }

    /**
     * @return string
     */
    private function sendAskForUserResponse(): string
    {
        return $this->dataSend('334 VXNlcm5hbWU6');
    }

    /**
     * @return string
     */
    private function sendAskForPasswordResponse(): string
    {
        return $this->dataSend('334 UGFzc3dvcmQ6');
    }

    /**
     * @return string
     */
    private function sendTemporaryErrorStartTls(): string
    {
        return $this->dataSend('454 TLS not available due to temporary reason');
    }

    /**
     * @return string
     */
    private function sendSyntaxErrorCommandUnrecognized(): string
    {
        return $this->dataSend('500 Syntax error, command unrecognized');
    }

    /**
     * @return string
     */
    private function sendSyntaxErrorInParameters(): string
    {
        return $this->dataSend('501 Syntax error in parameters or arguments');
    }

    /**
     * @return string
     */
    private function sendCommandNotImplemented(): string
    {
        return $this->dataSend('502 Command not implemented');
    }

    /**
     * @return string
     */
    private function sendAuthInvalid(): string
    {
        return $this->dataSend('535 Authentication credentials invalid');
    }

    /**
     * @return string
     */
    private function sendUserUnknown(): string
    {
        return $this->dataSend('550 User unknown');
    }

    public function shutdown()
    {
        if (!$this->getStatus('hasShutdown')) {
            $this->setStatus('hasShutdown', true);

            if ($this->getSocket()) {
                $this->getSocket()->shutdown();
                $this->getSocket()->close();
            }
        }
    }
}
