<?php
/**
 * This file contains tests for Contenido chain Example.Contenido.Chain.ExecuteStaticMethod
 *
 * @package          Testing
 * @subpackage       Test_Chains
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

/**
 * 1. chain object
 * @package          Testing
 * @subpackage       Test_Chains
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
 * @package          Testing
 * @subpackage       Test_Chains
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
 * @package          Testing
 * @subpackage       Test_Chains
 */
class ExampleContenidoChainExecuteStaticMethodTest extends PHPUnit_Framework_TestCase
{
    private $_chain = 'Example.Contenido.Chain.ExecuteStaticMethod';

    private $_obj;


    protected function setUp()
    {
        $this->_obj = new stdClass();
        $this->_obj->counter = 0;
    }


    /**
	* @deprecated 2014-08-07 - This method is deprecated and is not needed any longer
	 */
    public function tearDown() {
        cDeprecated('This method is deprecated and is not needed any longer');
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
