<?php
/**
 * Unittest for Contenido chain Contenido.Frontend.BaseHrefGeneration
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
 */


/**
 * 1. chain function to generate base href for frontend
 */
function chain_ContenidoFrontendBaseHrefGeneration_Test($baseHref)
{
    return $baseHref . 'foo/';
}

/**
 * 2. chain function to generate base href for frontend
 */
function chain_ContenidoFrontendBaseHrefGeneration_Test2($baseHref)
{
    return $baseHref . 'bar/';
}


/**
 * Class to test Contenido chain Contenido.Frontend.BaseHrefGeneration
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
 */
class ContenidoFrontendBaseHrefGenerationTest extends PHPUnit_Framework_TestCase
{
    private $_chain = 'Contenido.Frontend.BaseHrefGeneration';
    private $_baseHref = null;


    protected function setUp()
    {
        $this->_baseHref = $GLOBALS['cfgClient'][$GLOBALS['client']]['path']['htmlpath'];
    }


    /**
     * Test Contenido.Frontend.BaseHrefGeneration chain
     */
    public function testNoChain()
    {
        // execute chain
        $newBaseHref = CEC_Hook::executeAndReturn($this->_chain, $this->_baseHref);

        $this->assertEquals($this->_baseHref, $newBaseHref);
    }


    /**
     * Test Contenido.Frontend.BaseHrefGeneration chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCECRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test');

        // execute chain
        $newBaseHref = CEC_Hook::executeAndReturn($this->_chain, $this->_baseHref);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test');

        $this->assertEquals($this->_baseHref . 'foo/', $newBaseHref);
    }


    /**
     * Test Contenido.Frontend.BaseHrefGeneration chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCECRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test2');

        // execute chain
        $newBaseHref = CEC_Hook::executeAndReturn($this->_chain, $this->_baseHref);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test2');

        $this->assertEquals($this->_baseHref . 'foo/bar/', $newBaseHref);
    }

}
