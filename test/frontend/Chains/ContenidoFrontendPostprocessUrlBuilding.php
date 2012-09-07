<?php
/**
 * Unittest for Contenido chain Contenido.Frontend.PostprocessUrlBuilding
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
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
 */
class ContenidoFrontendPostprocessUrlBuildingTest extends PHPUnit_Framework_TestCase
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
        $newUrl = CEC_Hook::executeAndReturn($this->_chain, $this->_url);

        $this->assertEquals($this->_url, $newUrl);
    }


    /**
     * Test Contenido.Frontend.PostprocessUrlBuilding chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCECRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test');

        // execute chain
        $newUrl = CEC_Hook::executeAndReturn($this->_chain, $this->_url);

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
        $cecReg = cApiCECRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test2');

        // execute chain
        $newUrl = CEC_Hook::executeAndReturn($this->_chain, $this->_url);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendPostprocessUrlBuilding_Test2');

        $this->assertEquals($this->_urlTwoChains, $newUrl);
    }

}
