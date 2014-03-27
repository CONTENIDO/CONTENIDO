<?php
/**
 * This file contains the backend page for editing meta information of file in upload section.
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

cInclude('includes', 'functions.upl.php');

$isZipFile = isArchive($_REQUEST['file']);

$page = new cGuiPage('upl_edit');

$page->addStyle('jquery/plugins/timepicker.css');
$page->addScript('jquery/plugins/timepicker.js');
$page->addScript('include.upl_edit.js');

//get language js files
if (($lang_short = substr(strtolower($belang), 0, 2)) != 'en') {
    $page->addScript('jquery/plugins/timepicker-' . $lang_short . '.js');
    $page->addScript('jquery/plugins/datepicker-' . $lang_short . '.js');
}

$form = new cGuiTableForm('properties');
$form->setVar('frame', $frame);
$form->setVar('area', 'upl');
$form->setVar('path', $_REQUEST['path']);
$form->setVar('file', $_REQUEST['file']);
$form->setVar('action', 'upl_modify_file');
$form->setVar('startpage', $_REQUEST['startpage']);
$form->setVar('sortby', $_REQUEST['sortby']);
$form->setVar('sortmode', $_REQUEST['sortmode']);
$form->setVar('thumbnailmode', $_REQUEST['thumbnailmode']);
// $form->setVar('zip', (isArchive( $_REQUEST['file'])) ? '1' : '0');
$form->addHeader(i18n('Edit'));

$properties = new cApiPropertyCollection();
$uploads = new cApiUploadCollection();

if (cApiDbfs::isDbfs($_REQUEST['path'])) {
    $qpath = $_REQUEST['path'] . '/';
} else {
    $qpath = $_REQUEST['path'];
}

if ((is_writable($cfgClient[$client]['upl']['path'] . $path) || cApiDbfs::isDbfs($path)) && (int) $client > 0) {
    $bDirectoryIsWritable = true;
} else {
    $bDirectoryIsWritable = false;
}

$uploads->select("idclient = '" . $client . "' AND dirname = '" . $qpath . "' AND filename='" . $_REQUEST['file'] . "'");

if ($upload = $uploads->next()) {

    // Which rows to display?
    $aListRows = array();
    $aListRows['filename'] = i18n('File name');
    $aListRows['path'] = i18n('Path');
    $aListRows['replacefile'] = i18n('Replace file');
    $aListRows['medianame'] = i18n('Media name');
    $aListRows['description'] = i18n('Description');
    $aListRows['keywords'] = i18n('Keywords');
    $aListRows['medianotes'] = i18n('Internal notes');
    $aListRows['copyright'] = i18n('Copyright');
    $aListRows['protected'] = i18n('Protection');
    $aListRows['timecontrol'] = i18n('Time control');
    $aListRows['preview'] = i18n('Preview');
    $aListRows['author'] = i18n('Author');
    $aListRows['modified'] = i18n('Last modified by');

    if ($isZipFile) {
        $id = $_GET['user_id'];
        //echo '<input type="button" value="test";

        if (isset($_SESSION['zip']) && $_SESSION['zip'] === 'extract') {

        }

        $link = new cHTMLLink();
        $link->appendContent(i18n('extract'));
        $aListRows['zip'] = $link;
    }
    ($isZipFile) ? $aListRows['extractFolder'] = '<label class="ZipExtract">' . i18n('extractTo') . '</label>' : '';

    // Delete dbfs specific rows
    if (!cApiDbfs::isDbfs($_REQUEST['path'])) {
        unset($aListRows['protected']);
        unset($aListRows['timecontrol']);
    }

    // Call chains to process the rows
    $_cecIterator = $_cecRegistry->getIterator('Contenido.Upl_edit.Rows');
    if ($_cecIterator->count() > 0) {
        while ($chainEntry = $_cecIterator->next()) {
            $newRowList = $chainEntry->execute($aListRows);
            if (is_array($newRowList)) {
                $aListRows = $newRowList;
            }
        }
    }

    $iIdupl = $upload->get('idupl');

    $uploadMeta = new cApiUploadMeta();
    $uploadMeta->loadByUploadIdAndLanguageId($iIdupl, $lang);

    // Add rows to $form
    foreach ($aListRows as $sListRow => $sTitle) {
        $sCell = '';
        switch ($sListRow) {
            case 'filename':
                $sCell = $_REQUEST['file'];
                break;

            case 'zip':
                $sCell = new cHTMLCheckbox('extractZip', '');
                $sCell->setEvent('onclick', 'show();');
                // $sCell->setClass('ZipExtract');
                break;

            case 'extractFolder':
                $box = new cHTMLTextbox('efolder');
                $box->setID('extractFolder');
                $box->setValue(strstr($_REQUEST['file'], '.', TRUE));
                $box->setClass('ZipExtract');
                $sCell = $box;
                $checkbox = new cHTMLCheckbox('overwrite', i18n('overwrite'));
                $checkbox->setID('overwrite');
                $checkbox->setLabelText(i18n('overwrite'));
                $checkbox->setClass('ZipExtract');

                $sCell .= $checkbox;
                break;

            case 'path':
                $sCell = generateDisplayFilePath($qpath, 65);
                break;

            case 'replacefile':
                $uplelement = new cHTMLUpload('file', 40);
                $uplelement->setDisabled(!$bDirectoryIsWritable);
                $sCell = $uplelement->render();
                break;

            case 'medianame':
                if ($uploadMeta->get('medianame')) {
                    $medianame = cSecurity::unFilter($uploadMeta->get('medianame'));
                } else {
                    $medianame = $properties->getValue('upload', $qpath . $_REQUEST['file'], 'file', 'medianame');
                }

                $mnedit = new cHTMLTextbox('medianame', $medianame, 60);
                $sCell = $mnedit->render();
                break;

            case 'description':
                if ($uploadMeta->get('description')) {
                    $description = cSecurity::unFilter($uploadMeta->get('description'));
                } else {
                    $description = '';
                }

                $dsedit = new cHTMLTextarea('description', $description);
                $sCell = $dsedit->render();
                break;

            case 'keywords':
                if ($uploadMeta->get('keywords')) {
                    $keywords = cSecurity::unFilter($uploadMeta->get('keywords'));
                } else {
                    $keywords = $properties->getValue('upload', $qpath . $_REQUEST['file'], 'file', 'keywords');
                }

                $kwedit = new cHTMLTextarea('keywords', $keywords);
                $sCell = $kwedit->render();
                break;

            case 'medianotes':
                if ($uploadMeta->get('internal_notice')) {
                    $medianotes = cSecurity::unFilter($uploadMeta->get('internal_notice'));
                } else {
                    $medianotes = $properties->getValue('upload', $qpath . $_REQUEST['file'], 'file', 'medianotes');
                }

                $moedit = new cHTMLTextarea('medianotes', $medianotes);
                $sCell = $moedit->render();
                break;

            case 'copyright':
                if ($uploadMeta->get('copyright')) {
                    $copyright = cSecurity::unFilter($uploadMeta->get('copyright'));
                } else {
                    $copyright = $properties->getValue('upload', $qpath . $_REQUEST['file'], 'file', 'copyright');
                }

                $copyrightEdit = new cHTMLTextarea('copyright', $copyright);
                $sCell = $copyrightEdit->render();
                break;

            case 'protected':
                $vprotected = $properties->getValue('upload', $qpath . $_REQUEST['file'], 'file', 'protected');
                $protected = new cHTMLCheckbox('protected', '1');
                $protected->setChecked($vprotected);
                $protected->setLabelText(i18n('Protected for non-logged in users'));
                $sCell = $protected->render();
                break;

            case 'timecontrol':
                $iTimeMng = (int) $properties->getValue('upload', $qpath . $_REQUEST['file'], 'file', 'timemgmt');
                $sStartDate = $properties->getValue('upload', $qpath . $_REQUEST['file'], 'file', 'datestart');
                $sEndDate = $properties->getValue('upload', $qpath . $_REQUEST['file'], 'file', 'dateend');

                $oTimeCheckbox = new cHTMLCheckbox('timemgmt', i18n('Use time control'));
                $oTimeCheckbox->setChecked($iTimeMng);

                $sHtmlTimeMng = $oTimeCheckbox->render();
                $sHtmlTimeMng .= "<table id='dbfsTimecontrol' class='borderless' border='0' cellpadding='0' cellspacing='0'>\n";
                $sHtmlTimeMng .= "<tr><td><label for='datestart'>" . i18n('Start date') . "</label></td>\n";
                $sHtmlTimeMng .= '<td><input type="text" name="datestart" id="datestart" value="' . $sStartDate . '"  size="20" maxlength="40" class="text_medium">' .
                        '</td></tr>';
                $sHtmlTimeMng .= "<tr><td><label for='dateend'>" . i18n('End date') . "</label></td>\n";
                $sHtmlTimeMng .= '<td><input type="text" name="dateend" id="dateend" value="' . $sEndDate . '"  size="20" maxlength="40" class="text_medium">' .
                        '</td></tr>';
                $sHtmlTimeMng .= "</table>\n";

                $sCell = $sHtmlTimeMng;
                break;

            case 'preview':
                if (cApiDbfs::isDbfs($_REQUEST['path'])) {
                    $sCell = '<a target="_blank" href="' . $sess->url(cRegistry::getFrontendUrl() . "dbfs.php?file=" . $qpath . $_REQUEST['file']) . '"><img class="bordered" src="' . uplGetThumbnail($qpath . $_REQUEST['file'], 350) . '"></a>';
                } else {
                    $sCell = '<a target="_blank" href="' . $cfgClient[$client]['upl']['htmlpath'] . $qpath . $_REQUEST['file'] . '"><img class="bordered" src="' . uplGetThumbnail($qpath . $_REQUEST['file'], 350) . '"></a>';
                }
                break;

            case 'author':
                $oUser = new cApiUser($upload->get('author'));
                $sCell = $oUser->get('username') . ' (' . displayDatetime($upload->get('created')) . ')';
                break;

            case 'modified':
                $oUser = new cApiUser($upload->get('modifiedby'));
                $sCell = $oUser->get('username') . ' (' . displayDatetime($upload->get('lastmodified')) . ')';
                break;

            default:
                // Call chain to retrieve value
                $_cecIterator = $_cecRegistry->getIterator('Contenido.Upl_edit.RenderRows');

                if ($_cecIterator->count() > 0) {
                    $contents = array();
                    while ($chainEntry = $_cecIterator->next()) {
                        $contents[] = $chainEntry->execute($iIdupl, $qpath, $_REQUEST['file'], $sListRow);
                    }
                }
                $sCell = implode('', $contents);
        }
        $form->add($sTitle, $sCell);
    }

    if ($bDirectoryIsWritable == false) {
        $pager->displayError(i18n('Directory not writable') . ' (' . $cfgClient[$client]['upl']['path'] . $path . ')');
    }

    $page->set('s', 'FORM', $form->render());
} else {
    $page->displayCriticalError(sprintf(i18n('Could not load file %s'), $_REQUEST['file']));
}

$page->render();

function isArchive($fileName) {

    if (substr(strrchr($fileName, '.'), 1) === 'zip') {
        return true;
    } else {
        return false;
    }
}
