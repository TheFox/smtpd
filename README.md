# SMTPd
SMTP server (library) written in PHP. This library only provides an interface to the SMTP protocol with PHP. You need to deliver the mails by yourself.

## Installation
The preferred method of installation is via [Packagist](https://packagist.org/packages/thefox/smtpd) and [Composer](https://getcomposer.org/). Run the following command to install the package and add it as a requirement to composer.json:

`composer.phar require "thefox/smtpd=0.1.*"`

## Stand-alone server
To start a stand-alone server you can type the following command in your shell:

`./application.php server -d`

To show the usage options use `-h`:

`./application.php server -h`

You can change the IP and port (default port is 20025):

`./application.php server -a 0.0.0.0 -p 25`

**Note:** The stand-alone server is only for testing. If you want to use it for production you need to define a save/deliver function. See example below.

## Usage
Use this library to provide an IMAP server in your own project.

```php
<?php
require_once __DIR__.'/vendor/autoload.php';
use TheFox\Smtp\Server;
use TheFox\Smtp\Event;

$server = new Server('127.0.0.1', 20025);
$server->init();
$server->listen();

$event1 = new Event(Event::TRIGGER_MAIL_NEW, null, function($event, $from, $rcpt, $mail){
	// Do stuff: DNS lookup the MX record for the recipient's domain, ...
});
$server->eventAdd($event1);

$server->loop();
```

`loop()` is only a loop with `run()` executed. So you need to execute `run()` in your own project to keep the SMTP server updated.

## RFC 821 Implementation
### Complete
- 3.5 OPENING AND CLOSING

### Incomplete
- 3.1 MAIL
- 4.1.1 COMMAND SEMANTICS
	- HELO
	- MAIL
	- RCPT
	- DATA
	- NOOP
	- QUIT

## RFC 1651 Implementation
### Complete
- 4.1.1 First command
- 4.5 Error responses from extended servers

## Contribute
You're welcome to contribute to this project. Fork this project at <https://github.com/TheFox/smtpd>. You should read GitHub's [How to Fork a Repo](https://help.github.com/articles/fork-a-repo).

## License
Copyright (C) 2014 Christian Mayer <http://fox21.at>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
