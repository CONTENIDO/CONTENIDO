<?php
/**
 * This file contains tests for Contenido chain Contenido.Content.CopyArticle
 *
 * @package          Testing
 * @subpackage       Test_Chains
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

/**
 * 1. chain function
 */
function chain_ContenidoContentCopyArticle_Test($idart)
{
    ContenidoContentCopyArticleTest::$invokeCounter++;
}

/**
 * 2. chain function
 */
function chain_ContenidoContentCopyArticle_Test2($idart)
{
    ContenidoContentCopyArticleTest::$invokeCounter++;
}


/**
 * Class to test Contenido chain Contenido.Content.CopyArticle.
 * @package          Testing
 * @subpackage       Test_Chains
 */
class ContenidoContentCopyArticleTest extends PHPUnit_Framework_TestCase
{
    private $_chain = 'Contenido.Content.CopyArticle';
    private $_srcIdart = 123;
    private $_dstIdart = 234;

    public static $invokeCounter = 0;


    protected function setUp()
    {
        self::$invokeCounter = 0;
        cApiCecRegistry::getInstance()->registerChain($this->_chain);
    }


    public function tearDown()
    {
        cApiCecRegistry::getInstance()->unregisterChain($this->_chain);
    }


    /**
     * Test Contenido.Content.CopyArticle chain
     */
    public function testNoChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // execute chain
        $srcIdart = $this->_srcIdart;
        $dstIdart = $this->_dstIdart;
        $iterator = $cecReg->getIterator($this->_chain);
        while ($chainEntry = $iterator->next()) {
            $chainEntry->execute($srcIdart, $dstIdart);
        }

        $this->assertEquals(array(0, $this->_srcIdart, $this->_dstIdart), array(self::$invokeCounter, $srcIdart, $dstIdart));
    }


    /**
     * Test Contenido.Content.CopyArticle chain
     */
    public function testOneChain()
    {
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

        $this->assertEquals(array(1, $this->_srcIdart, $this->_dstIdart), array(self::$invokeCounter, $srcIdart, $dstIdart));
    }


    /**
     * Test Contenido.Content.CopyArticle chain
     */
    public function testTwoChains()
    {
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

        $this->assertEquals(array(2, $this->_srcIdart, $this->_dstIdart), array(self::$invokeCounter, $srcIdart, $dstIdart));
    }

}
