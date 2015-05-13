<?php

/**
 * Template TestSuite
 *
 * @package Testing
 * @subpackage Test_Security
 * @version SVN Revision $Rev:$
 *
 * @author claus schunk <claus.schunk@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

require_once 'bootstrap.php';

// foldername of the test
TestSuiteHelper::loadFeSuite('classes');

require_once 'util.php';

/**
 * Template Testsuite.
 */
class ContenidoClassesAllTest {

    /**
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('classes test');
        // class name of the test
        $suite->addTestSuite('cArticleCollectorTest');
        // $suite->addTestSuite('');

        return $suite;
    }

}
