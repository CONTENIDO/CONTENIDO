<?php
/**
 * Template TestSuite
 *
 * @package Testing
 * @subpackage Test_Security
 * @version SVN Revision $Rev:$
 *
 * @author claus schunk <claus.schunk@4fb.de>
 *         based on the test suite from Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txtnotwendig
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
require_once(dirname(dirname(__FILE__)) . '/bootstrap.php');
// foldername of the test
require_once(dirname(__FILE__) . '/Template/template.php');

/**
 * Template Testsuite.
 */
class ContenidoSecurityAllTest {

    /**
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('Template');
        // class name of the test
        $suite->addTestSuite('TemplateUnitTest');
        return $suite;
    }

}
