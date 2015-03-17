<?php
/**
 * This file contains the backend page for editing articles content.
 *
 * @todo replace code generation by Contenido_CodeGenerator (see
 *       contenido/classes/CodeGenerator)
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Jan Lengowski
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($idcat)) {
    cRegistry::shutdown();
    return;
}

$backendPath = cRegistry::getBackendPath();
$backendUrl = cRegistry::getBackendUrl();

$edit = 'true';
$db2 = cRegistry::getDb();
$scripts = '';
$cssData = '';
$jsData = '';
		
$data = cSecurity::toString($_REQUEST['data']);

if ($action == 20 || $action == 10) {
    if ($data != '') {
        $data = explode('||', substr($data, 0, -2));		
        foreach ($data as $value) {
            $value = explode('|', $value);
            if ($value[3] == '%$%EMPTY%$%') {
                $value[3] = '';
            } else {
                $value[3] = str_replace('%$%SEPERATOR%$%', '|', $value[3]);
            }
            
            conSaveContentEntry($value[0], 'CMS_' . $value[1], $value[2], $value[3]);
        }
       
        $versioning = new cContentVersioning();
        if ($versioning->getState() != 'advanced') {
            conMakeArticleIndex($idartlang, $idart);
        }

        // restore orginal values
        $data = $_REQUEST['data'];
        $value = $_REQUEST['value'];
    }

    conGenerateCodeForArtInAllCategories($idart);
}

$areaCode = '';
if (isset($area) && $area == 'con_content_list') {
    $areaCode = '&area=' . $area;
}
if ($action == 10) {
    header('Location: ' . $backendUrl . $cfg['path']['includes'] . "include.backendedit.php?type=$type&typenr=$typenr&client=$client&lang=$lang&idcat=$idcat&idart=$idart&idartlang=$idartlang&contenido=$contenido&lang=$lang$areaCode");
    return;
}

// @fulai.zhang: Mark submenuitem 'Editor' in the CONTENIDO Backend (Area:
// Contenido --> Articles --> Editor)
$markSubItem = markSubMenuItem(5, true);

// Include tiny class
include($backendPath . 'external/wysiwyg/tinymce3/editorclass.php');
$oEditor = new cTinyMCEEditor('', '');
$oEditor->setToolbar('inline_edit');

// Get configuration for popup und inline tiny
$sConfigInlineEdit = $oEditor->getConfigInlineEdit();
$sConfigFullscreen = $oEditor->getConfigFullscreen();

// Replace vars in Script
$oScriptTpl = new cTemplate();

$oScriptTpl->set('s', 'JS_EDITCONTENT', $markSubItem);

// Set urls to file browsers
$oScriptTpl->set('s', 'IMAGE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'FILE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
$oScriptTpl->set('s', 'FLASH', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'MEDIA', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'FRONTEND', cRegistry::getFrontendUrl());

// Add tiny options
$oScriptTpl->set('s', 'TINY_OPTIONS', $sConfigInlineEdit);
$oScriptTpl->set('s', 'TINY_FULLSCREEN', $sConfigFullscreen);
$oScriptTpl->set('s', 'IDARTLANG', $idartlang);
$oScriptTpl->set('s', 'CLOSE', utf8_encode(html_entity_decode(i18n('Close editor'))));
$oScriptTpl->set('s', 'SAVE', utf8_encode(html_entity_decode(i18n('Close editor and save changes'))));
$oScriptTpl->set('s', 'QUESTION', i18n('Do you want to save changes?'));

if (getEffectiveSetting('system', 'insite_editing_activated', 'true') == 'false') {
    $oScriptTpl->set('s', 'USE_TINY', '');
} else {
    $oScriptTpl->set('s', 'USE_TINY', '1');
}

$scripts = $oScriptTpl->generate($backendPath . $cfg['path']['templates'] . $cfg['templates']['con_editcontent'], 1);

$contentform = '
<form name="editcontent" method="post" action="' . $sess->url($backendUrl . "external/backendedit/front_content.php?area=con_editcontent&idart=$idart&idcat=$idcat&lang=$lang&action=20&client=$client") . '">
<input type="hidden" name="changeview" value="edit">
<input type="hidden" name="idArtLangVersion" value="">
<input type="hidden" name="copyTo" value="">
<input type="hidden" name="data" value="">
</form>
';
$contentform .= '
<form name="copyto" method="post" action="' . $sess->url($backendUrl . "external/backendedit/front_content.php?area=con_editcontent&idart=$idart&idcat=$idcat&lang=$lang&action=copyto&client=$client") . '">
<input type="hidden" name="changeview" value="edit">
<input type="hidden" name="idArtLangVersion" value="">
<input type="hidden" name="data" value="">
</form>        
';

$versioning = new cContentVersioning();
$versioningState = $versioning->getState();
$articleType = $versioning->getArticleType(
                    $_REQUEST['idArtLangVersion'], 
                    (int) $idartlang, 
                    $action
                );
$code = '';
$selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');
$versioningElement = '';

switch ($versioningState) {
    case 'simple' :
        
        // Set as current
        if ($action == 'copyto') {
            if (is_numeric($_REQUEST['idArtLangVersion']) && $articleType == 'current') {
                $artLangVersion = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
                $artLangVersion->markAsCurrent('content');
            }
        }
        $selectedArticle = $versioning->getSelectedArticle($_REQUEST['idArtLangVersion'], $idartlang, $articleType);

        // Get version numbers for Select Element
        $optionElementParameters = $versioning->getDataForSelectElement((int) $idartlang, 'content');

        // Create Current and Editable Content Option Element
        $optionElement = new cHTMLOptionElement(i18n('Published Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

        // Create Content Version Option Elements
        foreach ($optionElementParameters AS $key => $value) {
            $lastModified = $versioning->getTimeDiff($value[key($value)]);
            $optionElement = new cHTMLOptionElement('Version ' . $key . ': ' . $lastModified, key($value));
            if ($_REQUEST['idArtLangVersion'] == key($value) && $articleType != 'current') { 
                $optionElement->setSelected(true);
            }
            $selectElement->appendOptionElement($optionElement);            
        } 
        $selectElement->setEvent("onchange", "editcontent.idArtLangVersion.value=$('#selectVersionElement option:selected').val();editcontent.submit()");
        
        // Create markAsCurrent Button/Label
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', i18n('Copy to Published Version'));
        $markAsCurrentButton->setEvent('onclick', "copyto.idArtLangVersion.value=$('#selectVersionElement option:selected').val();copyto.submit()");
        if ($articleType == 'current' || $articleType == 'editable' && $versioningState == 'simple') {
            $markAsCurrentButton->setAttribute('DISABLED');
        }
        
        $versioning_info_text = i18n("<strong>Mode 'simple':</strong> Older Article Versions can be restored and reviewed "
                . "(Configurations under Administration/System configuration).<br/><br/>Accomplished actions only refer to contents itself!");
        
        // add code
        $versioningElement .=    $versioning->getVersionSelectionField(
                        'editcontentList',
                        $selectElement->toHtml(),
                        $markAsCurrentButton,
                        $versioning_info_text
                    );
        
        break;
    case 'advanced' :
        
        // Set as current/editable
        if ($action == 'copyto') {
            if (is_numeric($_REQUEST['idArtLangVersion']) && $articleType == 'current') {
                $artLangVersion = NULL;                
                $artLangVersion = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
                if (isset($artLangVersion)) {
                    $artLangVersion->markAsCurrent('content');
                }
            } else if (is_numeric($_REQUEST['idArtLangVersion']) && $articleType == 'editable') {
                $artLangVersion = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
                $artLangVersion->markAsEditable('content');
                $articleType = $versioning->getArticleType(
                    $_REQUEST['idArtLangVersion'], 
                    (int) $idartlang, 
                    $action
                );
            } else if ($_REQUEST['idArtLangVersion'] == 'current') {
                $artLang = new cApiArticleLanguage($idartlang);
                $artLang->markAsEditable('content');
                $articleType = $versioning->getArticleType(
                    $_REQUEST['idArtLangVersion'], 
                    (int) $idartlang, 
                    $action
                );
            }     
        }
        
        // load selected article
        $selectedArticle = $versioning->getSelectedArticle((int) $_REQUEST['idArtLangVersion'], $idartlang, $articleType);

         // Get version numbers for Select Element
        $optionElementParameters = $versioning->getDataForSelectElement((int) $idartlang, 'content');
                
        // set elements/buttons
        if (isset($versioning->editableArticleId)) {
            $optionElement = new cHTMLOptionElement(i18n('Draft'), $versioning->getEditableArticleId((int) $idartlang));
            if ($articleType == 'editable') {
                $optionElement->setSelected(true);
            }
            $selectElement->appendOptionElement($optionElement);
            if (count($optionElementParameters) > 0 ) {
                unset($optionElementParameters[max(array_keys($optionElementParameters))]);
            }
        }
        
        $optionElement = new cHTMLOptionElement(i18n('Published Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

        // Create Content Version Option Elements
        foreach ($optionElementParameters AS $key => $value) {
            $lastModified = $versioning->getTimeDiff($value[key($value)]);
            $optionElement = new cHTMLOptionElement('Revision ' . $key . ': ' . $lastModified, key($value));
            if ($articleType == 'version') {
                if ($_REQUEST['idArtLangVersion'] == key($value)) {
                    $optionElement->setSelected(true);
                }
            }
            $selectElement->appendOptionElement($optionElement);
        }
        
        $selectElement->setEvent("onchange", "editcontent.idArtLangVersion.value=$('#selectVersionElement option:selected').val();editcontent.submit()");
        
        // Create markAsCurrent Button
        if ($articleType == 'current' || $articleType == 'version') {
            $buttonTitle = i18n('Copy to Draft');
        } else if ($articleType == 'editable') {
            $buttonTitle = i18n('Publish Draft');
        }
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', $buttonTitle);
        $markAsCurrentButton->setEvent('onclick', "copyto.idArtLangVersion.value=$('#selectVersionElement option:selected').val();copyto.submit()");

        // set info text
        $versioning_info_text = i18n(
                '<strong>Konfigurationsstufe \'advanced\':</strong>  '
                . 'Es kann auf frühere Contentversionen zurückgegriffen werden. '
                . 'Es können äußerdem Entwürfe erstellt und zeitunabhängig veröffentlicht werden (Einstellungen sind in Administration/System/System-Konfiguration möglich).<br/><br/>'
                . 'Hier durchgeführte Aktionen beziehen sich nur auf Contents!');
        
        // add code
        $versioningElement .=    $versioning->getVersionSelectionField(
                        'editcontentList',
                        $selectElement->toHtml(),
                        $markAsCurrentButton,
                        $versioning_info_text
                    );

        break;
    case 'disabled' :
        
        // set elements/buttons
        $optionElement = new cHTMLOptionElement('Version 10: 11.12.13 14:15:16', '');
        $selectElement->appendOptionElement($optionElement);
        $selectElement->setAttribute('disabled', 'disabled');
        
        $buttonTitle = i18n('Copy to Published Version');
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', $buttonTitle);
        $markAsCurrentButton->setAttribute('disabled', 'disabled');
        
        // set info text
        $versioning_info_text = i18n('For reviewing and restoring older Article Versions activate the Article Versioning under Administration/System/System configuration.');
 
        // add code
        $versioningElement .=    $versioning->getVersionSelectionField(
                        'editcontentList',
                        $selectElement->toHtml(),
                        $markAsCurrentButton,
                        $versioning_info_text
                    );
        
        // load selected article
        $selectedArticle = $versioning->getSelectedArticle((int) $_REQUEST['idArtLangVersion'], $idartlang, $articleType);

    default :
        break;
}

// generate article code
if ($selectedArticle != NULL) {
    
    switch ($versioningState) {
        case 'advanced':
            if ($articleType == 'editable') {
                $editable = true;
                $version = $selectedArticle->get('version');
            } else if ($articleType == 'current') {
                $editable = false;
                $version = NULL;
            } else if ($articleType == 'version') {
                $editable = false;
                $version = $selectedArticle->get('version');
            }
            break;
        case 'simple':
             if ($articleType == 'editable' || $articleType == 'current') {
                $editable = true;
                $version = NULL;
            } else if ($articleType == 'version') {
                $editable = false;
                $version = $selectedArticle->get('version');
            }
            break;
        case 'disabled':
            $editable = true;
            $version = NULL;
            break;
        default:
            throw new cException('unknown');
            break;
    }    

    // sets global $edit = false; needed for edit/view output in editor
    if (!$editable) {
        global $edit;
        $edit = false;
    }

    $code .= $versioningElement;
    $code .= conGenerateCode($idcat, $idart, $lang, $client, false, false, true, $editable, $version); 

}


if ($code == "0601") {
    markSubMenuItem("1");
    $code = "<script type='text/javascript'>location.href = '" . $backendUrl . "main.php?frame=4&area=con_editart&action=con_edit&idart=" . $idart . "&idcat=" . $idcat . "&contenido=" . $contenido . "'; /*console.log(location.href);*/</script>";
} else {
    // inject some additional markup
    $code = cString::iReplaceOnce("</head>", "$scripts\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$encoding[$lang]\"></head>", $code);
    $code = cString::iReplaceOnceReverse("</body>", "$contentform</body>", $code);
    $code = cString::iReplaceOnce("<head>", "<head>\n" . '<base href="' . cRegistry::getFrontendUrl() . '">', $code);
}

if ($cfg["debug"]["codeoutput"]) {
    cDebug::out(conHtmlSpecialChars($code));
}

chdir(cRegistry::getFrontendPath());

eval("?>\n" . $code . "\n<?php\n");

cRegistry::shutdown();

?>