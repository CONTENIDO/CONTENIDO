<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Generate base href for multiple client domains
 *
 * Client setting must look like this:
 * Type:    client
 * Name:    frontend_pathX (X any number/character)
 * Value:    base href URL (e.g. http://www.example.org/example/)
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Plugins
 * @subpackage Chains
 * @version    1.1.1
 * @author     Andreas Lindner, 4fb AG
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2008-07-31
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

function cecCreateBaseHref($sCurrentBaseHref)
{
    global $cfg, $client;

    $oClient = new cApiClient($client);
    $aSettings = $oClient->getProperties();
    if (is_array($aSettings)) {
        foreach ($aSettings as $aClient) {
            if ($aClient["type"] == "client" && strstr($aClient["name"], "frontend_path") !== false) {
                $aUrlData = parse_url($aClient["value"]);

                if ($aUrlData["host"] == $_SERVER['HTTP_HOST'] ||
                    ("www." . $aUrlData["host"]) == $_SERVER['HTTP_HOST'] ||
                     $aUrlData["host"] ==  "www." . $_SERVER['HTTP_HOST'])
                {
                    // The currently used host has been found as
                    // part of the base href(s) specified in client settings

                    // Return base href as specified in client settings
                    $sNewBaseHref = $aClient["value"];
                    return $sNewBaseHref;
                }
            }
        }
    }

    // We are still here, so no alternative href was found - return the default one
    return $sCurrentBaseHref;
}
?>