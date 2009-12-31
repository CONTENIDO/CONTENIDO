<?php
/**
 * Unittest for Contenido chain Contenido.Content.CreateTitletag
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
function chain_ContenidoContentCreateTitletag_Test()
{
    return 'Foobar';
}

/**
 * 2. chain function
 */
function chain_ContenidoContentCreateTitletag_Test2()
{
    return 'Lorem ipsum';
}


/**
 * Class to test Contenido chain Contenido.Content.CreateTitletag
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
 */
class ContenidoContentCreateTitletagTest extends PHPUnit_Framework_TestCase
{
    private $_chain = 'Contenido.Content.CreateTitletag';
    private $_title          = 'Da title';
    private $_titleOneChain  = 'Foobar';
    private $_titleTwoChains = 'Lorem ipsum';


    /**
     * Test Contenido.Content.CreateTitletag chain
     */
    public function testNoChain()
    {
        // set n' execute chain
        CEC_Hook::setDefaultReturnValue($this->_title);
        $newTitle = CEC_Hook::executeAndReturn($this->_chain);

        $this->assertEquals($this->_title, $newTitle);
    }


    /**
     * Test Contenido.Content.CreateTitletag chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCECRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test');

        // set n' execute chain
        CEC_Hook::setDefaultReturnValue($this->_title);
        $newTitle = CEC_Hook::executeAndReturn($this->_chain);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test');

        $this->assertEquals($this->_titleOneChain, $newTitle);
    }


    /**
     * Test Contenido.Content.CreateTitletag chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCECRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test2');

        // set n' execute chain
        CEC_Hook::setDefaultReturnValue($this->_title);
        $newTitle = CEC_Hook::executeAndReturn($this->_chain);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test2');

        $this->assertEquals($this->_titleTwoChains, $newTitle);
    }

}
