
RM = rm -rfd
CHMOD = chmod
PHPCS = vendor/bin/phpcs
PHPUNIT = vendor/bin/phpunit
COMPOSER_PREFER_SOURCE := $(shell echo $(COMPOSER_PREFER_SOURCE))


.PHONY: all install update test test_phpcs test_phpunit release clean

all: install test

install: composer.phar
	./composer.phar install $(COMPOSER_PREFER_SOURCE) --no-interaction --dev

update: composer.phar
	./composer.phar selfupdate
	./composer.phar update

composer.phar:
	curl -sS https://getcomposer.org/installer | php
	$(CHMOD) 755 ./composer.phar

$(PHPCS): composer.phar

test: test_phpcs test_phpunit

test_phpcs: $(PHPCS) vendor/thefox/phpcsrs/Standards/TheFox
	$(PHPCS) -v -s --report=full --report-width=160 --standard=vendor/thefox/phpcsrs/Standards/TheFox src tests

test_phpunit: $(PHPUNIT) phpunit.xml
	$(PHPUNIT)

release: release.sh
	./release.sh

clean:
	$(RM) composer.lock composer.phar
	$(RM) vendor/*
	$(RM) vendor
