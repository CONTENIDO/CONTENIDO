<?php
/**
 * CONTENIDO Chain.
 * Generate base href for multiple client domains
 *
 * Client setting must look like this:
 * Type:    client
 * Name:    frontend_pathX (X any number/character)
 * Value:   base href URL (e.g. http://www.example.org/example/)
 *
 * @package          Core
 * @subpackage       Chain
 * @version          SVN Revision $Rev:$
 *
 * @author           Andreas Lindner
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

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