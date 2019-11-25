<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Contenido.Content.CopyArticle
 *
 * @package Testing
 * @subpackage Test_Chains
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * 1. chain function
 *
 * @param int $idart
 */
function chain_ContenidoContentCopyArticle_Test($idart) {
    ContenidoContentCopyArticleTest::$invokeCounter++;
}

/**
 * 2. chain function
 *
 * @param int $idart
 */
function chain_ContenidoContentCopyArticle_Test2($idart) {
    ContenidoContentCopyArticleTest::$invokeCounter++;
}

/**
 * Class to test Contenido chain Contenido.Content.CopyArticle.
 *
 * @package Testing
 * @subpackage Test_Chains
 */
class ContenidoContentCopyArticleTest extends TestCase {

    /**
     *
     * @var string
     */
    private $_chain = 'Contenido.Content.CopyArticle';

    /**
     *
     * @var int
     */
    private $_srcIdart = 123;

    /**
     *
     * @var int
     */
    private $_dstIdart = 234;

    /**
     *
     * @var int
     */
    public static $invokeCounter = 0;

    /**
     *
     */
    protected function setUp(): void {
        self::$invokeCounter = 0;
    }

    /**
     *
     * @deprecated 2014-08-07
     *         This method is deprecated and is not needed any longer
     */
    protected function tearDown(): void {
        cDeprecated('This method is deprecated and is not needed any longer');
    }

    /**
     * Test Contenido.Content.CopyArticle chain
     */
    public function testNoChain() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // execute chain
        $srcIdart = $this->_srcIdart;
        $dstIdart = $this->_dstIdart;
        $iterator = $cecReg->getIterator($this->_chain);
        while ($chainEntry = $iterator->next()) {
            $chainEntry->execute($srcIdart, $dstIdart);
        }

        $this->assertEquals(array(
            0,
            $this->_srcIdart,
            $this->_dstIdart
        ), array(
            self::$invokeCounter,
            $srcIdart,
            $dstIdart
        ));
    }

    /**
     * Test Contenido.Content.CopyArticle chain
     */
    public function testOneChain() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentCopyArticle_Test');

        // execute chain
        $srcIdart = $this->_srcIdart;
        $dstIdart = $this->_dstIdart;
        $iterator = $cecReg->getIterator($this->_chain);
        while ($chainEntry = $iterator->next()) {
            $chainEntry->execute($srcIdart, $dstIdart);
        }

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentCopyArticle_Test');

        $this->assertEquals(array(
            1,
            $this->_srcIdart,
            $this->_dstIdart
        ), array(
            self::$invokeCounter,
            $srcIdart,
            $dstIdart
        ));
    }

    /**
     * Test Contenido.Content.CopyArticle chain
     */
    public function testTwoChains() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentCopyArticle_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentCopyArticle_Test2');

        // execute chain
        $srcIdart = $this->_srcIdart;
        $dstIdart = $this->_dstIdart;
        $iterator = $cecReg->getIterator($this->_chain);
        while ($chainEntry = $iterator->next()) {
            $chainEntry->execute($srcIdart, $dstIdart);
        }

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentCopyArticle_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentCopyArticle_Test2');

        $this->assertEquals(array(
            2,
            $this->_srcIdart,
            $this->_dstIdart
        ), array(
            self::$invokeCounter,
            $srcIdart,
            $dstIdart
        ));
    }

}
