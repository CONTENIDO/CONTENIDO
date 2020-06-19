<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Contenido.Frontend.HTMLCodeOutput
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
 * 1. chain function to modify html code output
 */
function chain_ContenidoFrontendHTMLCodeOutput_Test($html)
{
    return str_replace('<title>', '<title>new ', $html);
}

/**
 * 2. chain function to modify html code output
 */
function chain_ContenidoFrontendHTMLCodeOutput_Test2($html)
{
    return str_replace('<body>', '<body>new ', $html);
}


/**
 * Class to test Contenido chain Contenido.Frontend.HTMLCodeOutput
 * @package          Testing
 * @subpackage       Test_Chains
 */
class ContenidoFrontendHTMLCodeOutputTest extends TestCase
{
    private $_chain = 'Contenido.Frontend.HTMLCodeOutput';
    private $_html  = '<html><head><title>test</title><body>content</body></html>';
    private $_htmlOneChain  = '<html><head><title>new test</title><body>content</body></html>';
    private $_htmlTwoChains = '<html><head><title>new test</title><body>new content</body></html>';


    /**
     * Test Contenido.Frontend.HTMLCodeOutput chain
     */
    public function testNoChain()
    {
        // execute chain
        $newHtml = cApiCecHook::executeAndReturn($this->_chain, $this->_html);

        $this->assertEquals($this->_html, $newHtml);
    }


    /**
     * Test Contenido.Frontend.HTMLCodeOutput chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendHTMLCodeOutput_Test');

        // execute chain
        $newHtml = cApiCecHook::executeAndReturn($this->_chain, $this->_html);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendHTMLCodeOutput_Test');

        $this->assertEquals($this->_htmlOneChain, $newHtml);
    }


    /**
     * Test Contenido.Frontend.HTMLCodeOutput chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendHTMLCodeOutput_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendHTMLCodeOutput_Test2');

        // execute chain
        $newHtml = cApiCecHook::executeAndReturn($this->_chain, $this->_html);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendHTMLCodeOutput_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendHTMLCodeOutput_Test2');

        $this->assertEquals($this->_htmlTwoChains, $newHtml);
    }

}
