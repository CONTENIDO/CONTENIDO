<?php

/**
 * This file contains the left top frame backend page for html template management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cTemplate $tpl
 */

global $select;

// Display critical error if client does not exist
$client = cSecurity::toInteger(cRegistry::getClientId());
if ($client < 1 || !cRegistry::getClient()->isLoaded()) {
    $oPage = new cGuiPage("html_tpl_left_top");
    $oPage->displayCriticalError(i18n('No Client selected'));
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

if ($perm->have_perm_area_action($area, "htmltpl_create")) {
    $tmp_mstr = '<div class="top_left_action"><a class="con_func_button addfunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a></div>';
    $mstr = sprintf(
        $tmp_mstr,
        'right_top', $sess->url("main.php?area=htmltpl&frame=3"),
        'right_bottom', $sess->url("main.php?area=htmltpl&frame=4&action=htmltpl_create"),
        i18n("Create HTML template")
    );
    $tpl->set('s', 'NEWSTYLE', $mstr);
} else {
    $tpl->set("s", "NEWSTYLE", '<div class="top_left_action"><a class="con_func_button addfunction_disabled" href="#">' . i18n("No permission to create new files") . '</a></div>');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['html_tpl_left_top']);
