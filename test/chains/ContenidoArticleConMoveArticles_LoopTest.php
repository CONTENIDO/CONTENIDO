<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain
 * Contenido.Article.conMoveArticles_Loop
 *
 * @package Testing
 * @subpackage Test_Chains
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

/**
 * 1. chain function
 *
 * @param array $data
 */
function chain_ContenidoArticleConMoveArticles_Loop_Test(array $data) {
    if (isset($data['idartlang']) && $data['idartlang'] == 1234) {
        ContenidoArticleConMoveArticles_LoopTest::$invokeCounter++;
    }
}

/**
 * 2. chain function
 *
 * @param array $data
 */
function chain_ContenidoArticleConMoveArticles_Loop_Test2(array $data) {
    if (isset($data['idartlang']) && $data['idartlang'] == 1234) {
        ContenidoArticleConMoveArticles_LoopTest::$invokeCounter++;
    }
}

/**
 * Class to test Contenido chain Contenido.Article.conMoveArticles_Loop.
 *
 * @package Testing
 * @subpackage Test_Chains
 */
class ContenidoArticleConMoveArticles_LoopTest extends TestCase {

    /**
     *
     * @var int
     */
    public static $invokeCounter = 0;

    /**
     *
     * @var string
     */
    private $_chain = 'Contenido.Article.conMoveArticles_Loop';

    /**
     *
     * @var array
     */
    private $_data = array(
        'idartlang' => 1234,
        'idart' => 12,
        'time_move_cat' => '20091229000000'
    );

    /**
     *
     */
    protected function setUp(): void {
        self::$invokeCounter = 0;
    }

    /**
     * Test Contenido.Article.conMoveArticles_Loop chain
     */
    public function testNoChain() {
        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        $this->assertEquals(array(
            0,
            $this->_data
        ), array(
            self::$invokeCounter,
            $this->_data
        ));
    }

    /**
     * Test Contenido.Article.conMoveArticles_Loop chain
     */
    public function testOneChain() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoArticleConMoveArticles_Loop_Test');

        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoArticleConMoveArticles_Loop_Test');

        $this->assertEquals(array(
            1,
            $this->_data
        ), array(
            self::$invokeCounter,
            $this->_data
        ));
    }

    /**
     * Test Contenido.Article.conMoveArticles_Loop chain
     */
    public function testTwoChains() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoArticleConMoveArticles_Loop_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoArticleConMoveArticles_Loop_Test2');

        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoArticleConMoveArticles_Loop_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoArticleConMoveArticles_Loop_Test2');

        $this->assertEquals(array(
            2,
            $this->_data
        ), array(
            self::$invokeCounter,
            $this->_data
        ));
    }

}
