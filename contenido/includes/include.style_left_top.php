<?php

/**
 * This file contains the left top frame backend page for style management.
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

/**
 * @var cTemplate $tpl
 */

global $select;

$oClient = cRegistry::getClient();

// Display critical error if client does not exist
if (!$oClient->isLoaded()) {
    $oPage = new cGuiPage("style_left_top");
    $oPage->displayCriticalError(i18n('No Client selected'));
    $oPage->render();
    return;
}

$client = cSecurity::toInteger(cRegistry::getClientId());

$sess = cRegistry::getSession();
$perm = cRegistry::getPerm();
$cfg = cRegistry::getConfig();
$area = cRegistry::getArea();

$tpl->set('s', 'ID', 'oTplSel');
$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'OPTIONS', '');
$tpl->set('s', 'CAPTION', '');
$tpl->set('s', 'ACTION', $select);

if ($perm->have_perm_area_action($area, "style_create")) {
    $tmp_mstr = '<div class="leftTopAction"><a class="addfunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a></div>';
    $mstr = sprintf(
        $tmp_mstr,
        'right_top', $sess->url("main.php?area=style&frame=3"),
        'right_bottom', $sess->url("main.php?area=style&frame=4&action=style_create"),
        i18n("Create style")
    );
    $tpl->set('s', 'NEWSTYLE', $mstr);
} else {
    $tpl->set("s", "NEWSTYLE", '<div class="leftTopAction"><a class="addfunction_disabled" href="#">' . i18n("No permission to create new styles") . '</a></div>');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['style_left_top']);
