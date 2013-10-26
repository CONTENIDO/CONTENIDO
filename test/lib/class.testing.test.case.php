<?php
/**
 * This file contains the test case class.
 *
 * @package          Testing
 * @subpackage       Helper
 * @version          SVN Revision $Rev:$
 *
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

/**
 * CONTENIDO test case class
 * @package          Testing
 * @subpackage       Helper
 */
abstract class cTestingTestCase extends PHPUnit_Framework_TestCase {
    /**
     * Name of the test case
     * @var string
     */
    protected static $_testCaseName = '';

    /**
     * Array with directories to test
     * @var array
     */
    protected static $_testDirectories = array();

    /**
     * Creates a test suite.
     * @return PHPUnit_Framework_TestSuite
     * @throws cTestingException
     */
    protected static function _createSuite() {
        if (self::$_testCaseName == '') {
            throw new cTestingException("No name provided for test case.");
        }

        return new PHPUnit_Framework_TestSuite(self::$_testCaseName);
    }

    /**
     * Adds test files to the given test suite and returns it.
     * @param PHPUnit_Framework_TestSuite $suite
     *
     * @return PHPUnit_Framework_TestSuite
     */
    protected static function _addTestFiles(PHPUnit_Framework_TestSuite $suite) {
        if (count(self::$_testDirectories) == 0) {
            throw new cTestingException("No directories specified for test case.");
        }

        foreach (self::$_testDirectories as $directory) {
            $dir = new DirectoryIterator($directory);
            foreach ($dir as $file) {
                /** @var $file SplFileInfo */
                if ($file->isFile() && $file->getExtension() == 'php') {
                    $suite->addTestFile($directory . '/' . $file->getFilename());
                }
            }
        }

        return $suite;
    }
}