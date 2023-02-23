<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Example.Contenido.Chain.ExecuteObject
 *
 * @package          Testing
 * @subpackage       Test_Chains
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

/**
 * 1. chain object
 */
class chain_ExampleContenidoChainExecuteObject_Test
{
    public function callMe()
    {
        ExampleContenidoChainExecuteObjectTest::$invokeCounter++;
    }
}

/**
 * 2. chain object
 */
class chain_ExampleContenidoChainExecuteObject_Test2
{
    public function callMe()
    {
        ExampleContenidoChainExecuteObjectTest::$invokeCounter++;
    }
}


/**
 * Class to test Contenido chain Example.Contenido.Chain.ExecuteObject.
 * @package          Testing
 * @subpackage       Test_Chains
 */
class ExampleContenidoChainExecuteObjectTest extends TestCase
{
    private $_chain = 'Example.Contenido.Chain.ExecuteObject';

    public static $invokeCounter = 0;


    protected function setUp(): void
    {
        self::$invokeCounter = 0;
    }


    /**
     * Test Example.Contenido.Chain.ExecuteObject chain
     */
    public function testNoChain()
    {
        // execute chain
        cApiCecHook::execute($this->_chain);

        $this->assertEquals(0, self::$invokeCounter);
    }


    /**
     * Test Example.Contenido.Chain.ExecuteObject chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test->callMe');

        // execute chain
        cApiCecHook::execute($this->_chain);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test->callMe');

        $this->assertEquals(1, self::$invokeCounter);
    }


    /**
     * Test Example.Contenido.Chain.ExecuteObject chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test->callMe');
        $cecReg->addChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test2->callMe');

        // execute chain
        cApiCecHook::execute($this->_chain);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test->callMe');
        $cecReg->removeChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test2->callMe');

        $this->assertEquals(2, self::$invokeCounter);
    }

}
