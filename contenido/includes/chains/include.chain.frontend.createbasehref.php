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
 * @author           Andreas Lindner
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 *
 * @param string $currentBaseHref
 *
 * @return string
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function cecCreateBaseHref($currentBaseHref) {

    // get props of current client
    $props = cRegistry::getClient()->getProperties();
    //$props = cRegistry::getClient()->getPropertiesByType('client');

    // return rootdir as defined in AMR if client has no props
    if (!is_array($props)) {
        return $currentBaseHref;
    }

    foreach ($props as $prop) {

        // skip props that are not of type 'client'
        if ($prop['type'] != 'client') {
            continue;
        }

        // skip props whose name does not contain 'frontend_path'
        if (false === strstr($prop['name'], 'frontend_path')) {
            continue;
        }

        // current host & path (HTTP_HOST & REQUEST_URI)
        $httpHost = $_SERVER['HTTP_HOST'];
        $httpPath = $_SERVER['REQUEST_URI'];

        // host & path of configured alternative URL
        $propHost = parse_url($prop['value'], PHP_URL_HOST);
        $propPath = parse_url($prop['value'], PHP_URL_PATH);

        // skip if http host does not equal configured host (allowing for optional www)
        if ($propHost != $httpHost && ('www.' . $propHost) != $httpHost && $propHost != 'www.' . $httpHost) {
            continue;
        }

        // skip if http path does not start with configured path
        if (0 !== cString::findFirstPos($httpPath, $propPath)) {
            continue;
        }

        // return URL as specified in client settings
        $currentBaseHref = $prop['value'];

    }

    // return default
    return $currentBaseHref;

}

?>