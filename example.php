<?php

require_once __DIR__ . '/vendor/autoload.php';

use TheFox\Smtp\Server;
use TheFox\Smtp\Event;
use Zend\Mail\Message;

// Certificate data:
$dn = [
    'countryName' => 'UK',
    'stateOrProvinceName' => 'Isle Of Wight',
    'localityName' => 'Cowes',
    'organizationName' => 'Open Sauce Systems',
    'organizationalUnitName' => 'Dev',
    'commonName' => '127.0.0.1',
    'emailAddress' => 'info@opensauce.systems',
];

// Generate certificate
$privkey = openssl_pkey_new();
$cert = openssl_csr_new($dn, $privkey);
$cert = openssl_csr_sign($cert, null, $privkey, 365);

// Generate PEM file
$pem = [];
openssl_x509_export($cert, $pem[0]);
openssl_pkey_export($privkey, $pem[1]);
$pem = implode($pem);

// Save PEM file
$pemfile = __DIR__ . '/server.pem';
file_put_contents($pemfile, $pem);

$contextOptions = [
    'ssl' => [
        'verify_peer' => false,
        'local_cert' => $pemfile,
        'allow_self_signed' => true,
    ],
];

$server = new Server('127.0.0.1', 20025);
$server->init();

if (!$server->listen($contextOptions)) {
    print 'Server could not listen.' . "\n";
    exit(1);
}

$sendEvent = new Event(Event::TRIGGER_NEW_MAIL, null, function (Event $event, string $from, array $rcpts, Message $mail) {
    // Do stuff: DNS lookup the MX record for the recipient's domain,
    //           check whether the recipient is on a whitelist,
    //           handle the email, etc, ...

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

    foreach ($rcpts as $rcptId => $rcpt) {
        $mailer->AddAddress($rcpt);
    }

    if (!$mailer->Send()) {
        throw new Exception($mailer->ErrorInfo);
    }
});

$authEvent = new Event(Event::TRIGGER_AUTH_ATTEMPT, null, function ($event, $type, $credentials): bool {
    // Do stuff: Check credentials against database, ...

    return true;
});

$server->addEvent($sendEvent);
$server->addEvent($authEvent);

// `$server->loop()` is only a while-loop with `$server->run()` executed.
// If you also need to process other things in your application as well
// it's recommded to execute `$server->run()` from time to time.
// You need to execute `$server->run()` in your own project to keep the SMTP server updated.
// If you use your own loop to keep everything running consider executing `$server->run()` from time to time.
$server->loop();
