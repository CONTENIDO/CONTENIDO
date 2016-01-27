<?php

/**
 * This file contains tests for Contenido chain
 * Contenido.Frontend.CategoryAccess
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
 * 1. chain function to check if the user has permission to access a category
 *
 * @param unknown_type $lang
 * @param unknown_type $idcat
 * @param unknown_type $uid
 * @return boolean
 */
function chain_ContenidoFrontendCategoryAccess_Test($lang, $idcat, $uid) {
    return false;
}

/**
 * 2. chain function to check if the user has permission to access a category
 *
 * @param unknown_type $lang
 * @param unknown_type $idcat
 * @param unknown_type $uid
 * @return boolean
 */
function chain_ContenidoFrontendCategoryAccess_Test2($lang, $idcat, $uid) {
    return true;
}

/**
 * 3. chain function to check if the user has permission to access a category
 *
 * @param unknown_type $lang
 * @param unknown_type $idcat
 * @param unknown_type $uid
 * @return boolean
 */
function chain_ContenidoFrontendCategoryAccess_Test3($lang, $idcat, $uid) {
    return false;
}

/**
 * Class to test Contenido chain Contenido.Frontend.CategoryAccess
 *
 * @package Testing
 * @subpackage Test_Chains
 */
class ContenidoFrontendCategoryAccessTest extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var string
     */
    private $_chain = 'Contenido.Frontend.CategoryAccess';

    /**
     *
     * @var int
     */
    private $_lang;

    /**
     * Hauptnavigation/Features-dieser-Website/Geschlossener-Bereich/Vertraulich/
     *
     * @var int
     */
    private $_idcat = 10;

    /**
     *
     * @var unknown_type
     */
    private $_uid = null;

    /**
     *
     */
    protected function setUp() {
        $this->_lang = $GLOBALS['lang'];

        if (!$user = cTestingTestHelper::getUserByUsername('sysadmin')) {
            $this->fail('Couldn\'t get user_id of user "sysadmin".');
            return;
        }
        $this->_uid = $user->user_id;
    }

    /**
     * Test Contenido.Frontend.CategoryAccess chain
     */
    public function testNoChain() {
        // set n' execute chain
        // break at "true", default value "false"
        cApiCecHook::setBreakCondition(true, false);
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_uid);

        $this->assertEquals(false, $allow);
    }

    /**
     * Test Contenido.Frontend.CategoryAccess chain
     */
    public function testOneChain() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test');

        // set n' execute chain
        // break at "true", default value "false"
        cApiCecHook::setBreakCondition(true, false);
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_uid);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test');

        $this->assertEquals(false, $allow);
    }

    /**
     * Test Contenido.Frontend.CategoryAccess chain
     */
    public function testTwoChains() {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test2');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test3');

        // set n' execute chain
        // break at "true", default value "false"
        cApiCecHook::setBreakCondition(true, false);
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_uid);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test2');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test3');

        $this->assertEquals(true, $allow);
    }

}
