<?php

/**
 * This file contains tests for Contenido cApiCecRegistry.
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
 * Chain function
 */
function chain_cApiCecRegistry_Test()
{
    // donut
}

/**
 * Chain callback class
 *
 * @package    Testing
 * @subpackage Test_Chains
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
 *
 * @package    Testing
 * @subpackage Test_Chains
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
 * @package    Testing
 * @subpackage Test_Chains
 */
class cApiCecRegistryTest extends cTestingTestCase
{

    /**
     * Test
     * - cApiCecRegistry->addChainFunction()
     * - cApiCecRegistry->chainFunctionExists()
     * - cApiCecRegistry->removeChainFunction()
     *
     * @throws cInvalidArgumentException
     */
    public function testChainFunctions()
    {
        // get cec registry instance
        $cecRegistry = cApiCecRegistry::getInstance();

        // add chain function
        $cecRegistry->addChainFunction('TestChain.Example', 'chain_cApiCecRegistry_Test');
        $exists = $cecRegistry->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistry_Test');
        $this->assertEquals(true, $exists);

        // remove chain function
        $cecRegistry->removeChainFunction('TestChain.Example', 'chain_cApiCecRegistry_Test');
        $exists = $cecRegistry->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistry_Test');
        $this->assertEquals(false, $exists);

        // add chain callback (object)
        $cecRegistry->addChainFunction('TestChain.Example', 'chain_cApiCecRegistryClass_Test->callMe');
        $exists = $cecRegistry->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistryClass_Test->callMe');
        $this->assertEquals(true, $exists);

        // remove chain callback (object)
        $cecRegistry->removeChainFunction('TestChain.Example', 'chain_cApiCecRegistryClass_Test->callMe');
        $exists = $cecRegistry->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistryClass_Test->callMe');
        $this->assertEquals(false, $exists);

        // add chain callback (object with static method)
        $cecRegistry->addChainFunction('TestChain.Example', 'chain_cApiCecRegistryClassStatic_Test::callMe');
        $exists = $cecRegistry->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistryClassStatic_Test::callMe');
        $this->assertEquals(true, $exists);

        // remove chain callback (object with static method)
        $cecRegistry->removeChainFunction('TestChain.Example', 'chain_cApiCecRegistryClassStatic_Test::callMe');
        $exists = $cecRegistry->chainFunctionExists('TestChain.Example', 'chain_cApiCecRegistryClassStatic_Test::callMe');
        $this->assertEquals(false, $exists);
    }
}
