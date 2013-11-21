<?php
/**
 * This file contains the left top frame backend page in upload section.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.str.php');
cInclude('includes', 'functions.upl.php');

$tpl->set('s', 'FORMACTION', '');
$sDisplayPath = '';
if (isset($_REQUEST['path'])) {
    $sDisplayPath = $_REQUEST['path'];
} else {
    $sDisplayPath = $sCurrentPathInfo;
}

//##echo "<pre>$sDisplayPath</pre>";
$sDisplayPath = generateDisplayFilePath($sDisplayPath, 35);
$tpl->set('s', 'CAPTION2', $sDisplayPath);

// Display notification, if there is no client
if ((int) $client == 0) {
    $sNoClientNotification = '<div class="leftTopAction>' . i18n('No Client selected') . '</div>';
    $tpl->set('s', 'NOTIFICATION', $sNoClientNotification);
} else {
    $tpl->set('s', 'NOTIFICATION', '');
}

// Form for 'Search'
if ($appendparameters != 'filebrowser' && (int) $client > 0) {
    $search = new cHTMLTextbox('searchfor', $_REQUEST['searchfor'], 26);
    $search->setClass('text_small vAlignMiddle');
    $sSearch = $search->render();

    $form = new cHTMLForm('search');
    $form->appendContent($sSearch . ' <input class="vAlignMiddle tableElement" type="image" src="images/submit.gif">');
    $form->setVar('area', $area);
    $form->setVar('frame', $frame);
    $form->setVar('contenido', $sess->id);
    $form->setVar('appendparameters', $appendparameters);
    $tpl->set('s', 'SEARCHFORM', $form->render());
    $tpl->set('s', 'SEARCHTITLE', i18n('Search for'));
    $tpl->set('s', 'DISPLAY_SEARCH', 'block');
} else {
    $tpl->set('s', 'SEARCHFORM', '');
    $tpl->set('s', 'SEARCHTITLE', '');
    $tpl->set('s', 'DISPLAY_SEARCH', 'none');
}

if ($perm->have_perm_area_action('upl', 'upl_mkdir') && (int) $client > 0) {
    $sCurrentPathInfo = '';
    if ($sess->isRegistered('upl_last_path') && !isset($path)) {
        $path = $upl_last_path;
    }

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
    $inputfield = '';
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
    $refreshMenu = "\n" . 'if (Con.getFrame(\'left_bottom\')) { Con.getFrame(\'left_bottom\').refreshMenu(); }';
    $tpl->set('s', 'RESULT', $mstr . $refreshMenu);
} else {
    $tpl->set('s', 'RESULT', '');
}

// Create javascript multilink
$tmp_mstr = '<a href="javascript:Con.multiLink(\'%s\', \'%s\',\'%s\', \'%s\')">%s</a>';
$mstr = sprintf(
    $tmp_mstr,
    'right_top', $sess->url("main.php?area=$area&frame=3&path=$pathstring&appendparameters=$appendparameters"),
    'right_bottom', $sess->url("main.php?area=$area&frame=4&path=$pathstring&appendparameters=$appendparameters"),
    '<img src="images/ordner_oben.gif" align="middle" alt="" border="0"><img align="middle" src="images/spacer.gif" width="5" border="0">' . $file
);

$tpl->set('d', 'PATH', $pathstring);
$tpl->set('d', 'BGCOLOR', $bgcolor);
$tpl->set('d', 'INDENT', 3);
$tpl->set('d', 'DIRNAME', $mstr);
$tpl->set('d', 'EDITBUTTON', '');
$tpl->set('d', 'COLLAPSE', '');
$tpl->next();

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['upl_left_top']);
