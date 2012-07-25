<?php
/**
 * Testsuite for validator related tests.
 *
 * Call this from cmd-line as follows:
 * ...>phpunit ValidatorTestSuite
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        20.10.2011
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  TestSuite
 */


require_once('bootstrap.php');
TestSuiteHelper::loadFeSuite('Validator');


class ContenidoValidatorAllTest
{

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('CONTENIDO Validator');
        $suite->addTestSuite('cValidatorEmailTest');
        return $suite;
    }

}
