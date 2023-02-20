<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsModifier
 */
/**
 * Smarty con_asset_frontend modifier plugin for contenido frontend
 * Type:     modifier
 * Name:     con_asset_frontend
 * Purpose:  Adds version hash to js/css assets
 *
 * @author Murat Purc <murat@purc.de>
 *
 * @param string  $path      Relative path to asset file
 * @param int     $clientId  Client id
 * @return string  Modified path with version hash
 */
function smarty_modifier_con_asset_frontend(string $path, int $clientId = 0): string
{
    if (!$clientId) {
        $clientId = cSecurity::toInteger(cRegistry::getClientId());
    }
    return cAsset::frontend($path, $clientId);
}
