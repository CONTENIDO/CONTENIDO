<?php
/**
 * This file contains tests for the cSecurity class.
 *
 * @package          Testing
 * @subpackage       Test_Security
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

/**
 * Class to check cSecurity class
 *
 * @todo             Implement more tests
 *
 * @package          Testing
 * @subpackage       Test_Security
 */
class cSecurityTest extends cTestingTestCase
{
    protected function setUp(): void
    {
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        $_REQUEST = [];
    }

    /**
     * @TODO Implement this test.
     */
    public function testCheckRequests()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @TODO Implement this test.
     */
    public function testFilter()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @TODO Implement this test.
     */
    public function testUnFilter()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test boolean check
     */
    public function testIsBoolean()
    {
        $this->assertEquals(false, cSecurity::isBoolean(1));
        $this->assertEquals(false, cSecurity::isBoolean(''));
        $this->assertEquals(false, cSecurity::isBoolean(null));
        $this->assertEquals(true, cSecurity::isBoolean(false));
        $this->assertEquals(true, cSecurity::isBoolean(true));
    }

    /**
     * Test integer check
     */
    public function testIsInteger()
    {
        $this->assertEquals(false, cSecurity::isInteger(''));
        $this->assertEquals(false, cSecurity::isInteger(null));
        $this->assertEquals(true, cSecurity::isInteger(123));
        $this->assertEquals(true, cSecurity::isInteger('123'));
    }

    /**
     * @TODO Implement this test.
     */
    public function testIsString()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @TODO Implement this test.
     */
    public function testToBoolean()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @TODO Implement this test.
     */
    public function testToInteger()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @TODO Implement this test.
     */
    public function testToString()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @TODO Implement this test.
     */
    public function testEscapeDb()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @TODO Implement this test.
     */
    public function testEscapeString()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @TODO Implement this test.
     */
    public function testUnescapeDb()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }
}