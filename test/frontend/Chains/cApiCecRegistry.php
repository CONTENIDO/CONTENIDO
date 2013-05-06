<?php
/**
 * This file contains tests for Contenido cApiCecRegistry.
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
 * Chain function
 */
function chain_cApiCecRegistry_Test()
{
    // donut
}

/**
 * Chain callback class
 * @package          Testing
 * @subpackage       Test_Chains
 */
class chain_cApiCecRegistryClass_Test
{
    public function callMe()
    {
        // donut
    }
}

/**
 * Chain callback class with static method
 * @package          Testing
 * @subpackage       Test_Chains
 */
class chain_cApiCecRegistryClassStatic_Test
{
    public static function callMe()
    {
        // donut
    }
}



/**
 * Class to test cApiCecRegistry.
 * @package          Testing
 * @subpackage       Test_Chains
 */
class cApiCecRegistryTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test
     * - cApiCecRegistry->registerChain()
     * - cApiCecRegistry->isChainRegistered()
     * - cApiCecRegistry->unregisterChain()
     */
    public function testChains()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // register chain
        $cecReg->registerChain('TestChain.Example');
        $isRegistered = $cecReg->isChainRegistered('TestChain.Example');
        $this->assertEquals(true, $isRegistered);

        // unregister chain
        $cecReg->unregisterChain('TestChain.Example');
        $isRegistered = $cecReg->isChainRegistered('TestChain.Example');
        $this->assertEquals(false, $isRegistered);
    }


    /**
     * Test
     * - cApiCecRegistry->addChainFunction()
     * - cApiCecRegistry->chainFunctionExists()
     * - cApiCecRegistry->removeChainFunction()
     */
    public function testChainFunctions()
    {
        // get cec registry instance
        $cecReg = cApiCecRegistry::getInstance();

        // register chain
        $cecReg->registerChain('TestChain.Example');

        // add chain function
        $cecReg->addChainFunction('TestChain.Example', 'chain_cApiCecRegistry_Test');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistry_Test');
        $this->assertEquals(true, $exists);

        // remove chain function
        $cecReg->removeChainFunction('TestChain.Example', 'chain_cApiCecRegistry_Test');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistry_Test');
        $this->assertEquals(false, $exists);

        // add chain callback (object)
        $cecReg->addChainFunction('TestChain.Example', 'chain_cApiCecRegistryClass_Test->callMe');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistryClass_Test->callMe');
        $this->assertEquals(true, $exists);

        // remove chain callback (object)
        $cecReg->removeChainFunction('TestChain.Example', 'chain_cApiCecRegistryClass_Test->callMe');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistryClass_Test->callMe');
        $this->assertEquals(false, $exists);

        // add chain callback (object with static method)
        $cecReg->addChainFunction('TestChain.Example', 'chain_cApiCecRegistryClassStatic_Test::callMe');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistryClassStatic_Test::callMe');
        $this->assertEquals(true, $exists);

        // remove chain callback (object with static method)
        $cecReg->removeChainFunction('TestChain.Example', 'chain_cApiCecRegistryClassStatic_Test::callMe');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistryClassStatic_Test::callMe');
        $this->assertEquals(false, $exists);

        // unregister chain
        $cecReg->unregisterChain('TestChain.Example');
    }

}
