<?php
/**
 * Plugin mod_rewrite backend include file (in content-top frame)
 *
 * @date        22.04.2008
 * @author      Murat Purc
 * @copyright   © Murat Purc 2008
 * @package     Contenido
 * @subpackage  ModRewrite
 */

defined('CON_FRAMEWORK') or die('Illegal call');


$tpl = new Template();
$tpl->generate(
    $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'mod_rewrite/templates/content_top.html', 0, 0
);
