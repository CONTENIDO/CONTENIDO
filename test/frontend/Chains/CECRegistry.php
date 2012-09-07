<?php
/**
 * Unittest for Contenido cApiCECRegistry
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
function chain_cApiCECRegistry_Test()
{
    // donut
}

/**
 * Chain callback class
 */
class chain_cApiCECRegistryClass_Test
{
    public function callMe()
    {
        // donut
    }
}

/**
 * Chain callback class with static method
 */
class chain_cApiCECRegistryClassStatic_Test
{
    public static function callMe()
    {
        // donut
    }
}



/**
 * Class to test cApiCECRegistry.
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        30.12.2009
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Chains
 */
class cApiCECRegistryTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test
     * - cApiCECRegistry->registerChain()
     * - cApiCECRegistry->isChainRegistered()
     * - cApiCECRegistry->unregisterChain()
     */
    public function testChains()
    {
        // get cec registry instance
        $cecReg = cApiCECRegistry::getInstance();

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
     * - cApiCECRegistry->addChainFunction()
     * - cApiCECRegistry->chainFunctionExists()
     * - cApiCECRegistry->removeChainFunction()
     */
    public function testChainFunctions()
    {
        // get cec registry instance
        $cecReg = cApiCECRegistry::getInstance();

        // register chain
        $cecReg->registerChain('TestChain.Example');

        // add chain function
        $cecReg->addChainFunction('TestChain.Example', 'chain_cApiCECRegistry_Test');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCECRegistry_Test');
        $this->assertEquals(true, $exists);

        // remove chain function
        $cecReg->removeChainFunction('TestChain.Example', 'chain_cApiCECRegistry_Test');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCECRegistry_Test');
        $this->assertEquals(false, $exists);

        // add chain callback (object)
        $cecReg->addChainFunction('TestChain.Example', 'chain_cApiCECRegistryClass_Test->callMe');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCECRegistryClass_Test->callMe');
        $this->assertEquals(true, $exists);

        // remove chain callback (object)
        $cecReg->removeChainFunction('TestChain.Example', 'chain_cApiCECRegistryClass_Test->callMe');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCECRegistryClass_Test->callMe');
        $this->assertEquals(false, $exists);

        // add chain callback (object with static method)
        $cecReg->addChainFunction('TestChain.Example', 'chain_cApiCECRegistryClassStatic_Test::callMe');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCECRegistryClassStatic_Test::callMe');
        $this->assertEquals(true, $exists);

        // remove chain callback (object with static method)
        $cecReg->removeChainFunction('TestChain.Example', 'chain_cApiCECRegistryClassStatic_Test::callMe');
        $exists = $cecReg->chainFunctionExists('TestChain.Example', 'chain_cApiCECRegistryClassStatic_Test::callMe');
        $this->assertEquals(false, $exists);

        // unregister chain
        $cecReg->unregisterChain('TestChain.Example');
    }

}
