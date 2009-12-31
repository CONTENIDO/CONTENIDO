<?php
/**
 * Unittest for Contenido chain Example.Contenido.Chain.ExecuteObject
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
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
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
 */
class ExampleContenidoChainExecuteObjectTest extends PHPUnit_Framework_TestCase
{
    private $_chain = 'Example.Contenido.Chain.ExecuteObject';

    public static $invokeCounter = 0;


    protected function setUp()
    {
        self::$invokeCounter = 0;
        cApiCECRegistry::getInstance()->registerChain($this->_chain);
    }


    protected function tearDown()
    {
        cApiCECRegistry::getInstance()->unregisterChain($this->_chain);
    }


    /**
     * Test Example.Contenido.Chain.ExecuteObject chain
     */
    public function testNoChain()
    {
        // execute chain
        CEC_Hook::execute($this->_chain);

        $this->assertEquals(0, self::$invokeCounter);
    }


    /**
     * Test Example.Contenido.Chain.ExecuteObject chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCECRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test->callMe');

        // execute chain
        CEC_Hook::execute($this->_chain);

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
        $cecReg = cApiCECRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test->callMe');
        $cecReg->addChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test2->callMe');

        // execute chain
        CEC_Hook::execute($this->_chain);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test->callMe');
        $cecReg->removeChainFunction($this->_chain, 'chain_ExampleContenidoChainExecuteObject_Test2->callMe');

        $this->assertEquals(2, self::$invokeCounter);
    }

}
