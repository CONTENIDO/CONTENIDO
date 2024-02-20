<?php

/**
 * This file contains the left top frame backend page in upload section.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var cSession $sess
 * @var cTemplate $tpl
 * @var array $cfg
 * @var int $frame
 * @var string $area
 * @var string $upl_last_path Session variable
 */

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.str.php');
cInclude('includes', 'functions.upl.php');

// Display critical error if client or language does not exist
$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
if (($client < 1 || !cRegistry::getClient()->isLoaded()) || ($lang < 1 || !cRegistry::getLanguage()->isLoaded())) {
    $message = $client && !cRegistry::getClient()->isLoaded() ? i18n('No Client selected') : i18n('No language selected');
    $oPage = new cGuiPage("upl_left_top");
    $oPage->displayCriticalError($message);
    $oPage->render();
    return;
}

$appendparameters = $_REQUEST['appendparameters'] ?? '';
$searchfor = cSecurity::escapeString($_REQUEST['searchfor'] ?? '');
$sDisplayPath = cSecurity::escapeString($_REQUEST['path'] ?? '');
$pathstring = cSecurity::escapeString($_REQUEST['pathstring'] ?? '');
$file = cSecurity::escapeString($_REQUEST['file'] ?? '');

$cfgClient = cRegistry::getClientConfig();

$tpl->set('s', 'FORMACTION', '');

$sDisplayPath = generateDisplayFilePath($sDisplayPath, 35);
$tpl->set('s', 'CAPTION2', $sDisplayPath);
$tpl->set('s', 'CREATEALT', i18n('Create'));
$tpl->set('s', 'NOTIFICATION', '');

// Form for 'Search'
$search = new cHTMLTextbox('searchfor', $searchfor, 26);
$search->setClass('align_middle mgr5');
$sSearch = $search->render();

$submitImg = cHTMLButton::image('images/submit.gif', i18n('Search'), ['class' => 'con_img_button align_middle']);
$form = new cHTMLForm('search');
$form->appendContent($sSearch . ' ' . $submitImg);
$form->setVar('area', $area);
$form->setVar('frame', $frame);
$form->setVar('contenido', $sess->id);
$form->setVar('appendparameters', $appendparameters);
$tpl->set('s', 'SEARCHFORM', $form->render());
$tpl->set('s', 'SEARCHTITLE', i18n('Search for'));
$tpl->set('s', 'DISPLAY_SEARCH', 'block');

if ($perm->have_perm_area_action('upl', 'upl_mkdir') && $client > 0) {
    $sCurrentPathInfo = '';
    if (!isset($path) && $sess->isRegistered('upl_last_path')) {
        $path = $upl_last_path;
    }
    $path = $path ?? '';

    if ($path == '' || cApiDbfs::isDbfs($path)) {
        $sCurrentPathInfo = $path;
    } else {
        $sCurrentPathInfo = str_replace($cfgClient[$client]['upl']['path'], '', $path);
    }

    // Form for 'New Directory'
    $tpl->set('s', 'PATH', $path);
    $sessURL = $sess->url("main.php?area=upl_mkdir&frame=2&appendparameters=$appendparameters");
    $tpl->set('s', 'TARGET', 'onsubmit="parent.frames[2].location.href=\'' . $sess->url("main.php?area=upl&action=upl_mkdir&frame=2&appendparameters=$appendparameters") .
        '&path=\'+document.newdir.path.value+\'&foldername=\'+document.newdir.foldername.value;"');
    $tpl->set('s', 'DISPLAY_DIR', 'block');
} else {
    // No permission with current rights
    $tpl->set('s', 'CAPTION', '');
    $tpl->set('s', 'CAPTION2', '');
    $tpl->set('s', 'CREATEALT', i18n('Create'));
    $tpl->set('s', 'TARGET', '');
    $tpl->set('s', 'SUBMIT', '');
    $tpl->set('s', 'ACTION', '');
    $tpl->set('s', 'DISPLAY_DIR', 'none');
}

// Searching
if ($searchfor != '') {
    $items = uplSearch($searchfor);

    $tmp_mstr = 'Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')';
    $mstr = sprintf(
        $tmp_mstr,
        'right_bottom', $sess->url("main.php?area=upl_search_results&frame=4&searchfor=$searchfor&appendparameters=$appendparameters"),
        'right_top', $sess->url("main.php?area=$area&frame=3&appendparameters=$appendparameters")
    );
    $tpl->set('s', 'RESULT', $mstr);
} else {
    $tpl->set('s', 'RESULT', '');
}

$tpl->set('d', 'PATH', $pathstring);
$tpl->next();

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['upl_left_top']);
