<?php
/**
 * This file contains all chains to load in the registry.
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// get cec registry instance
$_cecRegistry = cApiCecRegistry::getInstance();

cInclude('includes', 'chains/include.chain.frontend.cat_backendaccess.php');
cInclude('includes', 'chains/include.chain.frontend.cat_access.php');
cInclude("plugins", "chains/includes/include.chain.content.createmetatags.php");
cInclude('includes', 'chains/include.chain.frontend.createbasehref.php');

$_cecRegistry->addChainFunction("Contenido.Frontend.CategoryAccess", "cecFrontendCategoryAccess");
$_cecRegistry->addChainFunction("Contenido.Frontend.CategoryAccess", "cecFrontendCategoryAccess_Backend");
$_cecRegistry->addChainFunction("Contenido.Content.CreateMetatags", "cecCreateMetatags");
$_cecRegistry->addChainFunction("Contenido.Frontend.BaseHrefGeneration", "cecCreateBaseHref");