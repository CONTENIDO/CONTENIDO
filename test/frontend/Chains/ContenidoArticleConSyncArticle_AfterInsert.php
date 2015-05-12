<?php

/**
 * This file contains tests for Contenido chain
 * Contenido.Article.conSyncArticle_AfterInsert
 *
 * @package Testing
 * @subpackage Test_Chains
 * @version SVN Revision $Rev:$
 *
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * 1. chain function
 *
 * @param array $data
 */
function chain_ContenidoArticleConSyncArticle_AfterInsert_Test(array $data) {
    if (isset($data['dest_art_lang']) && is_array($data['dest_art_lang'])) {
        ContenidoArticleConSyncArticle_AfterInsertTest::$invokeCounter++;
    }
}

/**
 * 2. chain function
 *
 * @param array $data
 */
function chain_ContenidoArticleConSyncArticle_AfterInsert_Test2(array $data) {
    if (isset($data['dest_art_lang']) && is_array($data['dest_art_lang'])) {
        ContenidoArticleConSyncArticle_AfterInsertTest::$invokeCounter++;
    }
}

/**
 * Class to test Contenido chain Contenido.Article.conSyncArticle_AfterInsert.
 *
 * @package Testing
 * @subpackage Test_Chains
 */
class ContenidoArticleConSyncArticle_AfterInsertTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var unknown_type
     */
    public static $invokeCounter = 0;

    /**
     *
     * @var unknown_type
     */
    private $_chain = 'Contenido.Article.conSyncArticle_AfterInsert';

    /**
     *
     * @var unknown_type
     */
    private $_data = array(
        'src_art_lang' => array(),
        'dest_art_lang' => array(
            'idartlang' => 123,
            'idlang' => 2,
            'idtplcfg' => 21
        )
    );

    /**
     *
     */
    protected function setUp() {
        self::$invokeCounter = 0;
    }

    /**
     * Test Contenido.Article.conSyncArticle_AfterInsert chain
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
     * Test Contenido.Article.conSyncArticle_AfterInsert chain
     */
    public function testOneChain() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoArticleConSyncArticle_AfterInsert_Test');

        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoArticleConSyncArticle_AfterInsert_Test');

        $this->assertEquals(array(
            1,
            $this->_data
        ), array(
            self::$invokeCounter,
            $this->_data
        ));
    }

    /**
     * Test Contenido.Article.conSyncArticle_AfterInsert chain
     */
    public function testTwoChains() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoArticleConSyncArticle_AfterInsert_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoArticleConSyncArticle_AfterInsert_Test2');

        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoArticleConSyncArticle_AfterInsert_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoArticleConSyncArticle_AfterInsert_Test2');

        $this->assertEquals(array(
            2,
            $this->_data
        ), array(
            self::$invokeCounter,
            $this->_data
        ));
    }

}
