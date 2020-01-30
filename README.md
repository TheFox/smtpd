# SMTPd

SMTP server (library) for receiving emails, written in pure PHP. This library provides an interface to the SMTP server-side protocol with PHP. It creates a `\Zend\Mail\Message` Class object for every incoming email and hands this object to a custom PHP function for further processing. The project is in Beta status, so it's not recommended for production use.

The `d` in `SMTPd` stands for [Daemon](https://en.wikipedia.org/wiki/Daemon_(computing)). This script can run in background like any other daemon process. It's not meant for running as a webapplication.

## Why this project?

Believe it or not, **email is still the killer feature of the Internet**. There are tons of projects like [PHPMailer](https://github.com/PHPMailer/PHPMailer): to send emails programmatically (with PHP). But there are not so many to receive emails from SMTP.

With this interface you can do something like this for your app users:

```
+------+     +------------------------+     +-------+     +--------------+
| User +---> | MUA (like Thunderbird) +---> | SMTPd +---> | Your PHP App |
+------+     +------------------------+     +-------+     +--------------+
```

This is useful when you have a messaging application written in PHP but no graphical user interface for it. So your graphical user interface can be any [email client](http://en.wikipedia.org/wiki/Email_client). [Thunderbird](https://www.mozilla.org/en-US/thunderbird/) for instance.

## Project Outlines

The project outlines as described in my blog post about [Open Source Software Collaboration](https://blog.fox21.at/2019/02/21/open-source-software-collaboration.html).

- The main purpose of this software is to provide a server-side SMTP API for PHP scripts.
- Although the RFC implementations are not completed yet, they must be strict.
- More features can be possible in the future. In perspective of the protocols the features must be a RFC implementation.
- This list is open. Feel free to request features.

## Planned Features

- Full [RFC 821](https://tools.ietf.org/html/rfc821) implementation.
- Full [RFC 1651](https://tools.ietf.org/html/rfc1651) implementation.
- Full [RFC 1869](https://tools.ietf.org/html/rfc1869) implementation.
- Replace `Zend\Mail` with a better solution.

## Installation

The preferred method of installation is via [Packagist](https://packagist.org/packages/thefox/smtpd) and [Composer](https://getcomposer.org/). Run the following command to install the package and add it as a requirement to composer.json:

```bash
composer require thefox/smtpd
```

## Delivery

At the moment the server accepts all incoming emails. You decide what happens with incoming emails by adding `Event`s to the `Server` object (`$server->eventAdd($event)`). The server can handle certain events. Each event will be executed on a certain trigger. Even if you don't add any Events to the Server it accepts all incoming emails.

## Events

At the moment there are two Event Triggers.

- `TRIGGER_NEW_MAIL`: will be triggered when a Client has finished transmitting a new email.
- `TRIGGER_AUTH_ATTEMPT`: will be triggered when a Client wants to authenticate. Return a boolean from the callback function whether the authentication was successful or not.

## Examples

See also [`example.php`](example.php) file for full examples.

### Trigger New Mail Example

```php
$server = new Server(...);

$event = new Event(Event::TRIGGER_NEW_MAIL, null, function(Event $event, $from, $rcpts, $mail){
	// Do stuff: handle email, ...
});
$server->addEvent($event);
$server->loop();
```

### Trigger Auth Example

```php
$server = new Server(...);

$event = new Event(Event::TRIGGER_AUTH_ATTEMPT, null, function(Event $event, $type, $credentials): bool{
	// Do stuff: Check credentials against database, ...
	return true;
});
$server->addEvent($event);
$server->loop();
```

### Use SMTP Server with own loop

```php
$server = new Server(...);

// Set up server here.
// Add Events, etc, ...

while(myApplicationRuns()){
	// Do stuff your application needs.
	// ...
	
	// Run main SMTPd loop, once.
	$server->run();
	usleep(10000); // Never run a main thread loop without sleep. Never!
}
```

## RFC 821 Implementation

### Complete implementation

- 3.5 OPENING AND CLOSING

### Incomplete implementation

- 3.1 MAIL
- 4.1.1 COMMAND SEMANTICS
	- HELO
	- MAIL
	- RCPT
	- DATA
	- NOOP
	- QUIT

## RFC 1651 Implementation

### Complete implementation

- 4.1.1 First command
- 4.5 Error responses from extended servers

## RFC 3207 Implementation

## RFC 4954 Implementation

- 4. The AUTH Command

## Related Links

- [RFC 821](https://tools.ietf.org/html/rfc821)
- [RFC 1425](https://tools.ietf.org/html/rfc1425)
- [RFC 1651](https://tools.ietf.org/html/rfc1651)
- [RFC 1869](https://tools.ietf.org/html/rfc1869)
- [RFC 2821](https://tools.ietf.org/html/rfc2821)
- [RFC 3207](https://tools.ietf.org/html/rfc3207)
- [RFC 4954](https://tools.ietf.org/html/rfc4954)

## Related Projects

- [IMAPd](https://github.com/TheFox/imapd)

## Contribute

You're welcome to contribute to this project. Fork this project at <https://github.com/TheFox/smtpd>. You should read GitHub's [How to Fork a Repo](https://help.github.com/articles/fork-a-repo).

## Project Links

- [Packagist Package](https://packagist.org/packages/thefox/smtpd)
- [Travis CI Repository](https://travis-ci.org/TheFox/smtpd)
