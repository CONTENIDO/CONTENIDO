<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Contenido.Content.DeleteArticle
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
function chain_ContenidoContentDeleteArticle_Test($idart)
{
    ContenidoContentDeleteArticleTest::$invokeCounter++;
}

/**
 * 2. chain function
 */
function chain_ContenidoContentDeleteArticle_Test2($idart)
{
    ContenidoContentDeleteArticleTest::$invokeCounter++;
}


/**
 * Class to test Contenido chain Contenido.Content.DeleteArticle.
 * @package          Testing
 * @subpackage       Test_Chains
 */
class ContenidoContentDeleteArticleTest extends TestCase
{
    private $_chain = 'Contenido.Content.DeleteArticle';
    private $_idart = 123;

    public static $invokeCounter = 0;


    protected function setUp(): void
    {
        self::$invokeCounter = 0;
    }


    /**
     * Test Contenido.Content.DeleteArticle chain
     */
    public function testNoChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // execute chain
        $idart = $this->_idart;
        $iterator = $cecReg->getIterator($this->_chain);
        while ($chainEntry = $iterator->next()) {
            $chainEntry->execute($idart);
        }

        $this->assertEquals(array(0, $this->_idart), array(self::$invokeCounter, $idart));
    }


    /**
     * Test Contenido.Content.DeleteArticle chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentDeleteArticle_Test');

        // execute chain
        $idart = $this->_idart;
        $iterator = $cecReg->getIterator($this->_chain);
        while ($chainEntry = $iterator->next()) {
            $chainEntry->execute($idart);
        }

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentDeleteArticle_Test');

        $this->assertEquals(array(1, $this->_idart), array(self::$invokeCounter, $idart));
    }


    /**
     * Test Contenido.Content.DeleteArticle chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentDeleteArticle_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentDeleteArticle_Test2');

        // execute chain
        $idart = $this->_idart;
        $iterator = $cecReg->getIterator($this->_chain);
        while ($chainEntry = $iterator->next()) {
            $chainEntry->execute($idart);
        }

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentDeleteArticle_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentDeleteArticle_Test2');

        $this->assertEquals(array(2, $this->_idart), array(self::$invokeCounter, $idart));
    }

}
