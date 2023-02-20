<?php

/**
 * CONTENIDO Chain.
 * Parse template to do some CONTENIDO specific replacements.
 *
 * Replaces following placeholders in templates:
 * - {_SID_}:  CONTENIDO session id
 * - {_PATH_CONTENIDO_FULLHTML_}:  Full URL to contenido backend (protocol + host + path)
 * - {_META_HEAD_CONTENIDO_}:  Default meta tags
 * - {_CSS_HEAD_CONTENIDO_}:  Default links tags to load core CSS files
 * - {_CSS_HEAD_CONTENIDO_FULLHTML_}:  Default links tags with full URL to contenido backend
 * - {_JS_HEAD_CONTENIDO_}:  Default script tags to load core JS files
 * - {_JS_HEAD_CONTENIDO_FULLHTML_}:  Default script tags with full URL to contenido backend
 *
 * @package          Core
 * @subpackage       Chain
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Does some replacements in the given template.
 * Replaces some CONTENIDO specific placeholders against their values.
 *
 * @param string    $template
 *         Template string to preprocess
 * @param cTemplate $templateObj
 *         The current template instance
 *
 * @return string
 *
 * @throws cInvalidArgumentException
 */
function cecParseTemplate($template, cTemplate $templateObj) {

    global $frame;

    // Autofill special placeholders like
    // - Session id
    // - Initial CONTENIDO scripts

    $prefix = "\n    ";

    $cfg = cRegistry::getConfig();
    $sessid = (string) cRegistry::getBackendSessionId();
    $backendPath = cRegistry::getBackendUrl();
    $backendLang = cRegistry::getBackendLanguage();
    // Fixme: Creates an error on backend login form, since we have no language there, see main.loginform.php
    // $oLanguage = cRegistry::getLanguage();
    // $encoding = $oLanguage->get("encoding");
    $languageid = cRegistry::getLanguageId();
    if ($languageid) {
        $oLanguage = cRegistry::getLanguage();
        $encoding = $oLanguage->get('encoding');
    } else {
        $encoding = 'utf-8';
    }
    $frameNr = (!empty($frame) && is_numeric($frame)) ? $frame : 0;

    // Default meta tags
    // @TODO  Make this also configurable
    $metaCon = '
    <meta http-equiv="Content-type" content="text/html;charset=' . $encoding . '">
    <meta http-equiv="expires" content="0">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="pragma" content="no-cache">';

    // JavaScript configuration
    $urlHelp = !empty($cfg['help_url']) ? $cfg['help_url'] : '#';
    $jsConfigurationAdded = false;
    $jsConfiguration = '
    <script type="text/javascript">
    (function(Con, $) {
        Con.sid = "' . $sessid . '";
        $.extend(Con.cfg, {
            urlBackend: "' .  $backendPath . '",
            urlHelp: "' . $urlHelp . '",
            belang: "' . $backendLang . '",
            frame: ' . $frameNr . '
        });
    })(Con, Con.$);
    </script>';

    // Anonymous function to deal with the '{basePath}' prefix
    $assetBackendFn = function($file) {
        if (strpos($file, '{basePath}') === 0) {
            $file = str_replace('{basePath}', '', $file);
            $file = cAsset::backend($file);
            return '{basePath}' . $file;
        } else {
            return cAsset::backend($file);
        }
    };

    // Default CSS styles
    $cssHeadCon = '';
    $files = $cfg['backend_template']['css_files'];
    foreach ($files as $file) {
        $file = $assetBackendFn($file);
        $cssHeadCon .= $prefix . '<link rel="stylesheet" type="text/css" href="' . $file . '">';
    }
    $cssHeadCon = $prefix . "<!-- CSS -->" . $cssHeadCon . $prefix . "<!-- /CSS -->";

    // Default JavaScript files & JS code
    $jsHeadCon = '';
    $files = $cfg['backend_template']['js_files'];
    foreach ($files as $file) {
        if ('_CONFIG_' === $file) {
            $jsHeadCon .= $jsConfiguration;
            $jsConfigurationAdded = true;
        } else {
            $file = $assetBackendFn($file);
            $jsHeadCon .= $prefix . '<script type="text/javascript" src="' . $file . '"></script>';
        }
    }

    if (false === $jsConfigurationAdded) {
        $jsHeadCon .= $jsConfiguration;
    }

    $jsHeadCon = $prefix . "<!-- JS -->" . $jsHeadCon . $prefix . "<!-- /JS -->";

    // Placeholders to replace
    $replacements = [
        '_SID_'                         => $sessid,
        '_PATH_CONTENIDO_FULLHTML_'     => $backendPath,
        '_META_HEAD_CONTENIDO_'         => $metaCon,
        '_CSS_HEAD_CONTENIDO_'          => str_replace('{basePath}', '', $cssHeadCon),
        '_CSS_HEAD_CONTENIDO_FULLHTML_' => str_replace('{basePath}', $backendPath, $cssHeadCon),
        '_JS_HEAD_CONTENIDO_'           => str_replace('{basePath}', '', $jsHeadCon),
        '_JS_HEAD_CONTENIDO_FULLHTML_'  => str_replace('{basePath}', $backendPath, $jsHeadCon),
    ];

    // Loop through all replacements and replace keys which are not in needles but found
    // in the template
    foreach ($replacements as $key => $value) {
        $placeholder = '{' . $key . '}';
        if (!in_array($key, $templateObj->needles) && false !== cString::findFirstPos($template, $placeholder)) {
            $template = str_replace($placeholder, $value, $template);
        }
    }

    // Replace all asset marker like {_ASSET(scripts/rowMark.js)_}
    $template = preg_replace_callback('#{_ASSET\((.*)\)_}#', function($matches) {
        return cAsset::backend($matches[1]);
    }, $template);

    return $template;

}
