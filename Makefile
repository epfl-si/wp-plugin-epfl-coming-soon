# epfl-coming-soon Makefile
SHELL := /bin/bash
VERSION=$(shell ./change-version.sh -pv)

.PHONY: help
## Print this help (see <https://gist.github.com/klmr/575726c7e05d8780505a> for explanation)
help:
	@echo "$$(tput bold)Available rules (alphabetical order):$$(tput sgr0)";sed -ne"/^## /{h;s/.*//;:d" -e"H;n;s/^## //;td" -e"s/:.*//;G;s/\\n## /---/;s/\\n/ /g;p;}" ${MAKEFILE_LIST}|LC_ALL='C' sort -f |awk -F --- -v n=$$(tput cols) -v i=20 -v a="$$(tput setaf 6)" -v z="$$(tput sgr0)" '{printf"%s%*s%s ",a,-i,$$1,z;m=split($$2,w," ");l=n-i;for(j=1;j<=m;j++){l-=length(w[j])+1;if(l<= 0){l=n-i-length(w[j])-1;printf"\n%*s ",-i," ";}printf"%s ",w[j];}printf"\n";}'

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
