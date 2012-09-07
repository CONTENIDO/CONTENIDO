<?php
/**
 * Unittest for Contenido chain Contenido.Category.strSyncCategory_Loop
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
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
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
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
        CEC_Hook::execute($this->_chain, $this->_data);

        $this->assertEquals(array(0, $this->_data), array(self::$invokeCounter, $this->_data));
    }


    /**
     * Test Contenido.Category.strSyncCategory_Loop chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCECRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test');

        // execute chain
        CEC_Hook::execute($this->_chain, $this->_data);

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
        $cecReg = cApiCECRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test2');

        // execute chain
        CEC_Hook::execute($this->_chain, $this->_data);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoCategoryStrSyncCategory_Loop_Test2');

        $this->assertEquals(array(2, $this->_data), array(self::$invokeCounter, $this->_data));
    }

}
