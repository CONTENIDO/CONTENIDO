<?php

/**
 * This file contains the left top frame backend page for javascript management.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Willi Man
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cTemplate $tpl
 */

global $select;

$client = cSecurity::toInteger(cRegistry::getClientId());

// Display critical error if no valid client is selected
if ($client < 1) {
    $oPage = new cGuiPage('js_left_top');
    $oPage->displayCriticalError(i18n("No Client selected"));
    $oPage->render();
    return;
}

$sess = cRegistry::getSession();
$perm = cRegistry::getPerm();
$cfg = cRegistry::getConfig();
$area = cRegistry::getArea();

$tpl->set('s', 'ID', 'oTplSel');
$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'OPTIONS', '');
$tpl->set('s', 'CAPTION', '');
$tpl->set('s', 'ACTION', $select);

if ($perm->have_perm_area_action($area, "js_create")) {
    $tmp_mstr = '<div class="leftTopAction"><a class="addfunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a></div>';
    $mstr = sprintf(
        $tmp_mstr,
        'right_top', $sess->url("main.php?area=js&frame=3"),
        'right_bottom', $sess->url("main.php?area=js&frame=4&action=js_create"),
        i18n("Create script")
    );
    $tpl->set('s', 'NEWSCRIPT', $mstr);
} else {
    $tpl->set("s", "NEWSCRIPT", '<div class="leftTopAction"><a class="addfunction_disabled" href="#">' . i18n("No permission to create new scripts") . '</a></div>');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['js_left_top']);
