DEFAULT:
	find src -exec php -l {} \;
	composer dumpautoload

test:
	vendor/bin/codecept run --debug -vvv unit $(TEST)

