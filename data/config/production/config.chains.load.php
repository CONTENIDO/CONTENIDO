<?php

/**
 * This file contains all chains to load in the registry.
 *
 * @package    Core
 * @subpackage Backend_ConfigFile
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'chains/include.chain.frontend.cat_backendaccess.php');
cInclude('includes', 'chains/include.chain.frontend.cat_access.php');
cInclude('includes', 'chains/include.chain.content.createmetatags.php');
cInclude('includes', 'chains/include.chain.frontend.createbasehref.php');
cInclude('includes', 'chains/include.chain.content.indexarticle.php');
cInclude('includes', 'chains/include.chain.template.parsetemplate.php');

// get cec registry instance
$cecRegistry = cApiCecRegistry::getInstance();
$cecRegistry->addChainFunction('Contenido.Frontend.CategoryAccess', 'cecFrontendCategoryAccess');
$cecRegistry->addChainFunction('Contenido.Frontend.CategoryAccess', 'cecFrontendCategoryAccess_Backend');
$cecRegistry->addChainFunction('Contenido.Content.CreateMetatags', 'cecCreateMetatags');
$cecRegistry->addChainFunction('Contenido.Frontend.BaseHrefGeneration', 'cecCreateBaseHref');
$cecRegistry->addChainFunction('Contenido.Content.AfterStore', 'cecIndexArticle');
$cecRegistry->addChainFunction('Contenido.Template.BeforeParse', 'cecParseTemplate');
