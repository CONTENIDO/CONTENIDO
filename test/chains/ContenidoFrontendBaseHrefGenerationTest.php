<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Contenido.Frontend.BaseHrefGeneration
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
 * @package    Testing
 * @subpackage Test_Chains
 */
class ContenidoFrontendBaseHrefGenerationTest extends TestCase
{
    private $_chain = 'Contenido.Frontend.BaseHrefGeneration';
    private $_baseHref = null;


    protected function setUp(): void
    {
        $cfgClient = cRegistry::getClientConfig(cRegistry::getClientId());
        $this->_baseHref = $cfgClient['path']['htmlpath'];
    }


    /**
     * Test Contenido.Frontend.BaseHrefGeneration chain
     */
    public function testNoChain()
    {
        // execute chain
        $newBaseHref = cApiCecHook::executeAndReturn($this->_chain, $this->_baseHref);

        $this->assertEquals($this->_baseHref, $newBaseHref);
    }


    /**
     * Test Contenido.Frontend.BaseHrefGeneration chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test');

        // execute chain
        $newBaseHref = cApiCecHook::executeAndReturn($this->_chain, $this->_baseHref);

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
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test2');

        // execute chain
        $newBaseHref = cApiCecHook::executeAndReturn($this->_chain, $this->_baseHref);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendBaseHrefGeneration_Test2');

        $this->assertEquals($this->_baseHref . 'foo/bar/', $newBaseHref);
    }

}
