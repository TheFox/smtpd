<?php

namespace TheFox\Test;

require_once 'TestObj.php';

use PHPUnit_Framework_TestCase;
use Zend\Mail\Message;

use TheFox\Logger\Logger;
use TheFox\Smtp\Server;
use TheFox\Smtp\Client;
use TheFox\Smtp\Event;
use TheFox\Network\Socket;

class ServerTest extends PHPUnit_Framework_TestCase{
	
	public function testBasic(){
		$server = new Server('', 0);
		$this->assertTrue($server->getLog() === null);
		
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$this->assertTrue($server->getLog() !== null);
	}
	
	public function testInit(){
		$server = new Server('', 0);
		$server->init();
		$log = $server->getLog();
		
		$this->assertTrue($log instanceof Logger);
	}
	
	public function testClientNew(){
		$socket = new Socket();
		
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$server->init();
		
		$client = $server->clientNew($socket);
		$this->assertTrue($client instanceof Client);
	}
	
	public function testClientGetByHandle(){
		$socket = new Socket();
		$socket->bind('127.0.0.1', 22143);
		$socket->listen();
		$handle1 = $socket->getHandle();
		
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$server->init();
		
		$client1 = $server->clientNew($socket);
		$client2 = $server->clientGetByHandle($handle1);
		#\Doctrine\Common\Util\Debug::dump($handle2);
		$this->assertEquals($client1, $client2);
		
		$server->shutdown();
	}
	
	public function testClientRemove(){
		$socket = new Socket();
		$socket->bind('127.0.0.1', 22143);
		$socket->listen();
		
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$server->init();
		
		$client = $server->clientNew($socket);
		$server->clientRemove($client);
		
		#\Doctrine\Common\Util\Debug::dump($server);
		$this->assertTrue($client->getStatus('hasShutdown'));
		
		$server->shutdown();
	}
	
	public function testEvent(){
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$server->init();
		
		$testData = 21;
		$phpunit = $this;
		$event1 = new Event(Event::TRIGGER_MAIL_NEW, null, function($event, $from, $rcpt, $mail) use($phpunit, &$testData) {
			#fwrite(STDOUT, 'my function: '.$event->getTrigger().', '.$testData."\n");
			$testData = 24;
			
			$phpunit->assertEquals('from@example.com', $from);
			$phpunit->assertEquals(array('to1@example.com', 'to2@example.com', 'cc@example.com', 'bcc@example.com'),
				$rcpt);
			
			$current = array();
			foreach($mail->getTo() as $n => $address){
				$current[] = $address->toString();
			}
			$phpunit->assertEquals(array('Joe User <to1@example.com>', '<to2@example.com>'), $current);
			
			$phpunit->assertEquals('Here is the subject', $mail->getSubject());
			
			return 42;
		});
		$server->eventAdd($event1);
		
		$testObj = new TestObj();
		$event2 = new Event(Event::TRIGGER_MAIL_NEW, $testObj, 'test1');
		$server->eventAdd($event2);
		
		$mail = '';
		$mail .= 'Date: Thu, 31 Jul 2014 22:18:51 +0200'.Client::MSG_SEPARATOR;
		$mail .= 'To: Joe User <to1@example.com>, to2@example.com'.Client::MSG_SEPARATOR;
		$mail .= 'From: Mailer <from@example.com>'.Client::MSG_SEPARATOR;
		$mail .= 'Cc: cc@example.com'.Client::MSG_SEPARATOR;
		$mail .= 'Reply-To: Information <reply@example.com>'.Client::MSG_SEPARATOR;
		$mail .= 'Subject: Here is the subject'.Client::MSG_SEPARATOR;
		$mail .= 'MIME-Version: 1.0'.Client::MSG_SEPARATOR;
		$mail .= 'Content-Type: text/plain; charset=iso-8859-1'.Client::MSG_SEPARATOR;
		$mail .= 'Content-Transfer-Encoding: 8bit'.Client::MSG_SEPARATOR;
		$mail .= ''.Client::MSG_SEPARATOR;
		$mail .= 'This is the message body.'.Client::MSG_SEPARATOR;
		$mail .= 'END'.Client::MSG_SEPARATOR;
		
		$zmail = Message::fromString($mail);
		
		$rcpt = array('to1@example.com', 'to2@example.com', 'cc@example.com', 'bcc@example.com');
		$server->mailNew('from@example.com', $rcpt, $zmail);
		
		$this->assertEquals(24, $testData);
		$this->assertEquals(42, $event1->getReturnValue());
		$this->assertEquals(43, $event2->getReturnValue());
	}
	
	public function testEventAuth(){
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$server->init();
		
		$username = 'testuser';
		$password = 'super_secret_password';
		
		$testData = 21;
		$phpunit = $this;
		$event1 = new Event(Event::TRIGGER_AUTH_ATTEMPT, null, function($event, $type, $credentials) use($phpunit, &$testData, $username, $password) {
			#fwrite(STDOUT, 'my function: '.$event->getTrigger().', '.$testData."\n");
			$testData = 24;
			
			$phpunit->assertEquals('LOGIN', $type);
			
			$phpunit->assertEquals(base64_encode($username), $credentials['user']);
			$phpunit->assertEquals(base64_encode($password), $credentials['password']);
			
			return 42;
		});
		$server->eventAdd($event1);
		
		$testObj = new TestObj();
		$event2 = new Event(Event::TRIGGER_AUTH_ATTEMPT, $testObj, 'test1');
		$server->eventAdd($event2);
		
		$type = 'LOGIN';
		$credentials = array('user' => base64_encode($username), 'password' => base64_encode($password));
		
		$server->authenticateUser($type, $credentials);
		
		$this->assertEquals(24, $testData);
		$this->assertEquals(42, $event1->getReturnValue());
		$this->assertEquals(43, $event2->getReturnValue());
	}
	
}
