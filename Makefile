# epfl-coming-soon Makefile
SHELL := /bin/bash

# Define some useful variables
PROJECT_NAME := $(shell basename $(CURDIR))
VERSION := $(shell cat epfl-coming-soon.php | grep '* Version:' | awk '{print $$3}')
REPO_OWNER_NAME := $(shell git config --get user.name)
REPO_OWNER_EMAIL := $(shell git config --get user.email)
DOMAIN_PATH := $(shell cat epfl-coming-soon.php | grep '* Domain Path:' | awk '{print $$4}')

.PHONY: help
## Print this help (see <https://gist.github.com/klmr/575726c7e05d8780505a> for explanation)
help:
	@echo "$$(tput bold)Available rules (alphabetical order):$$(tput sgr0)";sed -ne"/^## /{h;s/.*//;:d" -e"H;n;s/^## //;td" -e"s/:.*//;G;s/\\n## /---/;s/\\n/ /g;p;}" ${MAKEFILE_LIST}|LC_ALL='C' sort -f |awk -F --- -v n=$$(tput cols) -v i=20 -v a="$$(tput setaf 6)" -v z="$$(tput sgr0)" '{printf"%s%*s%s ",a,-i,$$1,z;m=split($$2,w," ");l=n-i;for(j=1;j<=m;j++){l-=length(w[j])+1;if(l<= 0){l=n-i-length(w[j])-1;printf"\n%*s ",-i," ";}printf"%s ",w[j];}printf"\n";}'

test:
	echo ${PROJECT_NAME}
	echo ${VERSION}
	echo ${REPO_OWNER_NAME}
	echo ${REPO_OWNER_EMAIL}

check-wp:
	@type wp > /dev/null 2>&1 || { echo >&2 "Please install wp-cli (https://wp-cli.org/#installing). Aborting."; exit 1; }

check-gettext:
	@type gettext > /dev/null 2>&1 || { echo >&2 "Please install gettext. Aborting."; exit 1; }

.PHONY: install_phpcs
## Run linter WordPress
install_phpcs:
	@echo '**** install wpcs ****'
	composer install
	./vendor/bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs
	./vendor/bin/phpcs --config-set default_standard WordPress-Core

.PHONY: phpcs
## Run linter phpcs WordPress
phpcs: install_phpcs
	@echo '**** run phpcs ****'
	./vendor/bin/phpcs --standard=WordPress-Core epfl-coming-soon.php src/**/*.php

.PHONY: phpcbf
## Run linter phpcbf WordPress
phpcbf: install_phpcs phpcs
	@echo '**** phpcbf ****'
	./vendor/bin/phpcbf --standard=WordPress-Core epfl-coming-soon.php src/**/*.php

define JSON_HEADERS
{"Project-Id-Version": "EPFL Coming Soon $(VERSION)",\
"Last-Translator": "$(REPO_OWNER_NAME) <$(REPO_OWNER_EMAIL)>",\
"Language-Team": "EPFL IDEV-FSD <https://github.com/epfl-si/wp-plugin-$(PROJECT_NAME)>",\
"Report-Msgid-Bugs-To":"https://github.com/wp-cli/i18n-command/issues",\
"X-Domain": "$(PROJECT_NAME)"}
endef

.PHONY: pot
pot: check-wp check-gettext $(DOMAIN_PATH)/$(PROJECT_NAME).pot
	# 
	@wp i18n make-pot . $(DOMAIN_PATH)/$(PROJECT_NAME).pot --headers='$(JSON_HEADERS)'
	if [ -f $(DOMAIN_PATH)/$(PROJECT_NAME)-fr_FR.po ] ; then \
		sed -i.bak '/Project-Id-Version:/c "Project-Id-Version: WPForms EPFL Payonline $(VERSION)\\n"' $(DOMAIN_PATH)/$(PROJECT_NAME)-fr_FR.po; \
		msgmerge --update $(DOMAIN_PATH)/$(PROJECT_NAME)-fr_FR.po $(DOMAIN_PATH)/$(PROJECT_NAME).pot; \
	else \
		msginit --input=$(DOMAIN_PATH)/$(PROJECT_NAME).pot --locale=fr --output=$(DOMAIN_PATH)/$(PROJECT_NAME)-fr_FR.po; \
	fi
	msgfmt --output-file=$(DOMAIN_PATH)/$(PROJECT_NAME)-fr_FR.mo $(DOMAIN_PATH)/$(PROJECT_NAME)-fr_FR.po