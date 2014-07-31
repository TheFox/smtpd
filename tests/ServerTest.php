<?php

use Zend\Mail\Message;

use TheFox\Logger\Logger;
use TheFox\Smtp\Server;
use TheFox\Smtp\Client;
use TheFox\Smtp\Event;

class ServerTest extends PHPUnit_Framework_TestCase{
	
	public function testBasic(){
		$server = new Server('', 0);
		$this->assertTrue($server->getLog() === null);
		
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$this->assertTrue($server->getLog() !== null);
	}
	
	public function testEvent(){
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$server->init();
		
		$testData = 21;
		$event1 = new Event(Event::TRIGGER_MAIL_NEW, null, function($event, $from, $rcpt, $mail) use(&$testData) {
			#fwrite(STDOUT, 'my function: '.$event->getTrigger().', '.$testData."\n");
			$testData = 24;
			
			$this->assertEquals('from@example.com', $from);
			$this->assertEquals(array('to1@example.com', 'to2@example.com', 'cc@example.com', 'bcc@example.com'),
				$rcpt);
			
			$current = array();
			foreach($mail->getTo() as $n => $address){
				$current[] = $address->toString();
			}
			$this->assertEquals(array('Joe User <to1@example.com>', '<to2@example.com>'), $current);
			
			$this->assertEquals('Here is the subject', $mail->getSubject());
			
			return 42;
		});
		$server->eventAdd($event1);
		
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
	}
	
}
