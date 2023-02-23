<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Contenido.Frontend.PostprocessUrlBuilding
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
 * 1. chain function
 */
function chain_ContenidoFrontendPostprocessUrlBuilding_Test($url)
{
    return $url .= '&foo=bar';
}

/**
 * 2. chain function
 */
function chain_ContenidoFrontendPostprocessUrlBuilding_Test2($url)
{
    return $url .= '&myid=asdf';
}


/**
 * Class to test Contenido chain Contenido.Frontend.PostprocessUrlBuilding.
 * @package    Testing
 * @subpackage Test_Chains
 */
class ContenidoFrontendPostprocessUrlBuildingTest extends TestCase
{
    private $_chain = 'Contenido.Frontend.PostprocessUrlBuilding';
    private $_url          = '/front_content.php?idart=123';
    private $_urlOneChain  = '/front_content.php?idart=123&foo=bar';
    private $_urlTwoChains = '/front_content.php?idart=123&foo=bar&myid=asdf';


    /**
     * Test Contenido.Frontend.PostprocessUrlBuilding chain
     */
    public function testNoChain()
    {
        // execute chain
        $newUrl = cApiCecHook::executeAndReturn($this->_chain, $this->_url);

        $this->assertEquals($this->_url, $newUrl);
    }


    /**
     * Test Contenido.Frontend.PostprocessUrlBuilding chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test');

        // execute chain
        $newUrl = cApiCecHook::executeAndReturn($this->_chain, $this->_url);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test');

        $this->assertEquals($this->_urlOneChain, $newUrl);
    }


    /**
     * Test Contenido.Frontend.PostprocessUrlBuilding chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test2');

        // execute chain
        $newUrl = cApiCecHook::executeAndReturn($this->_chain, $this->_url);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test2');

        $this->assertEquals($this->_urlTwoChains, $newUrl);
    }

}
