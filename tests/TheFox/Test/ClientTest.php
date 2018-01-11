<?php

namespace TheFox\Test;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_MockObject_MockBuilder;
use TheFox\Network\StreamSocket;
use TheFox\Smtp\Event;
use TheFox\Smtp\Server;
use TheFox\Smtp\Client;

class ClientTest extends TestCase
{
    public function testSetId()
    {
        $client = new Client();
        $client->setId(1);

        $this->assertEquals(1, $client->getId());
    }

    public function testSetIp()
    {
        $client = new Client();
        $client->setIp('192.168.241.21');
        $this->assertEquals('192.168.241.21', $client->getIp());
    }

    public function testGetIp()
    {
        $client = new Client();
        $this->assertEquals('', $client->getIp());
    }

    public function testSetPort()
    {
        $client = new Client();
        $client->setPort(1024);
        $this->assertEquals(1024, $client->getPort());
    }

    public function testGetPort()
    {
        $client = new Client();
        $this->assertEquals(0, $client->getPort());
    }

    public function testGetIpPort1()
    {
        $client = new Client();
        $client->setIp('192.168.241.21');
        $client->setPort(1024);
        $this->assertEquals('192.168.241.21:1024', $client->getIpPort());
    }

    public function testGetIpPort2()
    {
        $client = new Client();
        $client->setIpPort('192.168.241.21', 1024);
        $this->assertEquals('192.168.241.21:1024', $client->getIpPort());
    }

    public function testGetCredentials()
    {
        $client = new Client();
        $client->setCredentials(['user' => 'testuser', 'password' => 'super_secret_password']);
        $credentials = $client->getCredentials();
        $this->assertEquals('testuser', $credentials['user']);
        $this->assertEquals('super_secret_password', $credentials['password']);
    }

    public function testGetHostname()
    {
        $client = new Client();
        $host = $client->getHostname();
        $this->assertEquals('localhost.localdomain', $host);
    }

    public function testMsgHandleHello()
    {
        $server = new Server();

        $client = new Client();
        $client->setServer($server);
        $client->setId(1);


        $msg = $client->handleMessage('HELO localhost.localdomain');
        $this->assertEquals('250 localhost.localdomain' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('EHLO localhost.localdomain');
        $expect = '250-localhost.localdomain' . Client::MSG_SEPARATOR;
        $expect .= '250-AUTH PLAIN LOGIN' . Client::MSG_SEPARATOR;
        $expect .= '250-STARTTLS' . Client::MSG_SEPARATOR;
        $expect .= '250 HELP' . Client::MSG_SEPARATOR;
        $this->assertEquals($expect, $msg);

        $msg = $client->handleMessage('XYZ abc');
        $this->assertEquals('500 Syntax error, command unrecognized' . Client::MSG_SEPARATOR, $msg);
    }

    public function testMsgHandleMail()
    {
        $server = new Server();

        $event1 = new Event(Event::TRIGGER_NEW_RCPT, null, function ($event, $rcpt) {
            return $rcpt !== 'invalid@example.com';
        });
        $server->addEvent($event1);

        $client = new Client();
        $client->setServer($server);
        $client->setId(1);


        $msg = $client->handleMessage('MAIL FROM:<Smith@Alpha.ARPA>');
        $this->assertEquals('500 Syntax error, command unrecognized' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('RCPT TO:<Jones@Beta.ARPA>');
        $this->assertEquals('500 Syntax error, command unrecognized' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('DATA');
        $this->assertEquals('500 Syntax error, command unrecognized' . Client::MSG_SEPARATOR, $msg);


        $msg = $client->handleMessage('HELO localhost.localdomain');
        $this->assertEquals('250 localhost.localdomain' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('MAIL');
        $this->assertEquals('501 Syntax error in parameters or arguments' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('MAIL FROM:<Smith@Alpha.ARPA>');
        $this->assertEquals('250 OK' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('RCPT');
        $this->assertEquals('501 Syntax error in parameters or arguments' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('RCPT TO:<Jones@Beta.ARPA>');
        $this->assertEquals('250 OK' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('RCPT TO:<Green@Beta.ARPA>');
        $this->assertEquals('250 OK' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('RCPT TO:<invalid@example.com>');
        $this->assertEquals('550 User unknown' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('DATA');
        $this->assertEquals('354 Start mail input; end with <CRLF>.<CRLF>' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('From: Dev1 <dev1@fox21.at>');
        $this->assertEquals('', $msg);
        $msg = $client->handleMessage('To: Dev1 <dev1@fox21.at>');
        $this->assertEquals('', $msg);
        $msg = $client->handleMessage('Subject: Test');
        $this->assertEquals('', $msg);
        $msg = $client->handleMessage('');
        $this->assertEquals('', $msg);
        $msg = $client->handleMessage('Body');
        $this->assertEquals('', $msg);

        $msg = $client->handleMessage('.');
        $this->assertEquals('250 OK' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('HELP');
        $this->assertEquals('250 HELO, EHLO, MAIL FROM, RCPT TO, DATA, NOOP, QUIT' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('NOOP');
        $this->assertEquals('250 OK' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('QUIT');
        $this->assertEquals('221 localhost.localdomain Service closing transmission channel' . Client::MSG_SEPARATOR, $msg);
    }

    public function testMsgHandleAuthPlain()
    {
        $server = new Server();

        /** @var PHPUnit_Framework_MockObject_MockBuilder $mockBuilder */
        $mockBuilder = $this->getMockBuilder(Client::class);
        $mockBuilder->setMethods(['authenticate']);
        
        /** @var Client|PHPUnit_Framework_MockObject_MockObject $client */
        $client = $mockBuilder->getMock();

        $client->expects($this->at(0))
            ->method('authenticate')
            ->with('plain')
            ->will($this->returnValue(false));
        $client->expects($this->at(1))
            ->method('authenticate')
            ->with('plain')
            ->will($this->returnValue(true));
        $client->expects($this->at(2))
            ->method('authenticate')
            ->with('plain')
            ->will($this->returnValue(false));
        $client->expects($this->at(3))
            ->method('authenticate')
            ->with('plain')
            ->will($this->returnValue(true));

        $client->setServer($server);
        $client->setId(1);

        $msg = $client->handleMessage('EHLO localhost.localdomain');
        $expect = '250-localhost.localdomain' . Client::MSG_SEPARATOR;
        $expect .= '250-AUTH PLAIN LOGIN' . Client::MSG_SEPARATOR;
        $expect .= '250-STARTTLS' . Client::MSG_SEPARATOR;
        $expect .= '250 HELP' . Client::MSG_SEPARATOR;
        $this->assertEquals($expect, $msg);

        $msg = $client->handleMessage('AUTH');
        $this->assertEquals('501 Syntax error in parameters or arguments' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('AUTH CRAM-MD5');
        $this->assertEquals('502 Command not implemented' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('AUTH UNKOWN');
        $this->assertEquals('501 Syntax error in parameters or arguments' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('AUTH PLAIN');
        $this->assertEquals('334 ' . Client::MSG_SEPARATOR, $msg);

        // base64 encoded PLAIN username and password
        $msg = $client->handleMessage(base64_encode('usertestusersuper_secret_password'));
        $this->assertEquals('535 Authentication credentials invalid' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage(base64_encode('usertestusersuper_secret_password'));
        $this->assertEquals('235 2.7.0 Authentication successful' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('AUTH PLAIN ' . base64_encode('usertestusersuper_secret_password'));
        $this->assertEquals('535 Authentication credentials invalid' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('AUTH PLAIN ' . base64_encode('usertestusersuper_secret_password'));
        $this->assertEquals('235 2.7.0 Authentication successful' . Client::MSG_SEPARATOR, $msg);
    }

    public function testMsgHandleAuthLogin()
    {
        $server = new Server();

        /** @var PHPUnit_Framework_MockObject_MockBuilder $mockBuilder */
        $mockBuilder = $this->getMockBuilder(Client::class);
        $mockBuilder->setMethods(['authenticate']);

        /** @var Client|PHPUnit_Framework_MockObject_MockObject $client */
        $client = $mockBuilder->getMock();

        $client->expects($this->at(0))
            ->method('authenticate')
            ->with('login')
            ->will($this->returnValue(false));
        $client->expects($this->at(1))
            ->method('authenticate')
            ->with('login')
            ->will($this->returnValue(true));

        $client->setServer($server);
        $client->setId(1);

        $msg = $client->handleMessage('EHLO localhost.localdomain');
        $expect = '250-localhost.localdomain' . Client::MSG_SEPARATOR;
        $expect .= '250-AUTH PLAIN LOGIN' . Client::MSG_SEPARATOR;
        $expect .= '250-STARTTLS' . Client::MSG_SEPARATOR;
        $expect .= '250 HELP' . Client::MSG_SEPARATOR;
        $this->assertEquals($expect, $msg);

        $msg = $client->handleMessage('AUTH');
        $this->assertEquals('501 Syntax error in parameters or arguments' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('AUTH LOGIN');
        $this->assertEquals('334 ' . base64_encode('Username:') . Client::MSG_SEPARATOR, $msg);

        // base64 encoded LOGIN username
        $msg = $client->handleMessage(base64_encode('testuser'));
        $this->assertEquals('334 ' . base64_encode('Password:') . Client::MSG_SEPARATOR, $msg);

        // base64 encoded LOGIN password
        $msg = $client->handleMessage(base64_encode('super_secret_password'));
        $this->assertEquals('535 Authentication credentials invalid' . Client::MSG_SEPARATOR, $msg);

        // base64 encoded LOGIN password
        $msg = $client->handleMessage(base64_encode('super_secret_password'));
        $this->assertEquals('235 2.7.0 Authentication successful' . Client::MSG_SEPARATOR, $msg);
    }

    public function testMsgHandleStartTls()
    {
        $server = new Server();

        /** @var PHPUnit_Framework_MockObject_MockBuilder $mockBuilder */
        $mockBuilder = $this->getMockBuilder(StreamSocket::class);
        $mockBuilder->setMethods(['enableEncryption']);

        /** @var StreamSocket|PHPUnit_Framework_MockObject_MockObject $client */
        $socket = $mockBuilder->getMock();

        $socket->expects($this->at(0))
            ->method('enableEncryption')
            ->will($this->throwException(new RuntimeException()));
        $socket->expects($this->at(1))
            ->method('enableEncryption')
            ->will($this->returnValue(true));

        $client = new Client();
        $client->setServer($server);
        $client->setId(1);
        $client->setSocket($socket);

        $msg = $client->handleMessage('EHLO localhost.localdomain');
        $expect = '250-localhost.localdomain' . Client::MSG_SEPARATOR;
        $expect .= '250-AUTH PLAIN LOGIN' . Client::MSG_SEPARATOR;
        $expect .= '250-STARTTLS' . Client::MSG_SEPARATOR;
        $expect .= '250 HELP' . Client::MSG_SEPARATOR;
        $this->assertEquals($expect, $msg);

        $msg = $client->handleMessage('STARTTLS PARAMETER');
        $this->assertEquals('501 Syntax error in parameters or arguments' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('STARTTLS');
        $this->assertEquals('454 TLS not available due to temporary reason' . Client::MSG_SEPARATOR, $msg);

        $msg = $client->handleMessage('STARTTLS');
        $this->assertEquals('', $msg);
    }
}
