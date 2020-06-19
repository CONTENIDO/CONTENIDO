<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain
 * Contenido.Content.SaveContentEntry
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
 * @param int $idartlang
 * @param unknown_type $type
 * @param unknown_type $typeid
 * @param unknown_type $value
 * @return mixed
 */
function chain_ContenidoContentSaveContentEntry_Test($idartlang, $type, $typeid, $value) {
    if ($type == 'CMS_HTML') {
        $value = str_replace('<p>', '<p>[foo] ', $value);
    }
    return $value;
}

/**
 * 2. chain function
 *
 * @param int $idartlang
 * @param unknown_type $type
 * @param unknown_type $typeid
 * @param unknown_type $value
 * @return mixed
 */
function chain_ContenidoContentSaveContentEntry_Test2($idartlang, $type, $typeid, $value) {
    if ($type == 'CMS_HTML') {
        $value = str_replace('</p>', ' [bar]</p>', $value);
    }
    return $value;
}

/**
 * Class to test Contenido chain Contenido.Content.SaveContentEntry.
 *
 * @package Testing
 * @subpackage Test_Chains
 */
class ContenidoContentSaveContentEntryTest extends TestCase {

    /**
     *
     * @var string
     */
    private $_chain = 'Contenido.Content.SaveContentEntry';

    /**
     *
     * @var int
     */
    private $_idartlang = 123;

    /**
     *
     * @var string
     */
    private $_type = 'CMS_HTML';

    /**
     *
     * @var string
     */
    private $_typeid = 'CMS_HTML[1]';

    /**
     *
     * @var string
     */
    private $_value = '<p>Test content</p>';

    /**
     *
     * @var string
     */
    private $_valueOneChain = '<p>[foo] Test content</p>';

    /**
     *
     * @var string
     */
    private $_valueTwoChains = '<p>[foo] Test content [bar]</p>';

    /**
     * Test Contenido.Content.SaveContentEntry chain
     */
    public function testNoChain() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // execute chain
        $iterator = $cecReg->getIterator($this->_chain);
        $value = $this->_value;
        while ($chainEntry = $iterator->next()) {
            $value = $chainEntry->execute($this->_idartlang, $this->_type, $this->_typeid, $value);
        }
        $value = urlencode($value);

        $this->assertEquals(urlencode($this->_value), $value);
    }

    /**
     * Test Contenido.Content.SaveContentEntry chain
     */
    public function testOneChain() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentSaveContentEntry_Test');

        // execute chain
        $iterator = $cecReg->getIterator($this->_chain);
        $value = $this->_value;
        while ($chainEntry = $iterator->next()) {
            $value = $chainEntry->execute($this->_idartlang, $this->_type, $this->_typeid, $value);
        }
        $value = urlencode($value);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentSaveContentEntry_Test');

        $this->assertEquals(urlencode($this->_valueOneChain), $value);
    }

    /**
     * Test Contenido.Content.SaveContentEntry chain
     */
    public function testTwoChains() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentSaveContentEntry_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoContentSaveContentEntry_Test2');

        // execute chain
        $iterator = $cecReg->getIterator($this->_chain);
        $value = $this->_value;
        while ($chainEntry = $iterator->next()) {
            $value = $chainEntry->execute($this->_idartlang, $this->_type, $this->_typeid, $value);
        }
        $value = urlencode($value);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentSaveContentEntry_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoContentSaveContentEntry_Test2');

        $this->assertEquals(urlencode($this->_valueTwoChains), $value);
    }

}
