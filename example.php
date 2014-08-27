<?php
require_once __DIR__.'/vendor/autoload.php';
use TheFox\Smtp\Server;
use TheFox\Smtp\Event;

$server = new Server('127.0.0.1', 20025);
$server->init();
$server->listen();

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
$server->eventAdd($sendEvent);

// `loop()` is only a loop with `run()` executed.
// So you need to execute `run()` in your own project to keep the SMTP server updated.
$server->loop();
