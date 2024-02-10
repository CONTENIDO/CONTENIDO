<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Contenido.Article.conCopyArtLang_AfterInsert
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
 * 1. chain function
 */
function chain_ContenidoArticleConCopyArtLang_AfterInsert_Test(array $data)
{
    if (isset($data['idartlang']) && $data['idartlang'] == 2345) {
        ContenidoArticleConCopyArtLang_AfterInsertTest::$invokeCounter++;
    }
}

/**
 * 2. chain function
 */
function chain_ContenidoArticleConCopyArtLang_AfterInsert_Test2(array $data)
{
    if (isset($data['idartlang']) && $data['idartlang'] == 2345) {
        ContenidoArticleConCopyArtLang_AfterInsertTest::$invokeCounter++;
    }
}


/**
 * Class to test Contenido chain Contenido.Article.conCopyArtLang_AfterInsert.
 * @package    Testing
 * @subpackage Test_Chains
 */
class ContenidoArticleConCopyArtLang_AfterInsertTest extends TestCase
{
    private $_chain = 'Contenido.Article.conCopyArtLang_AfterInsert';
    private $_data = array(
        'idartlang' => 2345,
        'idart' => 123,
        'idlang' => 1,
        'idtplcfg' => 33,
        'title' => 'this is a title'
    );

    public static $invokeCounter = 0;


    protected function setUp(): void
    {
        self::$invokeCounter = 0;
    }


    /**
     * Test Contenido.Article.conCopyArtLang_AfterInsert chain
     */
    public function testNoChain()
    {
        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        $this->assertEquals(array(0, $this->_data), array(self::$invokeCounter, $this->_data));
    }


    /**
     * Test Contenido.Article.conCopyArtLang_AfterInsert chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoArticleConCopyArtLang_AfterInsert_Test');

        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoArticleConCopyArtLang_AfterInsert_Test');

        $this->assertEquals(array(1, $this->_data), array(self::$invokeCounter, $this->_data));
    }


    /**
     * Test Contenido.Article.conCopyArtLang_AfterInsert chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoArticleConCopyArtLang_AfterInsert_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoArticleConCopyArtLang_AfterInsert_Test2');

        // execute chain
        cApiCecHook::execute($this->_chain, $this->_data);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoArticleConCopyArtLang_AfterInsert_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoArticleConCopyArtLang_AfterInsert_Test2');

        $this->assertEquals(array(2, $this->_data), array(self::$invokeCounter, $this->_data));
    }

}
