<?php

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
		
		$mail->Subject = 'Here is the subject';
		$mail->Body    = 'This is the message body.'.Client::MSG_SEPARATOR.'.'.Client::MSG_SEPARATOR.'..'.Client::MSG_SEPARATOR.'.test.'.Client::MSG_SEPARATOR.'END'.Client::MSG_SEPARATOR;
		#$mail->AltBody = 'This is the body in plain text for non-HTML mail clients.';
		
		$this->assertTrue($mail->send());
		
		fwrite(STDOUT, 'mail info: '.$mail->ErrorInfo."\n");
	}
	
}
