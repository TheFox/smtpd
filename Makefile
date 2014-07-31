
RELEASE_VERSION = 0.1.0-dev
RELEASE_NAME = smtpd

RM = rm -rfd
MKDIR = mkdir -p
TAR = tar
GZIP = gzip
MV = mv -i
PHPCS = vendor/bin/phpcs
PHPUNIT = vendor/bin/phpunit


.PHONY: all install update tests test_phpcs test_phpunit release clean

all: install tests

install: composer.phar

update: composer.phar
	./composer.phar selfupdate
	./composer.phar update -vv

composer.phar:
	curl -sS https://getcomposer.org/installer | php
	./composer.phar install

$(PHPCS): composer.phar

tests: test_phpcs test_phpunit

test_phpcs: $(PHPCS) vendor/thefox/phpcsrs/Standards/TheFox
	$(PHPCS) -v -s --report=full --report-width=160 --standard=vendor/thefox/phpcsrs/Standards/TheFox src tests

test_phpunit: $(PHPUNIT) phpunit.xml
	$(PHPUNIT)
	$(RM) tests/test_mailbox_*

release:
	find . -name .DS_Store -exec rm {} \;
	$(MKDIR) releases
	$(TAR) -cpf $(RELEASE_NAME)-$(RELEASE_VERSION).tar \
		README.md \
		application.php \
		composer.json \
		bootstrap.php \
		src \
		vendor/autoload.php \
		vendor/composer \
		vendor/liip \
		vendor/sebastian \
		vendor/symfony \
		vendor/thefox
	$(GZIP) -9 -f $(RELEASE_NAME)-$(RELEASE_VERSION).tar
	$(MV) ${RELEASE_NAME}-${RELEASE_VERSION}.tar.gz releases

clean:
	$(RM) composer.lock composer.phar
	$(RM) vendor/*
	$(RM) vendor
