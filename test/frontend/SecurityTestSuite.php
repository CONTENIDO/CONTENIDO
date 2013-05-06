<?php
/**
 * This file contains the TestSuite for security.
 *
 * @package          Testing
 * @subpackage       Test_Security
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

require_once('bootstrap.php');
TestSuiteHelper::loadFeSuite('Security');

/**
 * Testsuite for security related tests.
 *
 * Call this from cmd-line as follows:
 * ...>phpunit SecurityTestSuite
 *
 * @package          Testing
 * @subpackage       Test_Security
 */
class ContenidoSecurityAllTest
{

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Contenido Security');
        $suite->addTestSuite('cSecurityTest');
        return $suite;
    }

}
