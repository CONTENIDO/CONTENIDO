<?php
global $_cecRegistry, $cfg;

#$cfg['plugins']['frontendlogic'][] = "category";

cInclude("plugins", "chains/includes/include.chain.frontend.cat_backendaccess.php");
cInclude("plugins", "chains/includes/include.chain.frontend.cat_access.php");
cInclude("plugins", "chains/includes/include.chain.content.createmetatags.php");

$_cecRegistry->addChainFunction("Contenido.Frontend.CategoryAccess", "cecFrontendCategoryAccess");
$_cecRegistry->addChainFunction("Contenido.Frontend.CategoryAccess", "cecFrontendCategoryAccess_Backend");
$_cecRegistry->addChainFunction("Contenido.Content.CreateMetatags", "cecCreateMetatags");
?>
