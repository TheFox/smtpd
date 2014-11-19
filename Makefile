
RM = rm -rfd
CHMOD = chmod
MKDIR = mkdir -p
PHPCS = vendor/bin/phpcs
PHPUNIT = vendor/bin/phpunit
COMPOSER = ./composer.phar
COMPOSER_DEV ?= --dev


.PHONY: all install install_release update test test_phpcs test_phpunit test_phpunit_cc release clean clean_release

all: install test

install: $(COMPOSER)
	$(COMPOSER) install $(COMPOSER_PREFER_SOURCE) --no-interaction $(COMPOSER_DEV)

install_release: $(COMPOSER)
	$(MAKE) install COMPOSER_DEV=--no-dev

update: $(COMPOSER)
	$(COMPOSER) selfupdate
	$(COMPOSER) update

$(COMPOSER):
	curl -sS https://getcomposer.org/installer | php
	$(CHMOD) 755 $(COMPOSER)

$(PHPCS): $(COMPOSER)

test: test_phpcs test_phpunit

test_phpcs: $(PHPCS) vendor/thefox/phpcsrs/Standards/TheFox
	$(PHPCS) -v -s --report=full --report-width=160 --standard=vendor/thefox/phpcsrs/Standards/TheFox src tests

test_phpunit: $(PHPUNIT) phpunit.xml
	TEST=true $(PHPUNIT) $(PHPUNIT_COVERAGE_HTML) $(PHPUNIT_COVERAGE_CLOVER)

test_phpunit_cc:
	$(MAKE) test_phpunit PHPUNIT_COVERAGE_HTML="--coverage-html build/report"

release: release.sh
	./release.sh

clean:
	$(RM) composer.lock $(COMPOSER)
	$(RM) vendor/*
	$(RM) vendor

clean_release:
	$(RM) composer.lock $(COMPOSER)
	$(RM) log pid
