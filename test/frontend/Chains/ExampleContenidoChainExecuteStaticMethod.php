<?php
/**
 * Unittest for Contenido chain Example.Contenido.Chain.ExecuteStaticMethod
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
class chain_ExampleContenidoChainExecuteStaticMethod_Test
{
    public static function callMe($obj)
    {
        $obj->counter++;
    }
}

/**
 * 2. chain object
 */
class chain_ExampleContenidoChainExecuteStaticMethod_Test2
{
    public static function callMe($obj)
    {
        $obj->counter++;
    }
}


/**
 * Class to test Contenido chain Example.Contenido.Chain.ExecuteStaticMethod.
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
 */
class ExampleContenidoChainExecuteStaticMethodTest extends PHPUnit_Framework_TestCase
{
    private $_chain = 'Example.Contenido.Chain.ExecuteStaticMethod';

    private $_obj;


    protected function setUp()
    {
        $this->_obj = new stdClass();
        $this->_obj->counter = 0;
        cApiCECRegistry::getInstance()->registerChain($this->_chain, 'object');
    }


    protected function tearDown()
    {
        cApiCECRegistry::getInstance()->unregisterChain($this->_chain);
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
        $cecReg = cApiCECRegistry::getInstance();

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
        $cecReg = cApiCECRegistry::getInstance();

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
