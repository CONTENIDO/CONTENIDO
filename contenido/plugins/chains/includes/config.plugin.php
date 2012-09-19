<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * @package    CONTENIDO Plugins
 * @subpackage Chains
 * @version    1.0
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
global $_cecRegistry, $cfg;

cInclude("plugins", "chains/includes/include.chain.frontend.cat_backendaccess.php");
cInclude("plugins", "chains/includes/include.chain.frontend.cat_access.php");
cInclude("plugins", "chains/includes/include.chain.frontend.createbasehref.php");

$_cecRegistry->addChainFunction("Contenido.Frontend.CategoryAccess", "cecFrontendCategoryAccess");
$_cecRegistry->addChainFunction("Contenido.Frontend.CategoryAccess", "cecFrontendCategoryAccess_Backend");
$_cecRegistry->addChainFunction("Contenido.Frontend.BaseHrefGeneration", "cecCreateBaseHref");
