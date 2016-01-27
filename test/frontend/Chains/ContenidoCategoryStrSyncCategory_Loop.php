<?php
/**
 * This file contains tests for Contenido chain Contenido.Category.strSyncCategory_Loop
 *
 * @package          Testing
 * @subpackage       Test_Chains
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

/**
 * 1. chain function
 */
function chain_ContenidoCategoryStrSyncCategory_Loop_Test(array $data)
{
    if (isset($data['idcat']) && $data['idcat'] == 23) {
        ContenidoCategoryStrSyncCategory_LoopTest::$invokeCounter++;
    }
}

/**
 * 2. chain function
 */
function chain_ContenidoCategoryStrSyncCategory_Loop_Test2(array $data)
{
    if (isset($data['idcat']) && $data['idcat'] == 23) {
        ContenidoCategoryStrSyncCategory_LoopTest::$invokeCounter++;
    }
}


/**
 * Class to test Contenido chain Contenido.Category.strSyncCategory_Loop.
 * @package          Testing
 * @subpackage       Test_Chains
 */
class ContenidoCategoryStrSyncCategory_LoopTest extends PHPUnit_Framework_TestCase
{
    private $_chain = 'Contenido.Category.strSyncCategory_Loop';
    private $_data = array('idcat' => 23, 'idlang' => 2, 'idtplcfg' => 2314, 'visible' => 1);

    public static $invokeCounter = 0;


    protected function setUp()
    {
        self::$invokeCounter = 0;
    }


    /**
     * Test Contenido.Category.strSyncCategory_Loop chain
     */
    public function testNoChain()
    {
        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        $this->assertEquals(array(0, $this->_data), array(self::$invokeCounter, $this->_data));
    }


    /**
     * Test Contenido.Category.strSyncCategory_Loop chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test');

        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test');

        $this->assertEquals(array(1, $this->_data), array(self::$invokeCounter, $this->_data));
    }


    /**
     * Test Contenido.Category.strSyncCategory_Loop chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test2');

        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test2');

        $this->assertEquals(array(2, $this->_data), array(self::$invokeCounter, $this->_data));
    }

}
