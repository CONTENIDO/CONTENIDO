<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Contenido.Content.conGenerateCode
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
 * 1. chain function to modify html code output
 */
function chain_ContenidoContentConGenerateCode_Test($html)
{
    return str_replace('<title>test</title>', '<title><?php echo $title ?></title>', $html);
}

/**
 * 2. chain function to modify html code output
 */
function chain_ContenidoContentConGenerateCode_Test2($html)
{
    return str_replace('<body>content</body>', '<body><?php echo $body ?></body>', $html);
}


/**
 * Class to test Contenido chain Contenido.Content.conGenerateCode
 * @package    Testing
 * @subpackage Test_Chains
 */
class ContenidoContentConGenerateCodeTest extends TestCase
{
    private $_chain = 'Contenido.Content.conGenerateCode';
    private $_code          = '<html lang="en"><head><title>test</title><body>content</body></html>';
    private $_codeOneChain  = '<html lang="en"><head><title><?php echo $title ?></title><body>content</body></html>';
    private $_codeTwoChains = '<html lang="en"><head><title><?php echo $title ?></title><body><?php echo $body ?></body></html>';


    /**
     * Test Contenido.Content.conGenerateCode chain
     */
    public function testNoChain()
    {
        // execute chain
        $newCode = cApiCecHook::executeAndReturn($this->_chain, $this->_code);

        $this->assertEquals($this->_code, $newCode);
    }


    /**
     * Test Contenido.Content.conGenerateCode chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentConGenerateCode_Test');

        // execute chain
        $newCode = cApiCecHook::executeAndReturn($this->_chain, $this->_code);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentConGenerateCode_Test');

        $this->assertEquals($this->_codeOneChain, $newCode);
    }


    /**
     * Test Contenido.Content.conGenerateCode chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentConGenerateCode_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentConGenerateCode_Test2');

        // execute chain
        $newCode = cApiCecHook::executeAndReturn($this->_chain, $this->_code);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentConGenerateCode_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentConGenerateCode_Test2');

        $this->assertEquals($this->_codeTwoChains, $newCode);
    }

}
