
RM = rm -rfd
CHMOD = chmod
PHPCS = vendor/bin/phpcs
PHPUNIT = vendor/bin/phpunit


.PHONY: all install update test test_phpcs test_phpunit test_phpunit_cc release clean

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
	$(PHPUNIT) $(PHPUNIT_COVERAGE_HTML) $(PHPUNIT_COVERAGE_CLOVER)

test_phpunit_cc:
	$(MAKE) test_phpunit PHPUNIT_COVERAGE_HTML="--coverage-html build/report"

release: release.sh
	./release.sh

clean:
	$(RM) composer.lock composer.phar
	$(RM) vendor/*
	$(RM) vendor
