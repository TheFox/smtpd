<?php

use TheFox\Logger\Logger;
use TheFox\Smtp\Server;
use TheFox\Smtp\Client;

class ClientTest extends PHPUnit_Framework_TestCase{
	
	public function testMsgHandleHello(){
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$server->init();
		
		$client = new Client();
		$client->setServer($server);
		$client->setId(1);
		
		
		$msg = $client->msgHandle('HELO localhost.localdomain');
		$this->assertEquals('250 localhost.localdomain'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('EHLO localhost.localdomain');
		$this->assertEquals('502 Command not implemented'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('XYZ abc');
		$this->assertEquals('500 Syntax error, command unrecognized'.Client::MSG_SEPARATOR, $msg);
	}
	
	public function testMsgHandleMail(){
		$server = new Server('', 0);
		$server->setLog(new Logger('test_application'));
		$server->init();
		
		$client = new Client();
		$client->setServer($server);
		$client->setId(1);
		
		
		$msg = $client->msgHandle('MAIL FROM:<Smith@Alpha.ARPA>');
		$this->assertEquals('500 Syntax error, command unrecognized'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('HELO localhost.localdomain');
		$this->assertEquals('250 localhost.localdomain'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('MAIL');
		$this->assertEquals('501 Syntax error in parameters or arguments'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('MAIL FROM:<Smith@Alpha.ARPA>');
		$this->assertEquals('250 OK'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('RCPT');
		$this->assertEquals('501 Syntax error in parameters or arguments'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('RCPT TO:<Jones@Beta.ARPA>');
		$this->assertEquals('250 OK'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('RCPT TO:<Green@Beta.ARPA>');
		$this->assertEquals('250 OK'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('DATA');
		$this->assertEquals('354 Start mail input; end with <CRLF>.<CRLF>'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('From: TheFox <thefox@fox21.at>');
		$this->assertEquals('', $msg);
		$msg = $client->msgHandle('To: TheFox <thefox@fox21.at>');
		$this->assertEquals('', $msg);
		$msg = $client->msgHandle('Subject: Test');
		$this->assertEquals('', $msg);
		$msg = $client->msgHandle('');
		$this->assertEquals('', $msg);
		$msg = $client->msgHandle('Body');
		$this->assertEquals('', $msg);
		
		$msg = $client->msgHandle('.');
		$this->assertEquals('250 OK'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('QUIT');
		$this->assertEquals('221 localhost.localdomain Service closing transmission channel'.Client::MSG_SEPARATOR, $msg);
	}
	
}
