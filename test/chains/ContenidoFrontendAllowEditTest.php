<?php

use PHPUnit\Framework\TestCase;

/**
 * This file contains tests for Contenido chain Contenido.Frontend.AllowEdit
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
 * 1. chain function to check if the user has permission to edit articles in this category
 */
function chain_ContenidoFrontendAllowEdit_Test($lang, $idcat, $idart, $uid)
{
    return true;
}

/**
 * 2. chain function to check if the user has permission to edit articles in this category
 */
function chain_ContenidoFrontendAllowEdit_Test2($lang, $idcat, $idart, $uid)
{
    return false;
}

/**
 * 3. chain function to check if the user has permission to edit articles in this category
 */
function chain_ContenidoFrontendAllowEdit_Test3($lang, $idcat, $idart, $uid)
{
    return true;
}


/**
 * Class to test Contenido chain Contenido.Frontend.AllowEdit.
 * @package          Testing
 * @subpackage       Test_Chains
 */
class ContenidoFrontendAllowEditTest extends TestCase
{
    private $_chain = 'Contenido.Frontend.AllowEdit';
    private $_lang;
    private $_idcat = 10; // Hauptnavigation/Features-dieser-Website/Geschlossener-Bereich/Vertraulich/
    private $_idart = 17; // idart from above
    private $_uid   = null;


    protected function setUp(): void
    {
        $this->_lang = cRegistry::getLanguageId();

        if (!$user = cTestingTestHelper::getUserByUsername('sysadmin')) {
            $this->fail('Couldn\'t get user_id of user "sysadmin".');
            return;
        }
        $this->_uid = $user->user_id;
    }


    /**
     * Test Contenido.Frontend.AllowEdit chain
     */
    public function testNoChain()
    {
        // set n' execute chain
        cApiCecHook::setBreakCondition(false, true); // break at "false", default value "true"
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_idart, $this->_uid);

        $this->assertEquals(true, $allow);
    }


    /**
     * Test Contenido.Frontend.AllowEdit chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendAllowEdit_Test');

        // set n' execute chain
        cApiCecHook::setBreakCondition(false, true); // break at "false", default value "true"
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_idart, $this->_uid);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendAllowEdit_Test');

        $this->assertEquals(true, $allow);
    }


    /**
     * Test Contenido.Frontend.AllowEdit chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendAllowEdit_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendAllowEdit_Test2');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendAllowEdit_Test3');

        // set n' execute chain
        cApiCecHook::setBreakCondition(false, true); // break at "false", default value "true"
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_idart, $this->_uid);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendAllowEdit_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendAllowEdit_Test2');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendAllowEdit_Test2');

        $this->assertEquals(false, $allow);
    }

}
