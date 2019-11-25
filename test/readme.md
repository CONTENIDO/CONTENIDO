# CONTENIDO CMS Unit Tests

## Description

This folder (test) contains CONTENIDO CMS related tests, created with PHPUnit.

Unit tests are configured in the PHPUnit XML configuration file (`phpunit.xml`) in the CONTENIDO installation folder.

----

## Prerequisites

### CONTENIDO

Unit tests rely on an existing CONTENIDO installation. Setup your web server and install CONTENIDO.

### PHPUnit

Install PHPUnit with composer, run following command, if not done before:
```
$ composer install
```

### Test Database

Unit tests rely on a bootstrapped CONTENIDO application, and this is only possible with an existing database. We need to setup a database to be usable by the unit tests. 

#### Database (optional)

The easiest way to achieve this is to copy your existing database, e. g. `contenido` to `contenido_test`. The unit test database should have the suffix `_test` which identifies the purpose of the database. Using a unit test database is not mandatory but highly recommended. You can use also one database together for development and for unit tests.

#### Database Tables

All tables of the unit test database should have the prefix `test` (e. g. `test_actionlog`, `test_cat`, `test_art`, etc.) to identify them only for usage in unit tests. If you want to use one database for development and unit tests, then you should copy all your existing tables with the prefix `test`. If you have a separate database for your unit test, then ensure that all your tables also have the prefix `test`.


### Test Environment

Unit tests should run in a specific "test" environment, therefore "test" environment related configuration files in folder `cms/data/config/test` and `data/config/test` are needed. Do following steps to prepare the environment for unit tests in case the configuration folders are missing:

1. Copy folder `cms/data/config/{environment}` with its content to `cms/data/config/test`

2. Copy folder `data/config/{environment}` with its content to `data/config/test`
    - Plugins should be disabled, they may affect behaviour of unit tests. Open `data/config/test/config.misc.php` and disable plugins (`$cfg['debug']['disable_plugins'] = true;`).
    - Unit tests should use separate database and/or tables. Unit tests require tables with the prefix `test`. Change sql prefix in `data/config/test/config.php` to `$cfg['sql']['sqlprefix'] = 'test';`. In case to use a separate database for unit tests, adapt your database connection settings in `data/config/test/config.php`.

----

## Usage

Open command line and navigate to the CONTENIDO installation folder

### Run Unit Tests

Run "CONTENIDO classes" test suite by typing following command:
```
$ ./vendor/bin/phpunit --configuration phpunit.xml --testsuite contenido_classes
```

Run "Frontend chains" test suite by typing following command:
```
$ ./vendor/bin/phpunit --configuration phpunit.xml --testsuite frontend_chains
```

You can run any test suite defined in phpunit.xml by the test suite name
```
$ ./vendor/bin/phpunit --configuration phpunit.xml --testsuite {test_suite_name}
```

Run Url tests by typing following command
```
$ ./vendor/bin/phpunit --configuration phpunit.xml test/frontend/Url/Contenido_Url.php
```

Run any unit test file by typing following command
```
$ ./vendor/bin/phpunit --configuration phpunit.xml {path_to_unit_test_file}
```

**NOTE:**
If you work with the Windows command line, then use the Windows path separator for the path to the phpunit batch script, e. g.
```
$ .\vendor\bin\phpunit --configuration phpunit.xml {path_to_unit_test_file}
```

----

## Write Unit Tests

Write unit tests for new features and, if possible, for already existing features.

----

## Links

PHPUnit page:
https://www.phpunit.de/

PHPUnit documentation:
https://phpunit.de/documentation.html

PHPUnit Manual:
https://phpunit.readthedocs.io/en/8.4/
