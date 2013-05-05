<?php
/**
 * Unittest for class cSecurity
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        20.05.2010
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Security
 */


/**
 * Class to check cSecurity class
 *
 * @todo Implement more tests
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        20.05.2010
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Security
 */
class cSecurityTest extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $_REQUEST = array();
    }

    protected function tearDown()
    {
        $_REQUEST = array();
    }

    /**
     * Test boolean check
     */
    public function testIsBoolean()
    {
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

}
