<?php

use PHPUnit\Framework\TestCase;

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
 * @param int $lang
 * @param int $idcat
 * @param string $uid
 * @return boolean
 */
function chain_ContenidoFrontendCategoryAccess_Test($lang, $idcat, $uid) {
    return false;
}

/**
 * 2. chain function to check if the user has permission to access a category
 *
 * @param int $lang
 * @param int $idcat
 * @param string $uid
 * @return boolean
 */
function chain_ContenidoFrontendCategoryAccess_Test2($lang, $idcat, $uid) {
    return true;
}

/**
 * 3. chain function to check if the user has permission to access a category
 *
 * @param int $lang
 * @param int $idcat
 * @param string $uid
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
class ContenidoFrontendCategoryAccessTest extends TestCase {

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
     * @var string
     */
    private $_userId = null;

    /**
     *
     */
    protected function setUp(): void {
        $this->_lang = cRegistry::getLanguageId();

        if (!$user = cTestingTestHelper::getUserByUsername('sysadmin')) {
            $this->fail('Couldn\'t get user_id of user "sysadmin".');
            return;
        }
        $this->_userId = $user->user_id;
    }

    /**
     * Test Contenido.Frontend.CategoryAccess chain
     */
    public function testNoChain() {
        // set n' execute chain
        // break at "true", default value "false"
        cApiCecHook::setBreakCondition(true, false);
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_userId);

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
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_userId);

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
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_userId);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test2');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test3');

        $this->assertEquals(true, $allow);
    }

}
