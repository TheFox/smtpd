
RM = rm -rfd
CHMOD = chmod
MKDIR = mkdir -p
VENDOR = vendor
PHPCS = vendor/bin/phpcs
PHPCS_STANDARD = vendor/thefox/phpcsrs/Standards/TheFox
PHPCS_REPORT = --report=full --report-width=160
PHPUNIT = vendor/bin/phpunit
COMPOSER = ./composer.phar
COMPOSER_DEV ?= 


.PHONY: all install install_release update test test_phpcs test_phpunit test_phpunit_cc release clean clean_release

all: install test

install: $(VENDOR)

install_release: $(COMPOSER)
	$(MAKE) install COMPOSER_DEV=--no-dev

update: $(COMPOSER)
	$(COMPOSER) selfupdate
	$(COMPOSER) update

test: test_phpcs test_phpunit

test_phpcs: $(PHPCS) vendor/thefox/phpcsrs/Standards/TheFox
	$(PHPCS) -v -s $(PHPCS_REPORT) --standard=$(PHPCS_STANDARD) src tests

test_phpunit: $(PHPUNIT) phpunit.xml
	TEST=true $(PHPUNIT) $(PHPUNIT_COVERAGE_HTML) $(PHPUNIT_COVERAGE_CLOVER)

test_phpunit_cc: build
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

$(VENDOR): $(COMPOSER)
	$(COMPOSER) install $(COMPOSER_PREFER_SOURCE) --no-interaction $(COMPOSER_DEV)

$(COMPOSER):
	curl -sS https://getcomposer.org/installer | php
	$(CHMOD) a+rx-w,u+w $(COMPOSER)

$(PHPCS): $(VENDOR)

$(PHPUNIT): $(VENDOR)

build:
	$(MKDIR) build
	$(MKDIR) build/logs
	$(CHMOD) a-rwx,u+rwx build
