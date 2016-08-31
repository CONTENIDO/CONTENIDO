/**
 * Readme
 *
 * @author  Murat Purc <murat@purc.de>
 * @date    31.12.2009
 */

Description
-----------

This folder (test) contains Contenido CMS related tests, created with PHPUnit.

Usage
-----

Install PHPUnit with pear installer. Type following command:

$ pear install PHPUnit

Open command line and go into folder {contenido_installation_path}/test/frontend/

Run UnitTests:

    - Run Contenido_Url test suite by typing following command:

      $ phpunit UrlTestSuite

    - Run chains test suite by typing following command:

      $ phpunit ChainsTestSuite

Write tests for new features and, if possible, for allready existing features.

Todo
----
Organize the tests

Misc
----

PHPUnit page:
http://www.phpunit.de/

PHPUnit documentation:
http://www.phpunit.de/wiki/Documentation

PHPUnit german manual:
http://www.phpunit.de/manual/2.3/de/

A short tutorial about PHPUnit:
http://pear.php.net/manual/en/package.php.phpunit.intro.php
