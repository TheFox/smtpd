
RM = rm -rfd
CHMOD = chmod
PHPCS = vendor/bin/phpcs
PHPUNIT = vendor/bin/phpunit


.PHONY: all install update tests test_phpcs test_phpunit release clean

all: install tests

install: composer.phar
	./composer.phar install --prefer-source --no-interaction --dev

update: composer.phar
	./composer.phar selfupdate
	./composer.phar update

composer.phar:
	curl -sS https://getcomposer.org/installer | php
	$(CHMOD) 755 ./composer.phar

$(PHPCS): composer.phar

tests: test_phpcs test_phpunit

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
