<?php
/**
 * This file contains the TestSuite for validators.
 *
 * @package          Testing
 * @subpackage       Test_Validator
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

require_once(dirname(dirname(__FILE__)) . '/bootstrap.php');
require_once(dirname(__FILE__) . '/Validator/cValidatorEmail.php');

/**
 * Testsuite for validator related tests.
 *
 * Call this from cmd-line as follows:
 * ...>phpunit ValidatorTestSuite
 *
 * @package          Testing
 * @subpackage       Test_Validator
 */
class ContenidoValidatorAllTest
{

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('CONTENIDO Validator');
        $suite->addTestSuite('cValidatorEmailTest');
        return $suite;
    }

}
