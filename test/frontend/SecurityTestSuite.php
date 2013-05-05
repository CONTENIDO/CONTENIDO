<?php
/**
 * Testsuite for security related tests.
 *
 * Call this from cmd-line as follows:
 * ...>phpunit SecurityTestSuite
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        20.05.2010
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Security
 */


require_once('bootstrap.php');
TestSuiteHelper::loadFeSuite('Security');


class ContenidoSecurityAllTest
{

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Contenido Security');
        $suite->addTestSuite('cSecurityTest');
        return $suite;
    }

}
