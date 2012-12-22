<?php
/**
 * Unittest for Contenido cApiCecRegistry
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        31.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
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
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
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
