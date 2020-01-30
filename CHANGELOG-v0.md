# Release Notes for SMTPd v0.x

## v0.8.0 [unreleased]

- MIT License

## v0.7.0

- Add capability to reject RCPT addresses via an event handler. #11
- Use PHP 7.0 inside Docker.
- Use PSR-4.
- Dev Docker setup.

## v0.6.1

- Moved thefox/network to require. Fixes #10.

## v0.6.0

- Use thefox/network 1.0.
- Removed unused thefox/utilities 1.1.

## v0.5.0

- Functions renamed.
- Moved Network namespace to separate Repo.
- Use PSR Logger Interface instead of own logger.

## v0.4.0

- PSR1/PSR2
- DocBlocks added.
- Moved Make targets to Bash scripts.
- PHP7 only.

## v0.3.2

- Suggestions by PHPStan fixed.
- Test against PHP 7.1.

## v0.3.1

- Bugfix: use defined() to check to const. #6

## v0.3.0

- Merge pull request #8 from ashleyhood/feature-starttls
- Stand-alone server removed. #7 #9
- Releases script removed.
- PHP 7 ready.
- Clean up.

## v0.2.0

- [RFC 4954](https://tools.ietf.org/html/rfc4954) ESMTP implemented.

## v0.1.4

- Code style fix.

## v0.1.3

- Tests.
- Release script improvement.

## v0.1.2

- PHP 5.3 support fixes.
- Travis support added.

## v0.1.1

- Version numbers.

## v0.1.0

- Basic implementation.
- User events.
