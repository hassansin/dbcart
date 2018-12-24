RUNNER=docker run -it --rm --workdir "/src" -v "$(PWD):/src" -v "$(HOME)/.composer/cache:/root/.composer/cache" dbcartphp /bin/bash -c

.PHONY: build composer php test

build:
	@docker build --build-arg VERSION=7.2 --tag=dbcartphp .
composer:
	@$(RUNNER) "composer $(filter-out $@,$(MAKECMDGOALS))"
dependencies:
	make -s composer update -- --prefer-dist
test:
	$(RUNNER) "phpunit --coverage-text --coverage-html ./coverage $(filter-out $@,$(MAKECMDGOALS))"
php:
	$(RUNNER) "php $(filter-out $@,$(MAKECMDGOALS))"
%:
	@:
