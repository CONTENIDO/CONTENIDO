<?php
/**
 * Unittest for Contenido chain Contenido.Frontend.CategoryAccess
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
 */


/**
 * 1. chain function to check if the user has permission to access a category
 */
function chain_ContenidoFrontendCategoryAccess_Test($lang, $idcat, $uid)
{
    return false;
}

/**
 * 2. chain function to check if the user has permission to access a category
 */
function chain_ContenidoFrontendCategoryAccess_Test2($lang, $idcat, $uid)
{
    return true;
}

/**
 * 3. chain function to check if the user has permission to access a category
 */
function chain_ContenidoFrontendCategoryAccess_Test3($lang, $idcat, $uid)
{
    return false;
}


/**
 * Class to test Contenido chain Contenido.Frontend.CategoryAccess
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
 */
class ContenidoFrontendCategoryAccessTest extends PHPUnit_Framework_TestCase
{
    private $_chain = 'Contenido.Frontend.CategoryAccess';
    private $_lang;
    private $_idcat = 10; // Hauptnavigation/Features-dieser-Website/Geschlossener-Bereich/Vertraulich/
    private $_uid = null;


    protected function setUp()
    {
        $this->_lang = $GLOBALS['lang'];

        if (!$user = ContenidoTestHelper::getUserByUsername('sysadmin')) {
            $this->fail('Couldn\'t get user_id of user "sysadmin".');
            return;
        }
        $this->_uid = $user->user_id;
    }


    /**
     * Test Contenido.Frontend.CategoryAccess chain
     */
    public function testNoChain()
    {
        // set n' execute chain
        cApiCecHook::setBreakCondition(true, false); // break at "true", default value "false"
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_uid);

        $this->assertEquals(false, $allow);
    }


    /**
     * Test Contenido.Frontend.CategoryAccess chain
     */
    public function testOneChain()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test');

        // set n' execute chain
        cApiCecHook::setBreakCondition(true, false); // break at "true", default value "false"
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_uid);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test');

        $this->assertEquals(false, $allow);
    }


    /**
     * Test Contenido.Frontend.CategoryAccess chain
     */
    public function testTwoChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // add chain functions
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test2');
        $cecReg->addChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test3');

        // set n' execute chain
        cApiCecHook::setBreakCondition(true, false); // break at "true", default value "false"
        $allow = cApiCecHook::executeWhileBreakCondition($this->_chain, $this->_lang, $this->_idcat, $this->_uid);

        // remove chain functions
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test2');
        $cecReg->removeChainFunction($this->_chain, 'chain_ContenidoFrontendCategoryAccess_Test3');

        $this->assertEquals(true, $allow);
    }

}
