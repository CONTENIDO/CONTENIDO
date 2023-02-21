<?php

/**
 * This file contains the backend page for the visual template editor.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $idtpl, $client, $cfg, $db, $lang, $containerinf, $sess, $frame, $area;

cInclude('includes', 'functions.tpl.php');

$idtpl = cSecurity::toInteger($idtpl);
$client = cSecurity::toInteger($client);

$tplLayoutData = tplGetTplAndLayoutData($idtpl);
$idtpl = $tplLayoutData['idtpl'] ?? 0;
$tplname = $tplLayoutData['name'] ?? '';
$description = $tplLayoutData['description'] ?? '';
$idlay = $tplLayoutData['idlay'] ?? 0;
$laydescription = nl2br($tplLayoutData['laydescription'] ?? '');
$defaulttemplate = $tplLayoutData['defaulttemplate' ?? 0];

// Get all modules by clients
$moduleColl = new cApiModuleCollection();
$modules = $moduleColl->getAllByIdclient($client);

// $code = $db->f('code');
$layoutInFile = new cLayoutHandler($idlay, "", $cfg, $lang);
$code = $layoutInFile->getLayoutCode();

// get document version (html or xhtml)
$is_XHTML = getEffectiveSetting('generator', 'xhtml', 'false');
$sElemClosing = ($is_XHTML == 'true') ? ' /' : '';

$base = '<base href="' . cRegistry::getFrontendUrl() . '"' . $sElemClosing . '>';
$tags = $base;

$code = str_replace('<head>', "<head>\n" . $tags . "\n", $code);

$sContainerInHead = '';

// List of configured container
$containerNumbers = tplGetContainerNumbersInLayout($idlay);

// List of used modules in container
$containerModules = conGetUsedModules($idtpl);

foreach ($containerNumbers as $containerNr) {
    if (empty($containerNr)) {
        continue;
    }

    //*************** Loop through containers ****************
    $name = tplGetContainerName($idlay, $containerNr);

    $modselect = new cHTMLSelectElement("c[{$containerNr}]");
    $modselect->setAttribute('title', "Container {$containerNr} ($name)");

    $mode = tplGetContainerMode($idlay, $containerNr);

    if ($mode == 'fixed') {
        $default = tplGetContainerDefault($idlay, $containerNr);

        $option = new cHTMLOptionElement('-- ' . i18n("none") . ' --', 0);
        $modselect->addOptionElement(0, $option);

        foreach ($modules as $key => $val) {
            if ($val['name'] == $default) {
                if (cString::getStringLength($val['name']) > 20) {
                    $shortName = cString::trimHard($val['name'], 20);
                    $option = new cHTMLOptionElement($shortName, $key);
                    $option->setAttribute('title', "Container $containerNr ({$name}) {$val['name']}");
                } else {
                    $option = new cHTMLOptionElement($val['name'], $key);
                    $option->setAttribute('title', "Container $containerNr ({$name})");
                }

                if (isset($containerModules[$containerNr]) && $containerModules[$containerNr] == $key) {
                    $option->setSelected(true);
                }

                $modselect->addOptionElement($key, $option);
            }
        }
    } else {

        $default = tplGetContainerDefault($idlay, $containerNr);

        if ($mode == 'optional' || $mode == '') {
            $option = new cHTMLOptionElement('-- ' . i18n("none") . ' --', 0);

            if (isset($containerModules[$containerNr]) && $containerModules[$containerNr] != 0) {
                $option->setSelected(false);
            } else {
                $option->setSelected(true);
            }

            $modselect->addOptionElement(0, $option);
        }

        $allowedtypes = tplGetContainerTypes($idlay, $containerNr);

        foreach ($modules as $key => $val) {
            $short_name = $val['name'];
            if (cString::getStringLength($val['name']) > 20) {
                $short_name = cString::trimHard($val['name'], 20);
            }

            $option = new cHTMLOptionElement($short_name, $key);

            if (cString::getStringLength($val['name']) > 20) {
                $option->setAttribute('title', "Container $containerNr ({$name}) {$val['name']}");
            }

            if (isset($containerModules[$containerNr]) && ($containerModules[$containerNr] == $key || ($containerModules[$containerNr] == 0 && $val['name'] == $default))) {
                $option->setSelected(true);
            }

            if (count($allowedtypes) > 0) {
                if (in_array($val['type'], $allowedtypes) || $val['type'] == '') {
                    $modselect->addOptionElement($key, $option);
                }
            } else {
                $modselect->addOptionElement($key, $option);
            }
        }
    }

    // visual edit item container
    $sLabelAndSelect = '<label for="' . $modselect->getAttribute('id') . '">' . $containerNr . ':</label>' . $modselect->render();
    $sVisualEditItem = '<div class="visedit_item" onmouseover="this.style.zIndex = \'20\'" onmouseout="this.style.zIndex = \'10\'">' . $sLabelAndSelect . '</div>';

    // collect containers in head for displaying them at the start of body
    if (is_array($containerinf) && isset($containerinf[$idlay]) && isset($containerinf[$idlay][$containerNr]) &&
            isset($containerinf[$idlay][$containerNr]['is_body']) && $containerinf[$idlay][$containerNr]['is_body'] == false) {
        // replace container inside head with empty values and collect the container
        $code = preg_replace("/<container( +)id=\"$containerNr\"(.*)>(.*)<\/container>/Uis", "CMS_CONTAINER[$containerNr]", $code);
        $code = preg_replace("/<container( +)id=\"$containerNr\"(.*)\/>/i", "CMS_CONTAINER[$containerNr]", $code);
        $code = str_ireplace("CMS_CONTAINER[$containerNr]", '', $code);
        $sContainerInHead .= $sVisualEditItem . "\n";
    } else {
        // replace other container
        $code = preg_replace("/<container( +)id=\"$containerNr\"(.*)>(.*)<\/container>/Uis", "CMS_CONTAINER[$containerNr]", $code);
        $code = preg_replace("/<container( +)id=\"$containerNr\"(.*)\/>/i", "CMS_CONTAINER[$containerNr]", $code);
        $code = str_ireplace("CMS_CONTAINER[$containerNr]", $sVisualEditItem, $code);
    }

}

// Get rid of any forms
$code = preg_replace("/<form(.*)>/i", '', $code);
$code = preg_replace("/<\/form(.*)>/i", '', $code);

$backendUrl = cRegistry::getBackendUrl();

$headCode = cHTMLLinkTag::stylesheet($backendUrl . cAsset::backend('styles/jquery/jquery-ui.css')) . PHP_EOL;

$form = '
    <form id="tpl_visedit" name="tpl_visedit" action="' . $backendUrl . 'main.php">
    <input type="hidden" name="' . $sess->name . '" value="' . $sess->id . '"' . $sElemClosing . '>
    <input type="hidden" name="idtpl" value="' . $idtpl . '"' . $sElemClosing . '>
    <input type="hidden" name="frame" value="' . $frame . '"' . $sElemClosing . '>
    <input type="hidden" name="area" value="' . $area . '"' . $sElemClosing . '>
    <input type="hidden" name="description" value="' . $description . '"' . $sElemClosing . '>
    <input type="hidden" name="tplname" value="' . $tplname . '"' . $sElemClosing . '>
    <input type="hidden" name="idlay" value="' . $idlay . '"' . $sElemClosing . '>
    <input type="hidden" name="defaulttemplate" value="' . $defaulttemplate . '"' . $sElemClosing . '>
    <input type="hidden" name="action" value="tpl_visedit"' . $sElemClosing . '>';
$form .= $sContainerInHead;

$sInput = '<input type="image" src="' . $backendUrl . $cfg['path']['images'] . 'but_ok.gif' . '"' . $sElemClosing . '>';
$button = '<table border="0" width="100%"><tr><td align="right">' . $sInput . '</td></tr></table>';
$code = preg_replace("/<\/head(.*)>/i", $headCode . '</head\\1>', $code);
$code = preg_replace("/<body(.*)>/i", "<body\\1>" . $form . $button, $code);
$code = preg_replace("/<\/body(.*)>/i", '</form></body\\1>', $code);

eval("?>\n" . $code . "\n<?php\n");
