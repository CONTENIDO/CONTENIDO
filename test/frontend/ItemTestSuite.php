<?php

/**
 * Template TestSuite
 *
 * @package Testing
 * @subpackage Test_Security
 * @version SVN Revision $Rev:$
 *
 * @author claus schunk <claus.schunk@4fb.de>
 *         based on the unittests from Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

require_once 'bootstrap.php';

// foldername of the test
TestSuiteHelper::loadFeSuite('Item');

/**
 * Template Testsuite.
 */
class ContenidoSecurityAllTest {

    /**
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('Item xTests');
        // class name of the test
        $suite->addTestSuite('ItemTest');
        return $suite;
    }

}
