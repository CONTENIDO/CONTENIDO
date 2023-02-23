<?php

/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsModifier
 */
/**
 * Smarty con_asset_backend modifier plugin for CONTENIDO backend
 * Type:     modifier
 * Name:     con_asset_backend
 * Purpose:  Adds version hash to js/css assets
 *
 * @author     Murat Purc <murat@purc.de>
 *
 * @param string  $path      Relative path to asset file
 * @return string  Modified path with version hash
 */
function smarty_modifier_con_asset_backend(string $path): string
{
    return cAsset::backend($path);
}
