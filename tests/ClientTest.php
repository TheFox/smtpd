<?php

use TheFox\Logger\Logger;
use TheFox\Smtp\Server;
use TheFox\Smtp\Client;

class ClientTest extends PHPUnit_Framework_TestCase{
	
	public function testSetId(){
		$client = new Client();
		$client->setId(1);
		
		$this->assertEquals(1, $client->getId());
	}
	
	public function testSetIp(){
		$client = new Client();
		$client->setIp('192.168.241.21');
		$this->assertEquals('192.168.241.21', $client->getIp());
	}
	
	public function testGetIp(){
		$client = new Client();
		$this->assertEquals('', $client->getIp());
	}
	
	public function testSetPort(){
		$client = new Client();
		$client->setPort(1024);
		$this->assertEquals(1024, $client->getPort());
	}
	
	public function testGetPort(){
		$client = new Client();
		$this->assertEquals(0, $client->getPort());
	}
	
	public function testGetIpPort1(){
		$client = new Client();
		$client->setIp('192.168.241.21');
		$client->setPort(1024);
		$this->assertEquals('192.168.241.21:1024', $client->getIpPort());
	}
	
	public function testGetIpPort2(){
		$client = new Client();
		$client->setIpPort('192.168.241.21', 1024);
		$this->assertEquals('192.168.241.21:1024', $client->getIpPort());
	}
	
	public function testGetLog(){
		$client = new Client();
		$log = $client->getLog();
		$this->assertEquals(null, $log);
	}
	
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
		
		$msg = $client->msgHandle('RCPT TO:<Jones@Beta.ARPA>');
		$this->assertEquals('500 Syntax error, command unrecognized'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('DATA');
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
		
		$msg = $client->msgHandle('From: Dev1 <dev1@fox21.at>');
		$this->assertEquals('', $msg);
		$msg = $client->msgHandle('To: Dev1 <dev1@fox21.at>');
		$this->assertEquals('', $msg);
		$msg = $client->msgHandle('Subject: Test');
		$this->assertEquals('', $msg);
		$msg = $client->msgHandle('');
		$this->assertEquals('', $msg);
		$msg = $client->msgHandle('Body');
		$this->assertEquals('', $msg);
		
		$msg = $client->msgHandle('.');
		$this->assertEquals('250 OK'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('NOOP');
		$this->assertEquals('250 OK'.Client::MSG_SEPARATOR, $msg);
		
		$msg = $client->msgHandle('QUIT');
		$this->assertEquals('221 localhost.localdomain Service closing transmission channel'.Client::MSG_SEPARATOR, $msg);
	}
	
}
