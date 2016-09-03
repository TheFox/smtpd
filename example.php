<?php

require_once __DIR__.'/vendor/autoload.php';

use TheFox\Smtp\Server;
use TheFox\Smtp\Event;

// Certificate data:
$dn = array(
	'countryName' => 'UK',
	'stateOrProvinceName' => 'Isle Of Wight',
	'localityName' => 'Cowes',
	'organizationName' => 'Open Sauce Systems',
	'organizationalUnitName' => 'Dev',
	'commonName' => '127.0.0.1',
	'emailAddress' => 'info@opensauce.systems',
);

// Generate certificate
$privkey = openssl_pkey_new();
$cert    = openssl_csr_new($dn, $privkey);
$cert    = openssl_csr_sign($cert, null, $privkey, 365);

// Generate PEM file
$pem = array();
openssl_x509_export($cert, $pem[0]);
openssl_pkey_export($privkey, $pem[1]);
$pem = implode($pem);

// Save PEM file
$pemfile = __DIR__.'/server.pem';
file_put_contents($pemfile, $pem);

$contextOptions = array(
	'ssl' => array(
		'verify_peer'       => false,
		'local_cert'        => $pemfile,
		'allow_self_signed' => true,
	)
);

$server = new Server('127.0.0.1', 20025);
$server->init();
$server->listen($contextOptions);

$sendEvent = new Event(Event::TRIGGER_MAIL_NEW, null, function($event, $from, $rcpts, $mail){
	// Do stuff: DNS lookup the MX record for the recipient's domain, ...
	
	// For example, use PHPMailer to reply the mail through mail servers.
	$mailer = new PHPMailer();
	$mailer->IsSMTP();
	$mailer->SMTPAuth = true;
	$mailer->SMTPSecure = 'tls';
	$mailer->Host = 'smtp.example.com';
	$mailer->Port = 587;
	$mailer->Username = 'example@example.com';
	$mailer->Password = 'your_password';
	$mailer->SetFrom('example@example.com', 'John Doe');
	$mailer->Subject = $mail->getSubject();
	$mailer->AltBody = $mail->getBody();
	$mailer->MsgHTML($mail->getBody());
	
	foreach($rcpts as $rcptId => $rcpt){
		$mailer->AddAddress($rcpt);
	}
	
	if(!$mailer->Send()){
		throw new Exception($mailer->ErrorInfo);
	}
});

$authEvent = new Event(Event::TRIGGER_AUTH_ATTEMPT, null, function ($event, $type, $credentials) {
	// Do stuff: Check credentials against database, ...

	return true;
});

$server->eventAdd($sendEvent);
$server->eventAdd($authEvent);

// `loop()` is only a loop with `run()` executed.
// So you need to execute `run()` in your own project to keep the SMTP server updated.
$server->loop();
