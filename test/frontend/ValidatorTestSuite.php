<?php
/**
 * This file contains the TestSuite for validators.
 *
 * @package Testing
 * @subpackage Test_Validator
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

require_once 'bootstrap.php';

TestSuiteHelper::loadFeSuite('Validator');

/**
 * Testsuite for validator related tests.
 *
 * Call this from cmd-line as follows:
 * ...>phpunit ValidatorTestSuite
 *
 * @package Testing
 * @subpackage Test_Validator
 */
class ContenidoValidatorAllTest {

    /**
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('CONTENIDO Validator');
        $suite->addTestSuite('cValidatorEmailTest');
        return $suite;
    }

}
