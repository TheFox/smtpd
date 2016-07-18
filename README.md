# SMTPd

SMTP server (library) written in pure PHP. This library only provides an interface to the SMTP server-side protocol with PHP. You need to deliver the mails by yourself.

## Why this project?
Believe it or not, **email is still the killer feature of the Internet**. There are tons of projects like [PHPMailer](https://github.com/PHPMailer/PHPMailer): to send emails programmatically (with PHP). But there are not so many to receive emails from SMTP.

With this interface you can do something like this for your app users:

	User <-> MUA (like Thunderbird) <-> SMTP <-> Your PHP App

This is useful when you have a messaging application written in PHP but no graphical user interface for it. So your graphical user interface can be any [email client](http://en.wikipedia.org/wiki/Email_client). [Thunderbird](https://www.mozilla.org/en-US/thunderbird/) for instance.

## Installation
The preferred method of installation is via [Packagist](https://packagist.org/packages/thefox/smtpd) and [Composer](https://getcomposer.org/). Run the following command to install the package and add it as a requirement to composer.json:

	composer.phar require "thefox/smtpd=~0.1"

## Stand-alone server
1. Clone project from GitHub.

		git clone https://github.com/TheFox/smtpd.git

2. Install dependencies.

		make

3. To start a stand-alone server you can type the following command in your shell:

		./application.php server -d
	
	To show the usage options use `-h`:
	
		./application.php server -h

	You can change the IP and port. Default port is 20025.
	
		./application.php server -a 0.0.0.0 -p 25

**Note:** The stand-alone server is only for testing. If you want to use it for production you need to define a save/deliver function. See example below.

## Usage
See [`example.php`](example.php) file for more information.

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

## Related Links

- [RFC 821](https://tools.ietf.org/html/rfc821)
- [RFC 1651](https://tools.ietf.org/html/rfc1651)

## Related Projects
- [IMAPd](https://github.com/TheFox/imapd)

## Contribute
You're welcome to contribute to this project. Fork this project at <https://github.com/TheFox/smtpd>. You should read GitHub's [How to Fork a Repo](https://help.github.com/articles/fork-a-repo).

## Project Links
- [Packagist Package](https://packagist.org/packages/thefox/smtpd)
- [Travis CI Repository](https://travis-ci.org/TheFox/smtpd)
- [Coveralls Repository](https://coveralls.io/r/TheFox/smtpd)

## License
Copyright (C) 2014 Christian Mayer <http://fox21.at>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
