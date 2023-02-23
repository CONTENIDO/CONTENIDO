<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Contenido.Content.CreateTitletag
 *
 * @package          Testing
 * @subpackage       Test_Chains
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
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
 * @package          Testing
 * @subpackage       Test_Chains
 */
class ContenidoContentCreateTitletagTest extends TestCase
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
        cApiCecHook::setDefaultReturnValue($this->_title);
        $newTitle = cApiCecHook::executeAndReturn($this->_chain);

        $this->assertEquals($this->_title, $newTitle);
    }


    /**
     * Test Contenido.Content.CreateTitletag chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test');

        // set n' execute chain
        cApiCecHook::setDefaultReturnValue($this->_title);
        $newTitle = cApiCecHook::executeAndReturn($this->_chain);

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
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test2');

        // set n' execute chain
        cApiCecHook::setDefaultReturnValue($this->_title);
        $newTitle = cApiCecHook::executeAndReturn($this->_chain);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentCreateTitletag_Test2');

        $this->assertEquals($this->_titleTwoChains, $newTitle);
    }

}
