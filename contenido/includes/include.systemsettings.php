<?php

/**
 * This file contains the system settings backend page.
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

$page = new cGuiPage("systemsettings");

$aManagedValues = array(
    'versioning_prune_limit', 'update_check', 'update_news_feed', 'versioning_path', 'versioning_activated',
    'update_check_period', 'system_clickmenu', 'mail_transport', 'system_mail_host', 'system_mail_sender',
    'system_mail_sender_name', 'pw_request_enable', 'maintenance_mode', 'codemirror_activated',
    'backend_preferred_idclient', 'generator_basehref', 'generator_xhtml', 'system_insite_editing_activated',
    'backend_backend_label', 'backend_file_extensions', 'module_translation_message', 'versioning_enabled',
    'stats_tracking'
);

// @TODO Find a general solution for this!
if (defined('CON_STRIPSLASHES')) {
    $request = cString::stripSlashes($_REQUEST);
} else {
    $request = $_REQUEST;
}

// @TODO: Check possibility to use $perm->isSysadmin()
$isSysadmin = (false !== cString::findFirstPos($auth->auth["perm"], "sysadmin"));

if ($action == "systemsettings_save_item") {
    if (false === $isSysadmin) {
        $page->displayError(i18n("You don't have the permission to make changes here."));
    } else {
        if (!in_array($request['systype'] . '_' . $request['sysname'], $aManagedValues)) {
            setSystemProperty(trim($request['systype']), trim($request['sysname']), trim($request['sysvalue']), cSecurity::toInteger($request['csidsystemprop']));
            if (isset($x)) {
                $page->displayOk(i18n('Saved changes successfully!'));
            } else {
                $page->displayOk(i18n('Created new item successfully!'));
            }
        } else {
            $page->displayWarning(i18n('Please set this property in systemsettings directly'));
        }
    }
}

if ($action == "systemsettings_delete_item") {
    if (false === $isSysadmin) {
        $page->displayError(i18n("You don't have the permission to make changes here."));
    } else {
        deleteSystemProperty($request['systype'], $request['sysname']);
        $page->displayOk(i18n('Deleted item successfully!'));
    }
}


$list = new cGuiList();
$list->setCell(1, 1, i18n("Type"));
$list->setCell(1, 2, i18n("Name"));
$list->setCell(1, 3, i18n("Value"));

if (true === $isSysadmin) {
    $list->setCell(1, 4, i18n("Action"));
}

$backendUrl = cRegistry::getBackendUrl();

$count = 2;

if (true === $isSysadmin) {
    // Edit/delete links only for sysadmin
    $oLinkEdit = new cHTMLLink();
    $oLinkEdit->setCLink($area, $frame, "systemsettings_edit_item");
    $oLinkDelete = new cHTMLLink();
    $oLinkDelete->setCLink($area, $frame, "systemsettings_delete_item");
    $oLinkEdit->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'editieren.gif" alt="' . i18n("Edit") . '" title="' . i18n("Edit") . '">');
    $oLinkDelete->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'delete.gif" alt="' . i18n("Delete") . '" title="' . i18n("Delete") . '">');
}

$spacer = new cHTMLImage();
$spacer->setWidth(5);

$sMouseoverTemplate = '<span class="tooltip" title="%1$s">%2$s</span>';

try {
    $allSystemProperties = getSystemProperties(true);
} catch (cDbException $e) {
    $allSystemProperties = [];
} catch (cException $e) {
    $allSystemProperties = [];
}
foreach ($allSystemProperties as $type => $typeSystemProperties) {
    foreach ($typeSystemProperties as $name => $value) {

        // skip managed system settings
        if (in_array($type . '_' . $name, $aManagedValues)) {
            continue;
        }

        $settingType  = conHtmlentities($type);
        $settingName  = conHtmlentities($name);
        $settingValue = conHtmlentities($value['value']);

        if (($action == "systemsettings_edit_item") && ($request['systype'] == $type) && ($request['sysname'] == $name) && $isSysadmin) {

            $oInputboxType = new cHTMLTextbox("systype", $settingType);
            $oInputboxType->setWidth(10);

            $oInputboxName = new cHTMLTextbox("sysname", $settingName);
            $oInputboxName->setWidth(30);

            $oInputboxValue = new cHTMLTextbox("sysvalue", $settingValue);
            $oInputboxValue->setWidth(30);

            $hidden = '<input type="hidden" name="csidsystemprop" value="' . $value['idsystemprop'] . '">';
            $sSubmit = '<input type="image" class="vAlignMiddle" value="submit" src="' . $backendUrl . $cfg['path']['images'] . 'submit.gif">';

            $list->setCell($count, 1, $oInputboxType->render());
            $list->setCell($count, 2, $oInputboxName->render());
            $list->setCell($count, 3, $oInputboxValue->render() . $hidden . $sSubmit);
        } else {

            if (cString::getStringLength($type) > 35) {
                $sShort = conHtmlentities(cString::trimHard($type, 35));
                $type = sprintf($sMouseoverTemplate,  $settingType, $sShort);
            }

            if (cString::getStringLength($name) > 35) {
                $sShort = conHtmlentities(cString::trimHard($name, 35));
                $name = sprintf($sMouseoverTemplate, $settingName, $sShort);
            }

            if (cString::getStringLength($value['value']) > 35) {
                $sShort =  conHtmlentities(cString::trimHard($value['value'], 35));
                $settingValue = sprintf($sMouseoverTemplate, $settingValue, $sShort);
            }

            if (empty($settingValue)) {
                $settingValue = '&nbsp;';
            }

            $list->setCell($count, 1, $type);
            $list->setCell($count, 2, $name);
            $list->setCell($count, 3, $settingValue);
        }

        if ($isSysadmin) {
            $oLinkEdit->setCustom("systype", urlencode($type));
            $oLinkEdit->setCustom("sysname", urlencode($name));

            $oLinkDelete->setCustom("systype", urlencode($type));
            $oLinkDelete->setCustom("sysname", urlencode($name));

            $list->setCell(
                $count,
                4,
                $spacer->render() . $oLinkEdit->render()
                . $spacer->render() . $oLinkDelete->render()
                . $spacer->render()
            );
        }
        $count++;
    }
}

if ($count == 2) {
    $list->setCell($count, 4, "");
    $list->setCell($count, 1, i18n("No defined properties"));
    $list->setCell($count, 2, "");
    $list->setCell($count, 3, "");
}
unset($form);

$form = new cGuiTableForm("systemsettings");
$form->setVar("area", $area);
$form->setVar("frame", $frame);
$form->setVar("action", "systemsettings_save_item");
$form->addHeader(i18n("Add new variable"));
$inputbox = new cHTMLTextbox("systype");
$inputbox->setWidth(30);
$form->add(i18n("Type"), $inputbox->render());

$inputbox = new cHTMLTextbox("sysname");
$inputbox->setWidth(30);
$form->add(i18n("Name"), $inputbox->render());

$inputbox = new cHTMLTextbox("sysvalue");
$inputbox->setWidth(30);
$form->add(i18n("Value"), $inputbox->render());

$spacer = new cHTMLDiv();
$spacer->setContent("<br>");

$renderobj = array();

if ($action == "systemsettings_edit_item") {
    if (false === $isSysadmin) {
        $page->displayError(i18n("You don't have the permission to make changes here."));
        $renderobj[] = $list;
    } else {
        $form2 = new cHTMLForm("systemsettings");
        $form2->setVar("area", $area);
        $form2->setVar("frame", $frame);
        $form2->setVar("action", "systemsettings_save_item");
        $form2->appendContent($list->render());
        $renderobj[] = $form2;
    }
} else {
    $renderobj[] = $list;
}

if (true === $isSysadmin) {
    $renderobj[] = $spacer;
    $renderobj[] = $form;
}

$page->setContent($renderobj);
$page->render();
