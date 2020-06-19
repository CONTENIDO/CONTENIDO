<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Contenido.Frontend.PreprocessUrlBuilding
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
function chain_ContenidoFrontendPreprocessUrlBuilding_Test(array $param)
{
    $param['param']['foo'] = 'bar';
    return $param;
}

/**
 * 2. chain function
 */
function chain_ContenidoFrontendPreprocessUrlBuilding_Test2(array $param)
{
    $param['param']['lang'] = 'en';
    return $param;
}


/**
 * Class to test Contenido chain Contenido.Frontend.PreprocessUrlBuilding.
 * @package          Testing
 * @subpackage       Test_Chains
 */
class ContenidoFrontendPreprocessUrlBuildingTest extends TestCase
{
    private $_chain = 'Contenido.Frontend.PreprocessUrlBuilding';
    private $_params;
    private $_paramsOneChain;
    private $_paramsTwoChains;


    protected function setUp(): void
    {
        $param = array('idart' => 123, 'lang' => 2, 'client' => 1);
        $this->_params = array(
            'param' => $param, 'bUseAbsolutePath' => true, 'aConfig' => array()
        );

        $this->_paramsOneChain = $this->_params;
        $this->_paramsOneChain['param']['foo'] = 'bar';

        $this->_paramsTwoChains = $this->_paramsOneChain;
        $this->_paramsTwoChains['param']['lang'] = 'en';
    }


    /**
     * Test Contenido.Frontend.PreprocessUrlBuilding chain
     */
    public function testNoChain()
    {
        // execute chain
        $result = cApiCecHook::executeAndReturn($this->_chain, $this->_params);

        $this->assertEquals($this->_params, $result);
    }


    /**
     * Test Contenido.Frontend.PreprocessUrlBuilding chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendPreprocessUrlBuilding_Test');

        // execute chain
        $result = cApiCecHook::executeAndReturn($this->_chain, $this->_params);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendPreprocessUrlBuilding_Test');

        $this->assertEquals($this->_paramsOneChain, $result);
    }


    /**
     * Test Contenido.Frontend.PreprocessUrlBuilding chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendPreprocessUrlBuilding_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendPreprocessUrlBuilding_Test2');

        // execute chain
        $result = cApiCecHook::executeAndReturn($this->_chain, $this->_params);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendPreprocessUrlBuilding_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendPreprocessUrlBuilding_Test2');

        $this->assertEquals($this->_paramsTwoChains, $result);
    }

}
