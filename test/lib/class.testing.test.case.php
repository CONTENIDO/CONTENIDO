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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Assert;


/**
 * CONTENIDO test case class
 * @package          Testing
 * @subpackage       Helper
 */
abstract class cTestingTestCase extends TestCase {
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
     * @deprecated Since 4.10.2, unit tests will run under "test" environment, see constant CON_TEST_SQL_PREFIX
     */
    protected static $_originalSqlPrefix;

    /**
     * Sets the original database prefix
     * @param $sqlPrefix
     * @deprecated Since 4.10.2, unit tests will run under "test" environment, see constant CON_TEST_SQL_PREFIX
     */
    public static function setOriginalSqlPrefix($sqlPrefix) {
        self::$_originalSqlPrefix = $sqlPrefix;
    }

    /**
     * Creates a test suite.
     * @return TestSuite
     * @throws cTestingException
     * @deprecated Since 4.10.2, test suites are defined in the phpunit.xml
     */
    protected static function _createSuite() {
        if (self::$_testCaseName == '') {
            throw new cTestingException("No name provided for test case.");
        }

        return new TestSuite(self::$_testCaseName);
    }

    /**
     * Adds test files to the given test suite and returns it.
     * @param TestSuite $suite
     *
     * @return TestSuite
     * @deprecated Since 4.10.2, test files for test suites are defined in the phpunit.xml
     */
    protected static function _addTestFiles(TestSuite $suite) {
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
     * @see Assert::readAttribute
     * TODO We should not use Assert::readAttribute or Reflection to access protected/private attributes.
     *      Assert::readAttribute is deprecated, see also issues
     *      - https://github.com/sebastianbergmann/phpunit/issues/3338
     *      - https://github.com/sebastianbergmann/phpunit/issues/3339
     */
    protected function _readAttribute($object, $attributeName) {
//        return Assert::readAttribute($classOrObject, $attributeName);
        $reflector = new ReflectionObject($object);
        $property = $reflector->getProperty($attributeName);
        $property->setAccessible(true);

        return $property->getValue($object);

    }

    /**
     * @see Util::callProtectedMethod
     * TODO We should not use Reflection to access protected/private methods.
     *      Util::callProtectedMethod doesn't exist anymore, see also issues
     *      - https://github.com/sebastianbergmann/phpunit/issues/3338
     *      - https://github.com/sebastianbergmann/phpunit/issues/3339
     */
    protected function _callMethod($reflection, $obj, $methodName, $params) {
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $params);
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

        if ($cfg['sql']['sqlprefix'] !== CON_TEST_SQL_PREFIX) {
            throw new cTestingException('Current used database SQL prefix does not match the required test prefix - can not proceed.');
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

    /**
     * Creates the test tables for the test case. Adds the tables to the global $cfg['tab'] configuration
     * and ensures to remove leftover test tables from previous tests.
     * This function should be invoked within the setUp() function of the test case, and it requires
     * a defined $_tables property.
     *
     * @global $cfg
     * @throws cDbException
     * @throws cTestingException
     */
    protected function setUpTestCaseDbTables() {
        global $cfg; // don't use cRegistry!

        if (!isset($this->_tables) || !is_array($this->_tables)) {
            throw new cTestingException('No tables ($this->_tables) defined to set up for the test case');
        }

        // Update global tables configuration
        foreach ($this->_tables as $table) {
            $cfg['tab'][$table] = $table;
        }

        // Ensure to remove any leftover tables from previous tests, e.g. failed tests!
        $sql = SqlItemCollection::getDeleteStatement($this->_tables);
        cRegistry::getDb()->query($sql);
    }

    /**
     * Deletes the test tables for the test case. Removes tables from the global $cfg['tab'] configuration.
     * This function should be invoked within the tearDown() function of the test case, and it requires
     * a defined $_tables property.
     *
     * @global $cfg
     * @throws cDbException
     * @throws cTestingException
     */
    protected function tearDownTestCaseDbTables() {
        global $cfg; // don't use cRegistry!

        if (!isset($this->_tables) || !is_array($this->_tables)) {
            throw new cTestingException('No tables ($this->_tables) defined to tear down for the test case');
        }

        // Remove tables
        $sql = SqlItemCollection::getDeleteStatement($this->_tables);
        cRegistry::getDb()->query($sql);

        // Reset global tables configuration
        foreach ($this->_tables as $table) {
            unset($cfg['tab'][$table]);
        }
    }

}