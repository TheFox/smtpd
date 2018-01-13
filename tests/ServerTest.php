<?php

namespace TheFox\Test;

require_once 'TestObj.php';

use PHPUnit\Framework\TestCase;
use Zend\Mail\Message;
use TheFox\Smtp\Server;
use TheFox\Smtp\Client;
use TheFox\Smtp\Event;
use TheFox\Network\Socket;

class ServerTest extends TestCase
{
    public function testClientNew()
    {
        $socket = new Socket();

        $server = new Server();

        $client = $server->newClient($socket);
        $this->assertTrue($client instanceof Client);
    }

    public function testClientGetByHandle()
    {
        $socket = new Socket();
        $socket->bind('127.0.0.1', 22143);
        $socket->listen();
        $handle1 = $socket->getHandle();

        $server = new Server();

        $client1 = $server->newClient($socket);
        $client2 = $server->getClientByHandle($handle1);
        #\Doctrine\Common\Util\Debug::dump($handle2);
        $this->assertEquals($client1, $client2);
        
        $this->assertNull($server->getClientByHandle(null));

        $server->shutdown();
    }

    public function testClientRemove()
    {
        $socket = new Socket();
        $socket->bind('127.0.0.1', 22143);
        $socket->listen();

        $server = new Server();

        $client = $server->newClient($socket);
        $server->removeClient($client);

        $this->assertTrue($client->getStatus('hasShutdown'));

        $server->shutdown();
    }

    public function testEvent()
    {
        $server = new Server();

        $testData = 21;
        $phpunit = $this;
        $event1 = new Event(Event::TRIGGER_NEW_MAIL, null, function ($event, $from, $rcpt, $mail) use ($phpunit, &$testData) {
            #fwrite(STDOUT, 'my function: '.$event->getTrigger().', '.$testData."\n");
            $testData = 24;

            $phpunit->assertEquals('from@example.com', $from);
            $phpunit->assertEquals(
                ['to1@example.com', 'to2@example.com', 'cc@example.com', 'bcc@example.com'],
                $rcpt
            );

            $current = [];
            foreach ($mail->getTo() as $n => $address) {
                $current[] = $address->toString();
            }
            $phpunit->assertEquals(['Joe User <to1@example.com>', '<to2@example.com>'], $current);

            $phpunit->assertEquals('Here is the subject', $mail->getSubject());

            return 42;
        });
        $server->addEvent($event1);

        $testObj = new TestObj();
        $event2 = new Event(Event::TRIGGER_NEW_MAIL, $testObj, 'test1');
        $server->addEvent($event2);

        $mail = '';
        $mail .= 'Date: Thu, 31 Jul 2014 22:18:51 +0200' . Client::MSG_SEPARATOR;
        $mail .= 'To: Joe User <to1@example.com>, to2@example.com' . Client::MSG_SEPARATOR;
        $mail .= 'From: Mailer <from@example.com>' . Client::MSG_SEPARATOR;
        $mail .= 'Cc: cc@example.com' . Client::MSG_SEPARATOR;
        $mail .= 'Reply-To: Information <reply@example.com>' . Client::MSG_SEPARATOR;
        $mail .= 'Subject: Here is the subject' . Client::MSG_SEPARATOR;
        $mail .= 'MIME-Version: 1.0' . Client::MSG_SEPARATOR;
        $mail .= 'Content-Type: text/plain; charset=iso-8859-1' . Client::MSG_SEPARATOR;
        $mail .= 'Content-Transfer-Encoding: 8bit' . Client::MSG_SEPARATOR;
        $mail .= '' . Client::MSG_SEPARATOR;
        $mail .= 'This is the message body.' . Client::MSG_SEPARATOR;
        $mail .= 'END' . Client::MSG_SEPARATOR;

        $zmail = Message::fromString($mail);

        $rcpt = ['to1@example.com', 'to2@example.com', 'cc@example.com', 'bcc@example.com'];
        $server->newMail('from@example.com', $rcpt, $zmail);

        $this->assertEquals(24, $testData);
        $this->assertEquals(42, $event1->getReturnValue());
        $this->assertEquals(43, $event2->getReturnValue());
    }

    public function rcptProvider()
    {
        return [
            'valid' => ['valid@example.com', true],
            'invalid' => ['invalid@example.com', false],
        ];
    }

    /**
     * @dataProvider rcptProvider
     * @param string $mail
     * @param bool $valid
     */
    public function testEventNewRcpt($mail, $valid)
    {
        $server = new Server();
        $phpunit = $this;
        $event1 = new Event(Event::TRIGGER_NEW_RCPT, null, function ($event, $rcpt) use ($phpunit, $mail, $valid) {
            $phpunit->assertEquals($mail, $rcpt);
            return $valid;
        });
        $server->addEvent($event1);

        $return = $server->newRcpt($mail);
        $this->assertEquals($valid, $return);
    }

    public function testEventAuthWithFalse()
    {
        $server = new Server();

        $username = 'testuser';
        $password = 'super_secret_password';

        $phpunit = $this;
        $event1 = new Event(
            Event::TRIGGER_AUTH_ATTEMPT,
            null,
            function ($event, $method, $credentials) {

                return false;
            }
        );
        $server->addEvent($event1);

        $testObj = new TestObj();
        $event2 = new Event(Event::TRIGGER_AUTH_ATTEMPT, $testObj, 'test2');
        $server->addEvent($event2);

        $method = 'LOGIN';
        $credentials = ['user' => base64_encode($username), 'password' => base64_encode($password)];

        $authenticated = $server->authenticateUser($method, $credentials);

        $this->assertFalse($authenticated);
    }

    public function testEventAuthWithAllTrue()
    {
        $server = new Server();

        $username = 'testuser';
        $password = 'super_secret_password';

        $phpunit = $this;
        $event1 = new Event(
            Event::TRIGGER_AUTH_ATTEMPT,
            null,
            function ($event, $method, $credentials) {
                return true;
            }
        );
        $server->addEvent($event1);

        $testObj = new TestObj();
        $event2 = new Event(Event::TRIGGER_AUTH_ATTEMPT, $testObj, 'test2');
        $server->addEvent($event2);

        $method = 'LOGIN';
        $credentials = ['user' => base64_encode($username), 'password' => base64_encode($password)];

        $authenticated = $server->authenticateUser($method, $credentials);

        $this->assertTrue($authenticated);
    }
}
