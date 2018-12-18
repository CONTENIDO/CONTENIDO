<?php
/**
 * This file contains the test case class.
 *
 * @package          Testing
 * @subpackage       Helper
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
     * Original database prefix
     * @var
     */
    protected static $_originalSqlPrefix;

    /**
     * Sets the original database prefix
     * @param $sqlPrefix
     */
    public static function setOriginalSqlPrefix($sqlPrefix) {
        self::$_originalSqlPrefix = $sqlPrefix;
    }

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

    /**
     * @see PHPUnit_Framework_Assert::readAttribute
     */
    protected function _readAttribute($classOrObject, $attributeName) {
        return PHPUnit_Framework_Assert::readAttribute($classOrObject, $attributeName);
    }

    /**
     * Returns the content of the required SQL data file.
     * @param $databaseTable
     *
     * @return array
     * @throws cTestingException
     */
    protected function _fetchSqlFileContent($databaseTable) {
        $cfg = cRegistry::getConfig();

        $fileName = CON_TEST_PATH . '/sql/test_' . $databaseTable . '.sql';
        if (file_exists($fileName) === false) {
            throw new cTestingException('Can not load SQL data for this table - the source does not exist.');
        }

        if ($cfg['sql']['sqlprefix'] == self::$_originalSqlPrefix) {
            throw new cTestingException('Original database SQL prefix matches current installation prefix - can not proceed.');
        }

        $sqlStatements = array();
        $content = file($fileName);
        $lineBuffer = '';
        foreach ($content as $fileLine) {
            $lineBuffer .= str_replace('!PREFIX!', $cfg['sql']['sqlprefix'], $fileLine);

            if (cString::getPartOfString(trim($fileLine), -1) == ';') {
                $sqlStatements[] = $lineBuffer;
                $lineBuffer = '';
            }
        }

        if ($lineBuffer != '') {
            $sqlStatements[] = $lineBuffer;
        }

        return $sqlStatements;
    }
}