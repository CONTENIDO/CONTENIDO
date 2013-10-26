<?php
/**
 * This file contains the class for testing all test cases.
 *
 * @package          Testing
 * @subpackage       TestCase
 * @version          SVN Revision $Rev:$
 *
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

error_reporting((E_ALL ^ E_NOTICE) | E_STRICT);
ini_set('display_errors', true);

/**
 * This class tests all test cases.
 * @package          Testing
 * @subpackage       TestCase
 */
class cAllTestCase extends cTestingTestCase {
    /**
     * Create test suite for all tests.
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite() {
        parent::$_testCaseName = 'CONTENIDO All Unit Tests';
        parent::$_testDirectories = array();

        try {
            // We can not use the normal logic to search for sub folders, so we must add the main suites manually.
            $suite = parent::_createSuite();
            $suite->addTestFile(CON_TEST_PATH . '/class.contenido.test.case.php');
            return $suite;
        } catch (cTestingException $ex) {
            die("Can not fetch test case: " . $ex->getMessage());
        }
    }
}