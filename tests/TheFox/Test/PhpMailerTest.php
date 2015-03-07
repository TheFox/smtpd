<?php

namespace TheFox\Test;

use PHPUnit_Framework_TestCase;

use TheFox\Logger\Logger;
use TheFox\Smtp\Server;
use TheFox\Smtp\Client;

class PhpMailerTest extends PHPUnit_Framework_TestCase{
	
	/**
	 * @group medium
	 */
	public function testMailing(){
		#$server = new Server('127.0.0.1', 20025);
		#$server->init();
		#$server->listen();
		#$server->run();
		
		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->Host = '127.0.0.1:20025';
		$mail->SMTPAuth = false;
		
		$mail->From = 'from@example.com';
		$mail->FromName = 'Mailer';
		$mail->addAddress('to1@example.com', 'Joe User');
		$mail->addAddress('to2@example.com');
		$mail->addReplyTo('reply@example.com', 'Information');
		$mail->addCC('cc@example.com');
		$mail->addBCC('bcc@example.com');
		$mail->isHTML(false);
		
		$body = '';
		$body .= 'This is the message body.'.Client::MSG_SEPARATOR;
		$body .= '.'.Client::MSG_SEPARATOR;
		$body .= '..'.Client::MSG_SEPARATOR;
		$body .= '.test.'.Client::MSG_SEPARATOR;
		$body .= 'END'.Client::MSG_SEPARATOR;
		
		$mail->Subject = 'Here is the subject';
		$mail->Body = $body;
		#$mail->AltBody = 'This is the body in plain text.';
		
		$this->assertTrue($mail->send());
		
		fwrite(STDOUT, 'mail info: /'.$mail->ErrorInfo.'/'."\n");
	}
	
}
