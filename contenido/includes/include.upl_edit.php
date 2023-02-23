<?php

/**
 * This file contains the backend page for editing meta information of file in upload section.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

/**
 * @var string $belang
 * @var array $cfg
 * @var array $cfgClient
 * @var int $lang
 * @var int $client
 * @var int $frame
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.upl.php');

$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());

$path = $path ?? '';

// Define local filename variable
$filename = cSecurity::escapeString($_REQUEST['file']);
$filename = str_replace('"', '', $filename);
$filename = str_replace("'", '', $filename);

// Define local pathname variable
$pathname = cSecurity::escapeString($_REQUEST['path']);
$pathname = str_replace('"', '', $pathname);
$pathname = str_replace("'", '', $pathname);

$isZipFile = isArchive($filename);

$page = new cGuiPage('upl_edit');

$page->addStyle('jquery/plugins/timepicker.css');
$page->addScript('jquery/plugins/timepicker.js');
$page->addScript('include.upl_edit.js');

//get language js files
if (($lang_short = cString::getPartOfString(cString::toLowerCase($belang), 0, 2)) != 'en') {
    $page->addScript('jquery/plugins/timepicker-' . $lang_short . '.js');
    $page->addScript('jquery/plugins/datepicker-' . $lang_short . '.js');
}

$form = new cGuiTableForm('properties');
$form->setVar('frame', $frame);
$form->setVar('area', 'upl');
$form->setVar('path', $pathname);
$form->setVar('file', $filename);
$form->setVar('action', 'upl_modify_file');
$form->setVar('startpage', cSecurity::toInteger($_REQUEST['startpage']));
$form->setVar('sortby', cSecurity::escapeString($_REQUEST['sortby']));
$form->setVar('sortmode', cSecurity::escapeString($_REQUEST['sortmode']));
$form->setVar('thumbnailmode', cSecurity::escapeString($_REQUEST['thumbnailmode']));
// $form->setVar('zip', (isArchive( $filename)) ? '1' : '0');
$form->addHeader(i18n('Edit'));

$properties = new cApiPropertyCollection();
$uploads = new cApiUploadCollection();

if (cApiDbfs::isDbfs($_REQUEST['path'])) {
    $qpath = $pathname . '/';
} else {
    $qpath = $pathname;
}

if ((is_writable($cfgClient[$client]['upl']['path'] . $path) || cApiDbfs::isDbfs($path)) && (int) $client > 0) {
    $bDirectoryIsWritable = true;
} else {
    $bDirectoryIsWritable = false;
}

$where = "`idclient` = %d AND `dirname` = '%s' AND `filename` = '%s'";
$where = $uploads->prepare($where, $client, $qpath, $filename);
$uploads->select($where);

if ($upload = $uploads->next()) {

    // Which rows to display?
    $aListRows = [
        'filename'    => i18n('File name'),
        'path'        => i18n('Path'),
        'replacefile' => i18n('Replace file'),
        'medianame'   => i18n('Media name'),
        'description' => i18n('Description'),
        'keywords'    => i18n('Keywords'),
        'medianotes'  => i18n('Internal notes'),
        'copyright'   => i18n('Copyright'),
        'protected'   => i18n('Protection'),
        'timecontrol' => i18n('Time control'),
        'preview'     => i18n('Preview'),
        'author'      => i18n('Author'),
        'modified'    => i18n('Last modified by'),
    ];

    if ($isZipFile) {
        $id = $_GET['user_id'];
        //echo '<input type="button" value="test";

        //if (isset($_SESSION['zip']) && $_SESSION['zip'] === 'extract') {
        //}

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
    $_cecRegistry = cApiCecRegistry::getInstance();
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
                $sCell = $filename;
                break;

            case 'zip':
                $sCell = new cHTMLCheckbox('extractZip', '');
                $sCell->setEvent('onclick', 'show();');
                // $sCell->setClass('ZipExtract');
                break;

            case 'extractFolder':
                $box = new cHTMLTextbox('efolder');
                $box->setID('extractFolder');
                $box->setValue(strstr($filename, '.', TRUE));
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
                    $medianame = $properties->getValue('upload', $qpath . $filename, 'file', 'medianame');
                }

                $mnedit = new cHTMLTextbox('medianame', conHtmlentities($medianame), 60);
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
                    $keywords = $properties->getValue('upload', $qpath . $filename, 'file', 'keywords');
                }

                $kwedit = new cHTMLTextarea('keywords', $keywords);
                $sCell = $kwedit->render();
                break;

            case 'medianotes':
                if ($uploadMeta->get('internal_notice')) {
                    $medianotes = cSecurity::unFilter($uploadMeta->get('internal_notice'));
                } else {
                    $medianotes = $properties->getValue('upload', $qpath . $filename, 'file', 'medianotes');
                }

                $moedit = new cHTMLTextarea('medianotes', $medianotes);
                $sCell = $moedit->render();
                break;

            case 'copyright':
                if ($uploadMeta->get('copyright')) {
                    $copyright = cSecurity::unFilter($uploadMeta->get('copyright'));
                } else {
                    $copyright = $properties->getValue('upload', $qpath . $filename, 'file', 'copyright');
                }

                $copyrightEdit = new cHTMLTextarea('copyright', $copyright);
                $sCell = $copyrightEdit->render();
                break;

            case 'protected':
                $vprotected = $properties->getValue('upload', $qpath . $filename, 'file', 'protected');
                $protected = new cHTMLCheckbox('protected', '1');
                $protected->setChecked($vprotected);
                $protected->setLabelText(i18n('Protected for non-logged in users'));
                $sCell = $protected->render();
                break;

            case 'timecontrol':
                $iTimeMng = (int) $properties->getValue('upload', $qpath . $filename, 'file', 'timemgmt');
                $sStartDate = $properties->getValue('upload', $qpath . $filename, 'file', 'datestart');
                $sEndDate = $properties->getValue('upload', $qpath . $filename, 'file', 'dateend');

                $oTimeCheckbox = new cHTMLCheckbox('timemgmt', '1');
                $oTimeCheckbox->setChecked($iTimeMng);
                $oTimeCheckbox->setLabelText(i18n('Use time control'));

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
                    $sCell = '<a target="_blank" href="' . cRegistry::getFrontendUrl() . "dbfs.php?file=" . $qpath . $filename . '"><img alt="" class="bordered" src="' . uplGetThumbnail($qpath . $filename, 350) . '"></a>';
                } else {
                    $sCell = '<a target="_blank" href="' . $cfgClient[$client]['upl']['htmlpath'] . $qpath . $filename . '"><img alt="" class="bordered" src="' . uplGetThumbnail($qpath . $filename, 350) . '"></a>';
                }
                break;

            case 'author':
                $oUser = new cApiUser($upload->get('author'));
                $sCell = $oUser->get('username') . ' (' . cDate::formatDatetime($upload->get('created')) . ')';
                break;

            case 'modified':
                $oUser = new cApiUser($upload->get('modifiedby'));
                $sCell = $oUser->get('username') . ' (' . cDate::formatDatetime($upload->get('lastmodified')) . ')';
                break;

            default:
                // Call chain to retrieve value
                $_cecIterator = $_cecRegistry->getIterator('Contenido.Upl_edit.RenderRows');

                if ($_cecIterator->count() > 0) {
                    $contents = [];
                    while ($chainEntry = $_cecIterator->next()) {
                        $contents[] = $chainEntry->execute($iIdupl, $qpath, $filename, $sListRow);
                    }
                }
                $sCell = implode('', $contents);
        }
        $form->add($sTitle, $sCell);
    }

    if ($bDirectoryIsWritable == false) {
        $page->displayError(i18n('Directory not writable') . ' (' . $cfgClient[$client]['upl']['path'] . $path . ')');
    }

    $page->set('s', 'FORM', $form->render());
} else {
    $page->displayCriticalError(sprintf(i18n('Could not load file %s'), $filename));
}

$page->render();

/**
 * @param string $fileName
 *
 * @return bool
 */
function isArchive($fileName)
{
    if (cString::getPartOfString(cString::findLastOccurrence($fileName, '.'), 1) === 'zip') {
        return true;
    } else {
        return false;
    }
}
