
RM = rm -rfd
CHMOD = chmod
MKDIR = mkdir -p
VENDOR = vendor
PHPCS = vendor/bin/phpcs
PHPCS_STANDARD = vendor/thefox/phpcsrs/Standards/TheFox
PHPCS_OPTIONS = --report=full --report-width=160
PHPUNIT = vendor/bin/phpunit
COMPOSER = ./composer.phar
COMPOSER_OPTIONS ?= --no-interaction


.PHONY: all
all: install test

.PHONY: install
install: $(VENDOR)

.PHONY: update 
update: $(COMPOSER)
	$(COMPOSER) selfupdate
	$(COMPOSER) update

.PHONY: test
test: test_phpcs test_phpunit

.PHONY: test_phpcs
test_phpcs: $(PHPCS) vendor/thefox/phpcsrs/Standards/TheFox
	$(PHPCS) -v -s $(PHPCS_OPTIONS) --standard=$(PHPCS_STANDARD) src tests

.PHONY: test_phpunit
test_phpunit: $(PHPUNIT) phpunit.xml
	$(PHPUNIT) $(PHPUNIT_OPTIONS)

.PHONY: test_phpunit_cc
test_phpunit_cc: build
	$(MAKE) test_phpunit PHPUNIT_OPTIONS="--coverage-html build/report"

.PHONY: clean
clean:
	$(RM) composer.lock $(COMPOSER)
	$(RM) vendor/*
	$(RM) vendor

$(VENDOR): $(COMPOSER)
	$(COMPOSER) install $(COMPOSER_OPTIONS) $(COMPOSER_DEV)

$(COMPOSER):
	curl -sS https://getcomposer.org/installer | php
	$(CHMOD) u=rwx,go=rx $(COMPOSER)

$(PHPCS): $(VENDOR)

$(PHPUNIT): $(VENDOR)

build:
	$(MKDIR) $@
	$(MKDIR) $@/logs
	$(CHMOD) u=rwx,go-rwx $@
