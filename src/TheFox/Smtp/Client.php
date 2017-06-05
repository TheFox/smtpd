<?php

namespace TheFox\Smtp;

use RuntimeException;
use PHPUnit_Framework_MockObject_MockObject;
use Zend\Mail\Message;
use TheFox\Logger\Logger;
use TheFox\Network\StreamSocket;

class Client
{
    const MSG_SEPARATOR = "\r\n";

    /**
     * @var int
     */
    private $id = 0;

    /**
     * @var array
     */
    private $status = [];

    /**
     * @var Server
     */
    private $server;

    /**
     * @var StreamSocket
     */
    private $socket;

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
     * @param string $hostname
     */
    public function __construct(string $hostname = 'localhost.localdomain')
    {
        $this->hostname = $hostname;
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
     * @param StreamSocket|PHPUnit_Framework_MockObject_MockObject $socket
     */
    public function setSocket(StreamSocket $socket)
    {
        $this->socket = $socket;
    }

    /**
     * @return StreamSocket|null
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
     * @return null|Logger
     */
    public function getLog()
    {
        if ($this->getServer()) {
            return $this->getServer()->getLog();
        }

        return null;
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
        return $this->hostname;
    }

    /**
     * @param string $level
     * @param string $msg
     */
    private function log(string $level, string $msg)
    {
        if ($this->getLog()) {
            if (method_exists($this->getLog(), $level)) {
                $this->getLog()->$level($msg);
            }
        }
    }

    public function dataRecv()
    {
        $data = $this->getSocket()->read();

        do {
            $separatorPos = strpos($data, static::MSG_SEPARATOR);
            if ($separatorPos === false) {
                $this->recvBufferTmp .= $data;

                $this->log('debug', 'client ' . $this->id . ': collect data');

                break;
            } else {
                $msg = $this->recvBufferTmp . substr($data, 0, $separatorPos);
                $this->recvBufferTmp = '';

                $this->msgHandle($msg);

                $data = substr($data, $separatorPos + strlen(static::MSG_SEPARATOR));
            }
        } while ($data);
    }

    /**
     * @param string $msgRaw
     * @return string
     */
    public function msgHandle(string $msgRaw): string
    {
        #$this->log('debug', 'client '.$this->id.' raw: /'.$msgRaw.'/');

        $rv = '';

        $str = new StringParser($msgRaw);
        $args = $str->parse();

        $command = array_shift($args);
        $commandcmp = strtolower($command);


        if ($commandcmp == 'helo') {
            #$this->log('debug', 'client '.$this->id.' helo');
            $this->setStatus('hasHello', true);

            return $this->sendOk($this->getHostname());
        } elseif ($commandcmp == 'ehlo') {
            #$this->log('debug', 'client '.$this->id.' helo');
            $this->setStatus('hasHello', true);
            $msg = '250-' . $this->getHostname() . static::MSG_SEPARATOR;
            $count = count($this->extendedCommands) - 1;

            for ($i = 0; $i < $count; $i++) {
                $msg .= '250-' . $this->extendedCommands[$i] . static::MSG_SEPARATOR;
            }

            $msg .= '250 ' . end($this->extendedCommands);

            return $this->dataSend($msg);
        } elseif ($commandcmp == 'mail') {
            #$this->log('debug', 'client '.$this->id.' mail');

            if ($this->getStatus('hasHello')) {
                if (isset($args[0]) && $args[0]) {
                    $this->setStatus('hasMail', true);
                    $from = $args[0];
                    if (substr(strtolower($from), 0, 6) == 'from:<') {
                        $from = substr(substr($from, 6), 0, -1);
                    }
                    #$this->log('debug', 'client '.$this->id.' from: /'.$from.'/');
                    $this->from = $from;
                    $this->mail = '';
                    return $this->sendOk();
                } else {
                    return $this->sendSyntaxErrorInParameters();
                }
            } else {
                return $this->sendSyntaxErrorCommandUnrecognized();
            }
        } elseif ($commandcmp == 'rcpt') {
            #$this->log('debug', 'client '.$this->id.' rcpt');

            if ($this->getStatus('hasHello')) {
                if (isset($args[0]) && $args[0]) {
                    $this->setStatus('hasMail', true);
                    $rcpt = $args[0];
                    if (substr(strtolower($rcpt), 0, 4) == 'to:<') {
                        $rcpt = substr(substr($rcpt, 4), 0, -1);
                        $this->rcpt[] = $rcpt;
                    }
                    #$this->log('debug', 'client '.$this->id.' rcpt: /'.$rcpt.'/');
                    return $this->sendOk();
                } else {
                    return $this->sendSyntaxErrorInParameters();
                }
            } else {
                return $this->sendSyntaxErrorCommandUnrecognized();
            }
        } elseif ($commandcmp == 'data') {
            #$this->log('debug', 'client '.$this->id.' data');

            if ($this->getStatus('hasHello')) {
                $this->setStatus('hasData', true);
                return $this->sendDataResponse();
            } else {
                return $this->sendSyntaxErrorCommandUnrecognized();
            }
        } elseif ($commandcmp == 'noop') {
            return $this->sendOk();
        } elseif ($commandcmp == 'quit') {
            $rv .= $this->sendQuit();
            $this->shutdown();
        } elseif ($commandcmp == 'auth') {
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
        } elseif ($commandcmp == 'starttls') {
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
        } elseif ($commandcmp == 'help') {
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

                    $this->getServer()->mailNew($this->from, $this->rcpt, $zmail);
                    $this->from = '';
                    $this->rcpt = [];
                    $this->mail = '';

                    return $this->sendOk();
                } else {
                    $this->mail .= $msgRaw . static::MSG_SEPARATOR;
                }
            } else {
                $this->log('debug', 'client ' . $this->id . ' not implemented: /' . $command . '/ - /' . join('/ /', $args) . '/');
                return $this->sendSyntaxErrorCommandUnrecognized();
            }
        }

        return $rv;
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
            $this->log('debug', 'client ' . $this->id . ' data send: "' . $tmp . '"');
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
