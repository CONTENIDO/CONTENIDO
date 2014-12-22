<?php
/**
 * This file contains the backend page for editing meta tags.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Fulai Zhang
 * @author ilja.schwarz@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.str.php');
cInclude('includes', 'functions.pathresolver.php');

// ugly globals that are used in this script
global $tpl, $cfg, $db, $perm, $sess, $auth;
global $frame, $area, $contenido, $notification;
global $client, $lang, $belang;
global $idcat, $idart, $idcatlang, $idcatart, $idtpl;
global $syncoptions, $tmp_notification;

// Reset template
$tpl->reset();

// Check permissions
if (!$perm->have_perm_area_action($area, 'con_meta_edit') && !$perm->have_perm_area_action_item($area, 'con_meta_edit', $idcat)) {
    // User has no permission to see this form
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

$versioning = new cContentVersioning();

// Get article (version) data
$art = new cApiArticleLanguage();
$art->loadByArticleAndLanguageId(cSecurity::toInteger($idart), cSecurity::toInteger($lang));

if ($_REQUEST['idArtLangVersion'] == NULL && $versioning->getState() == 'advanced') {
    
    $art = new cApiArticleLanguageVersion($versioning->getEditableArticleId($art->getField('idartlang')));
    //$art = new cApiArticleLanguageVersion($versioning->getEditableArticleId($idArtLang));
    
} else if ($versioning->getState() == 'advanced' && $_REQUEST['idArtLangVersion'] != 'current'
    || $versioning->getState() == 'simple' && ($_REQUEST['idArtLangVersion'] != NULL 
    && is_numeric ($_REQUEST['idArtLangVersion']) || is_numeric ($_REQUEST['idArtLangVersion']))) {
    
    $art = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
    
}

$articleType = $versioning->getArticleType(
        $_REQUEST['idArtLangVersion'],
        $art->getField('idartlang'),
        $action
);

// Check is form edit available
$disabled = '';
if ($art->getField('created')) {
    // Get cApiInUseCollection
    $col = new cApiInUseCollection();

    // Remove all own marks
    $col->removeSessionMarks($sess->id);
    $obj = $col->checkMark('article', $art->getField('idartlang'));

    if ($obj === false || $obj->get('userid') == $auth->auth['uid']) {
        $col->markInUse('article', $art->getField('idartlang'), $sess->id, $auth->auth['uid']);
        $disabled = '';
    } else {
        $vuser = new cApiUser($obj->get('userid'));
        $inUseUser = $vuser->getField('username');
        $inUseUserRealName = $vuser->getField('realname');
        $disabled = 'disabled="disabled"';

        $message = sprintf(i18n('Article is in use by %s (%s)'), $inUseUser, $inUseUserRealName);
        $notification->displayNotification('warning', $message);
        $tpl->set("s", "REASON", sprintf(i18n('Article is in use by %s (%s)'), $inUseUser, $inUseUserRealName));
    }

    if ($art->getField('locked') == 1) {
        $disabled = 'disabled="disabled"';
        $tpl->set('s', 'DISABLED', ' ' . $disabled);
        $notification->displayNotification('warning', i18n('This article is currently frozen and can not be edited!'));
        $tpl->set("s", "REASON", i18n('This article is currently frozen and can not be edited!'));
    } else if ($versioning->getState() == 'advanced' && $articleType == 'editable'
        || $versioning->getState() == 'simple' && $articleType != 'version'
        || $versioning->getState() == 'disabled'){
        $tpl->set('s', 'DISABLED', '');
        $tpl->set("s", "REASON", "");
    } else {
        $disabled = 'disabled="disabled"';
        $tpl->set('s', 'DISABLED', ' ' . $disabled);
    }
    
    if ($disabled == '') {
        $tpl->set('s', 'IS_DATETIMEPICKER_DISABLED', 0);
        $tpl->set('s', 'BUTTONIMAGE', 'but_ok.gif');
        $tpl->set('s', 'BUTTONDISABLE', $disabled);
        $tpl->set("s", "REASON", "");
    } else {
        $tpl->set('s', 'IS_DATETIMEPICKER_DISABLED', 1);
        $tpl->set('s', 'BUTTONIMAGE', 'but_ok_off.gif');
        $tpl->set('s', 'BUTTONDISABLE', $disabled);
        $tpl->set("s", "REASON", "");
    }    
    
}

// Assign head values to page
$lang_short = substr(strtolower($belang), 0, 2);
$langscripts = '';
if ($lang_short != 'en') {
    $langscripts = '
<script type="text/javascript" src="scripts/jquery/plugins/timepicker-' . $lang_short . '.js"></script>
<script type="text/javascript" src="scripts/jquery/plugins/datepicker-' . $lang_short . '.js"></script>
    ';
}
$tpl->set('s', 'CAL_LANG', $langscripts);
$tpl->set('s', 'DISPLAY_MENU', 1);
$tpl->set('s', 'SYNCOPTIONS', -1);

// Assign body values
$breadcrumb = renderBackendBreadcrumb($syncoptions, true, true);
$tpl->set('s', 'CATEGORY', $breadcrumb);

// Assign form values
$tpl->set('s', 'ACTION', $sess->url('main.php?area=' . $area . '&frame=' . $frame . '&action=con_meta_saveart'));

// Assign form hidden elements values
$tpl->set('s', 'IDART', $idart);
$tpl->set('s', 'IDCAT', $idcat);
$tpl->set('s', 'TMP_FIRSTEDIT', $art->getField('created') ? 0 : 1);
$tpl->set('s', 'IDARTLANG', $art->getField('idartlang'));
$tpl->set('s', 'TITEL', i18n('SEO administration'));

// Assign notification
if (isset($tmp_notification)) {       
    $tpl->set('s', 'NOTIFICATION', '<tr><td colspan="4">' . $tmp_notification . '<br></td></tr>');
} else {
    $tpl->set('s', 'NOTIFICATION', '');
}

// Assign form page seo elements values
$tpl->set('s', 'LINK', $art->getLink());
$tpl->set('s', 'FULL_LINK', $cfgClient[$client]['path']['htmlpath'] . $art->getLink());

$tpl->set('s', 'PAGE_TITLE', conHtmlSpecialChars(cSecurity::unFilter(stripslashes($art->getField('pagetitle')))));

$tpl->set('s', 'ALIAS', cSecurity::unFilter(stripslashes($art->getField('urlname'))));

// Assign Meta-Tags elements
$availableTags = conGetAvailableMetaTagTypes();
$managedTypes = array(
    'author',
    'description',
    'expires',
    'keywords',
    'revisit-after',
    'robots',
    'copyright'
);

$metaPreview = array();

// Set meta tags values
foreach ($availableTags as $key => $value) {   
    
    $metaPreview[] = array(
        'fieldname' => $value['fieldname'],
        'name' => $value['metatype'],
        'content' => cSecurity::unFilter(stripslashes(conGetMetaValue($art->getField('idartlang'), $key, $art->getField('version'))))
    );

    // Set meta values to inputs
    if (in_array($value['metatype'], $managedTypes)) {
        if ($value['metatype'] == 'robots') {
            if (conGetMetaValue($art->getField('idartlang'), $key) == '') {
                conSetMetaValue($art->getField('idartlang'), $key, 'index, follow');

                $i = 0;
                foreach ($metaPreview as $k => $metaRow) {
                    if ($metaRow['name'] == 'robots') {
                        $metaPreview[$i]['content'] = 'index, follow';
                        break;
                    }

                    $i++;
                }
            }

            // Set robots checkboxes
            $robot_array = explode(', ', conHtmlSpecialChars(conGetMetaValue($art->getField('idartlang'), $key, $art->getField('version'))));
            foreach ($robot_array as $instruction) {
                $tpl->set('s', strtoupper($instruction), 'checked');
            }
        } else {
            $metaType = conHtmlSpecialChars(cSecurity::unFilter(stripslashes(conGetMetaValue($art->getField('idartlang'), $key, $art->getField('version')))));
            $tpl->set('s', strtoupper($value['metatype']), str_replace('\\', '', $metaType));
        }
        
        continue;        
    }
    
    // Create add and edit MetaTag form BLOCK
    $tpl->set('d', 'METAINPUT', 'META' . $value);
    switch ($value['fieldtype']) {
        case 'text':
            $element = '<input ' . $disabled . '
                            class="metaTag"
                            type="text"
                            name="META' . $value['metatype'] . '"
                            id="META' . $value['metatype'] . '"
                            size="24"
                            maxlength=' . $value['maxlength'] . '
                            value="' . conHtmlSpecialChars(conGetMetaValue($art->getField('idartlang'), $key, $art->getField('version'))) . '">';

            break;
        case 'textarea':
            $textValue = cSecurity::unFilter(stripslashes(conGetMetaValue($art->getField('idartlang'), $key, $art->getField('version'))));
            $element = '<textarea ' . $disabled . '
                            class="metaTag"
                            name="META' . $value['metatype'] . '"
                            id="META' . $value['metatype'] . '"
                            rows="3"
                        >' . $textValue . '</textarea>';
            break;
        case 'date':
            $element = '<input ' . $disabled . '
                            class="metaTag datepickerTextbox"
                            type="text"
                            name="META' . $value['metatype'] . '"
                            id="META' . $value['metatype'] . '"
                            size="24"
                            maxlength=' . $value['maxlength'] . '
                            value="' . conHtmlSpecialChars(conGetMetaValue($art->getField('idartlang'), $key, $art->getField('version'))) . '">';

            break;
    }

    $tpl->set('d', 'ARTICLE_LANGUAGE_ID', $art->getField('idartlang'));
    $tpl->set('d', 'ARTICLE_ID', $idart);
    $tpl->set('d', 'CAT_ID', $idcat);
    $tpl->set('d', 'METAFIELDTYPE', $element);
    $tpl->set('d', 'IDMETATYPE', $value['idmetatype']);
    $tpl->set('d', 'METATITLE', $value['metatype'] . ':');
    $tpl->set('d', 'DELETE_META_CONFIRM', i18n('Are you sure to delete this Meta tag?'));
    
    /*$tpl->set('d', 'METAFIELDTYPE', $element);
    $tpl->set('d', 'METATITLE', $value['metatype'] . ':');
    if ($versioning->getState() == 'simple' && $articleType == 'current'
            || $versioning->getState() == 'advanced' && $articleType == 'editable') {
        
        $tpl->set('d', 'DELETE_META', 
            "Con.showConfirmation('" .
                    i18n('Are you sure to delete this Meta tag?') . "' ,
                    function() { 
                        deleteMetaTag(" . $value['idmetatype'] . "," . $idart . "," . $idcat . "," . $art->getField('idartlang') . ");                 
                    }
            ); "
        );
        echo "if...";
    } else {
        
        $tpl->set('d', 'DELETE_META', '');
                echo "else...";
    }*/
    $tpl->next();
}

$tpl->set('s', 'SITEMAP_PRIO', $art->getField('sitemapprio'));

switch ($versioning->getState()) {
    
    case 'advanced':
        
        // Set as current/editable
        if ($action == 'copyto') {
            if (is_numeric($_REQUEST['idArtLangVersion']) && $articleType == 'current') {
                // editable->current
                $artLangVersion = NULL;                
                $artLangVersion = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
                if (isset($artLangVersion)) {
                    $artLangVersion->markAsCurrent('meta');
                }
                
            } else if (is_numeric($_REQUEST['idArtLangVersion']) && $articleType == 'editable') {
                // version->editable
                $artLangVersion = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
                $artLangVersion->markAsEditable('meta');
                $articleType = $versioning->getArticleType($_REQUEST['idArtLangVersion'], (int) $_REQUEST['idartlang'], $action);

            } else if ($_REQUEST['idArtLangVersion'] == 'current') {
                // current->editable
                $artLang = new cApiArticleLanguage((int) $_REQUEST['idartlang']);
                $artLang->markAsEditable('meta');
                $articleType = $versioning->getArticleType($_REQUEST['idArtLangVersion'], (int) $_REQUEST['idartlang'], $action);

            }
        }
    
        $optionElementParameters = $versioning->getDataForSelectElement($art->getField('idartlang'), 'seo');

        // set editable element 
        $selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');
        if (isset($versioning->editableArticleId)) {
            $optionElement = new cHTMLOptionElement(i18n('Editable Version'), $versioning->getEditableArticleId($art->getField('idartlang')));
            if ($articleType == 'editable') {
                $optionElement->setSelected(true);
            }
            $selectElement->appendOptionElement($optionElement);
            unset($optionElementParameters[max(array_keys($optionElementParameters))]);
        }

        // Create Metatag Version Option Elements
        $optionElement = new cHTMLOptionElement(i18n('Current Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

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
         $selectElement->setEvent("onchange", "selectVersion.idArtLangVersion.value=$('#selectVersionElement option:selected').val();selectVersion.submit()");

        $tpl->set('s', 'SELECT_ELEMENT', $selectElement->toHtml());
        $tpl->set("s", "ACTION2", $sess->url('main.php?area=' . $area . '&frame=' . $frame . '&action=con_meta_change_version'));
        $tpl->set("s", "ACTION3", $sess->url('main.php?area=' . $area . '&frame=' . $frame . '&action=copyto'));

        // Create markAsCurrent Button
        if ($articleType == 'current' || $articleType == 'version') {
            $buttonTitle = i18n('Copy to Editable Version');
        } else if ($articleType == 'editable') {
            $buttonTitle = i18n('Publish Draft');
        }
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', $buttonTitle, 'copytobutton');
        $tpl->set('s', 'SET_AS_CURRENT_VERSION', $markAsCurrentButton->toHtml());
        
        break;
    case 'simple' :
        
        if ($action == 'copyto') {            
            if (is_numeric($_REQUEST['idArtLangVersion'])) {                
                $artLangVersion = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
                $artLangVersion->markAsCurrent('meta');                
            }            
        }
    
        $optionElementParameters = $versioning->getDataForSelectElement($art->getField('idartlang'), 'seo');

        // Create Metatag Version Option Elements
        $selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');
        $optionElement = new cHTMLOptionElement(i18n('Current Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

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
         $selectElement->setEvent("onchange", "selectVersion.idArtLangVersion.value=$('#selectVersionElement option:selected').val();selectVersion.submit()");

        $tpl->set('s', 'SELECT_ELEMENT', $selectElement->toHtml());
        $tpl->set("s", "ACTION2", $sess->url('main.php?area=' . $area . '&frame=' . $frame . '&action=con_meta_change_version'));
        $tpl->set("s", "ACTION3", $sess->url('main.php?area=' . $area . '&frame=' . $frame . '&action=copyto'));


        // Create markAsCurrent Button
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', i18n('Copy to Current Version'), 'copytobutton');
        if ($articleType == 'current' || $articleType == 'editable' && $versioningState == 'simple') {
            $markAsCurrentButton->setAttribute('DISABLED');
        }
        $tpl->set('s', 'SET_AS_CURRENT_VERSION', $markAsCurrentButton->toHtml());
    
        break;        
    case 'disabled' :

        // Create Sample Metatag Version Option Element
        $selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');
        $optionElement = new cHTMLOptionElement('Version 10: 11.12.13 14:15:16', '');
        $selectElement->appendOptionElement($optionElement);
        $selectElement->setAttribute('disabled', 'disabled');
        $tpl->set('s', 'SELECT_ELEMENT', $selectElement->toHtml());

        $buttonTitle = i18n('Copy to Current Version');
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', $buttonTitle);
        $markAsCurrentButton->setAttribute('disabled', 'disabled');
        $tpl->set('s', 'SET_AS_CURRENT_VERSION', $markAsCurrentButton->toHtml());

        $versioning_info_text = i18n('Aktiviere die Artikel-Versionierung in den Administration/System/System-Konfiguration. Artikel-Versionierung bedeutet, dass auf frühere Versionen eines Artikels zurückgegriffen werden kann.');
        $tpl->set('s', 'VERSIONING_INFO_TEXT', $versioning_info_text);  
        
    default :
        break;
    
}

$infoButton = new cGuiBackendHelpbox(i18n('The title-tag is one of the most important on-page factors for SEO and is not longer than 60 characters. It includes top keywords and the branding.'));
$tpl->set("s", "INFO_BUTTON_PAGE_TITLE", $infoButton->render());

$infoButton->setHelpText(i18n('Infos, was bei Auswahl/Button passiert...'));
$tpl->set("s", "INFO_BUTTON_VERSION_SELECTION", $infoButton->render());

$infoButton->setHelpText(i18n('The description-tag describes the article in a short way (not more than 150 characters). The content should be related to the title-tag and the H1-tag.'));
$tpl->set("s", "INFO_BUTTON_DESCRIPTION", $infoButton->render());

$infoButton->setHelpText(i18n('No more than 6 Keywords should be used.'));
$tpl->set("s", "INFO_BUTTON_KEYWORDS", $infoButton->render());

$infoButton->setHelpText(i18n('The frequency of the revisit after tag depends on new publications of the content. Nevertheless the robots decide on their own when to visit.'));
$tpl->set("s", "INFO_BUTTON_REVISIT", $infoButton->render());

$infoButton->setHelpText(i18n('The robot-tag sets certain rules for search engines. You can tell it to not index certain articles or to keep pictures in this article out of its index. It has a high relevance for SEO. Only relevant and most visited articels should be indexed.'));
$tpl->set("s", "INFO_BUTTON_ROBOTS", $infoButton->render());

$infoButton->setHelpText(i18n('The avarage value for the sitemap priority is 0.5. Only important articels should have a value no more than 0.8.'));
$tpl->set("s", "INFO_BUTTON_SITEMAP_PRIORITY", $infoButton->render());

$infoButton->setHelpText(i18n('The refresh rate is focused on the content.'));
$tpl->set("s", "INFO_BUTTON_SITEMAP_FREQUENCY", $infoButton->render());

$tpl->set('s', 'SELECTED_' . $art->getField('changefreq'), 'selected');
$sitemapChangeFrequencies = array(
    '',
    'always',
    'hourly',
    'daily',
    'weekly',
    'monthly',
    'yearly',
    'never'
);
foreach ($sitemapChangeFrequencies as $value) {
    $tpl->set('s', 'SELECTED_' . $value, '');
}

// Assign additional rows
$additionalRows = ''; // call the chain to add additional rows
$cecRegistry = cApiCecRegistry::getInstance();
$cecIterator = $cecRegistry->getIterator('Contenido.Backend.ConMetaEditFormAdditionalRows');
while (false !== $chainEntry = $cecIterator->next()) {
    $additionalRows .= $chainEntry->execute($idart, $lang, $client, $art->getField('locked'));
}
$tpl->set('s', 'ADDITIONAL_ROWS', $additionalRows);

// Assign add new meta
$result = array(
    'metatype' => '',
    'fieldtype' => array(
        'text',
        'textarea',
        'date'
    ),
    'maxlength' => '255',
    'fieldname' => 'name'
);
$tpl2 = new cTemplate();
$tpl2->set('s', 'METATITLE', i18n('New meta tag'));

$sql = "SHOW FIELDS
        FROM `" . $cfg['tab']['meta_type'] . "`";
$db->query($sql);

while ($db->nextRecord()) {

    if ($db->f('Field') == 'idmetatype')
        continue;

    switch ($db->f('Field')) {

        case 'fieldtype':
            $tpl2->set('d', 'METATITLE', i18n('Field Type'));
            break;

        case 'metatype':
            $tpl2->set('d', 'METATITLE', i18n('Attribute content'));
            break;

        case 'maxlength':
            $tpl2->set('d', 'METATITLE', i18n('Max Length'));
            break;

        case 'fieldname':
            $tpl2->set('d', 'METATITLE', i18n('Meta Attribute'));
            break;

        default:
            $tpl2->set('d', 'METATITLE', i18n($db->f('Field')));
            break;
    }

    if (is_array($result[$db->f('Field')])) {
        $str = '<select id="META' . $db->f('Field') . '" name="META' . $db->f('Field') . '" ' . $disabled . '>';
        foreach ($result[$db->f('Field')] as $item) {
            $str .= '<option value="' . $item . '">' . $item . '</option>';
        }
        $str .= '<select>';
    } else {
        $str = '<input
                    type="text"
                    ' . $disabled . '
                    onblur="restoreOnBlur(this, \'' . $result[$db->f('Field')] . '\')"
                    onfocus="clearOnFocus(this, \'' . $result[$db->f('Field')] . '\');"
                    value="' . $result[$db->f('Field')] . '"
                    maxlength="255"
                    id="META' . $db->f('Field') . '"
                    name="META' . $db->f('Field') . '"
                    class="text_medium"
                >';
    }

    $tpl2->set('d', 'METAFIELDTYPE', $str);
    $tpl2->next();
}

// accessible by the current user (sysadmin client admin) anymore.
$aUserPerm = explode(',', $auth->auth['perm']);

if (in_array('sysadmin', $aUserPerm)) {
    $tpl->set('s', 'ADDMETABTN', '<img src="images/but_art_new.gif" id="addMeta">');
    $tpl->set('s', 'ADDNEWMETA', $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['con_meta_addnew'], true));
} else {
    $tpl->set('s', 'ADDMETABTN', '&nbsp;');
    $tpl->set('s', 'ADDNEWMETA', '&nbsp;');
}

// call the chain to create meta tags to display any additional tags in the
// preview
$_cecIterator = cRegistry::getCecRegistry()->getIterator('Contenido.Content.CreateMetatags');
if ($_cecIterator->count() > 0) {
    while (false !== $chainEntry = $_cecIterator->next()) {
        $metaPreview = $chainEntry->execute($metaPreview);
    }
}

$tpl2 = new cTemplate();
foreach ($metaPreview as $metaRow) {
    if ($metaRow['content'] == '') {
        $tpl2->set('d', 'META_SHOWN', 'display: none;');
    } else {
        $tpl2->set('d', 'META_SHOWN', '');
    }
    $tpl2->set('d', 'META_NAME', $metaRow['fieldname']);
    $tpl2->set('d', 'META_TYPE', $metaRow['name']);
    $tpl2->set('d', 'META_CONTENT', $metaRow['content']);
    $tpl2->next();
}

// render metatags preview
$tpl->set('s', 'META_TAGS', $tpl2->generate($cfg['path']['templates'] . 'template.con_meta_edit_form_preview.html', true));

// Assign bottom js values
if (0 != $idart && 0 != $idcat) {
    $script = 'artObj.setProperties("' . $idart . '", "' . $art->getField('idartlang') . '", "';
    $script .= $idcat . '", "' . $idcatlang . '", "' . $idcatart . '", "' . $lang . '");';
} else {
    $script = 'artObj.reset();';
}
$tpl->set('s', 'DATAPUSH', $script);
$tpl->set('s', 'PATH_TO_CALENDER_PIC', cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif');

// Genereate the Template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_meta_edit_form']);