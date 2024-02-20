<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain
 * Example.Contenido.Chain.ExecuteStaticMethod
 *
 * @package    Testing
 * @subpackage Test_Chains
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * 1.
 * chain object
 *
 * @package    Testing
 * @subpackage Test_Chains
 */
class chain_ExampleContenidoChainExecuteStaticMethod_Test
{

    /**
     *
     * @param object $obj
     */
    public static function callMe($obj)
    {
        $obj->counter++;
    }
}

/**
 * 2.
 * chain object
 *
 * @package    Testing
 * @subpackage Test_Chains
 */
class chain_ExampleContenidoChainExecuteStaticMethod_Test2
{

    /**
     * @param stdClass $obj
     */
    public static function callMe($obj)
    {
        $obj->counter++;
    }
}

/**
 * Class to test Contenido chain Example.Contenido.Chain.ExecuteStaticMethod.
 *
 * @package    Testing
 * @subpackage Test_Chains
 */
class ExampleContenidoChainExecuteStaticMethodTest extends TestCase
{

    /**
     * @var string
     */
    private $_chain = 'Example.Contenido.Chain.ExecuteStaticMethod';

    /**
     * @var stdClass
     */
    private $_obj;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->_obj = new stdClass();
        $this->_obj->counter = 0;
    }

    /**
     * Test Example.Contenido.Chain.ExecuteStaticMethod chain
     */
    public function testNoChain()
    {
        // execute chain
        cApiCecHook::execute($this->_chain, $this->_obj);

        $this->assertEquals(0, $this->_obj->counter);
    }

    /**
     * Test Example.Contenido.Chain.ExecuteStaticMethod chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteStaticMethod_Test::callMe');

        // execute chain
        cApiCecHook::execute($this->_chain, $this->_obj);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteStaticMethod_Test::callMe');

        $this->assertEquals(1, $this->_obj->counter);
    }

    /**
     * Test Example.Contenido.Chain.ExecuteStaticMethod chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteStaticMethod_Test::callMe');
        $cecReg->addChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteStaticMethod_Test2::callMe');

        // execute chain
        cApiCecHook::execute($this->_chain, $this->_obj);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteStaticMethod_Test::callMe');
        $cecReg->removeChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteStaticMethod_Test2::callMe');

        $this->assertEquals(2, $this->_obj->counter);
    }

}
