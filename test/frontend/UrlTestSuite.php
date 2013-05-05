<?php
/**
 * Testsuite for Contenido_Url related tests.
 *
 * Call this from cmd-line as follows:
 * ...>phpunit UrlTestSuite
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        26.12.2008
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  TestSuite
 */


require_once('bootstrap.php');
TestSuiteHelper::loadFeSuite('Url');


class ContenidoUrlAllTest
{

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Contenido Url');
        $suite->addTestSuite('cUriTest');
        return $suite;
    }

}
