<?php
/**
 * This file contains the system settings backend page.
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

$page = new cGuiPage("systemsettings");

$aManagedValues = array(
    'versioning_prune_limit', 'update_check', 'update_news_feed', 'versioning_path', 'versioning_activated',
    'update_check_period', 'system_clickmenu', 'system_mail_host', 'system_mail_sender',
    'system_mail_sender_name', 'pw_request_enable', 'maintenance_mode', 'codemirror_activated',
    'backend_preferred_idclient', 'generator_basehref', 'generator_xhtml',
    'system_insite_editing_activated', 'backend_backend_label'
);

if ($action == "systemsettings_save_item") {
    if (strpos($auth->auth["perm"], "sysadmin") === false) {
        $page->displayError(i18n("You don't have the permission to make changes here."), 1);
    } else {
        if (!in_array($systype . '_' . $sysname, $aManagedValues)) {
            setSystemProperty($systype, $sysname, $sysvalue, $csidsystemprop);
            if (isset($x)) {
                $page->displayInfo(i18n('Saved changes successfully!'), 1);
            } else {
                $page->displayInfo(i18n('Created new item successfully!'), 1);
            }
        } else {
            $page->displayWarning(i18n('Please set this property in systemsettings directly'), 1);
        }
    }
}

if ($action == "systemsettings_delete_item") {
    if (strpos($auth->auth["perm"], "sysadmin") === false) {
        $page->displayError(i18n("You don't have the permission to make changes here."), 1);
    } else {
        deleteSystemProperty($systype, $sysname);
        $page->displayInfo(i18n('Deleted item successfully!'), 1);
    }
}

$settings = getSystemProperties(1);

$list = new cGuiList();
$list->setCell(1, 1, i18n("Type"));
$list->setCell(1, 2, i18n("Name"));
$list->setCell(1, 3, i18n("Value"));

if (!(strpos($auth->auth["perm"], "sysadmin") === false)) {
    $list->setCell(1, 4, "&nbsp;");
}

$backendUrl = cRegistry::getBackendUrl();

$count = 2;

$oLinkEdit = new cHTMLLink();
$oLinkEdit->setCLink($area, $frame, "systemsettings_edit_item");
$oLinkDelete = new cHTMLLink();
$oLinkDelete->setCLink($area, $frame, "systemsettings_delete_item");
if (strpos($auth->auth["perm"], "sysadmin") === false) {
    $oLinkEdit->setContent('<img src="' .$backendUrl . $cfg['path']['images'] . 'editieren_off.gif" alt="' . i18n("Edit") . '" title="' . i18n("Edit") . '">');
    $oLinkDelete->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'delete_inact.gif" alt="' . i18n("Delete") . '" title="' . i18n("Delete") . '">');
} else {
    $oLinkEdit->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'editieren.gif" alt="' . i18n("Edit") . '" title="' . i18n("Edit") . '">');
    $oLinkDelete->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'delete.gif" alt="' . i18n("Delete") . '" title="' . i18n("Delete") . '">');
}

$spacer = new cHTMLImage();
$spacer->setWidth(5);

if (is_array($settings)) {
    foreach ($settings as $key => $types) {
        foreach ($types as $type => $value) {
            $oLinkEdit->setCustom("sysname", urlencode($type));
            $oLinkEdit->setCustom("systype", urlencode($key));

            $oLinkDelete->setCustom("sysname", urlencode($type));
            $oLinkDelete->setCustom("systype", urlencode($key));

            $link = $oLinkEdit;
            $dlink = $oLinkDelete->render();

            if (in_array($key . '_' . $type, $aManagedValues)) {
                #ignore record
            } else if (($action == "systemsettings_edit_item") && (stripslashes($systype) == $key) && (stripslashes($sysname) == $type) && (strpos($auth->auth["perm"], "sysadmin") !== false)) {
                $oInputboxValue = new cHTMLTextbox("sysvalue", $value['value']);
                $oInputboxValue->setWidth(30);
                $oInputboxName = new cHTMLTextbox("sysname", $type);
                $oInputboxName->setWidth(30);
                $oInputboxType = new cHTMLTextbox("systype", $key);
                $oInputboxType->setWidth(10);

                $hidden = '<input type="hidden" name="csidsystemprop" value="' . $value['idsystemprop'] . '">';
                $sSubmit = '<input type="image" class="vAlignMiddle" value="submit" src="' . $backendUrl . $cfg['path']['images'] . 'submit.gif">';

                $list->setCell($count, 1, $oInputboxType->render(true));
                $list->setCell($count, 2, $oInputboxName->render(true));
                $list->setCell($count, 3, $oInputboxValue->render(true) . $hidden . $sSubmit);
            } else {
                $sMouseoverTemplate = '<span class="tooltip" title="%1$s">%2$s</span>';

                if (strlen($type) > 35) {
                    $sShort = conHtmlSpecialChars(cApiStrTrimHard($type, 35));
                    $type = sprintf($sMouseoverTemplate, conHtmlSpecialChars(addslashes($type), ENT_QUOTES), $sShort);
                }

                if (strlen($value['value']) > 35) {
                    $sShort = conHtmlSpecialChars(cApiStrTrimHard($value['value'], 35));
                    $value['value'] = sprintf($sMouseoverTemplate, conHtmlSpecialChars(addslashes($value['value']), ENT_QUOTES), $sShort);
                }

                if (strlen($key) > 35) {
                    $sShort = conHtmlSpecialChars(cApiStrTrimHard($key, 35));
                    $key = sprintf($sMouseoverTemplate, conHtmlSpecialChars(addslashes($key), ENT_QUOTES), $sShort);
                }
                !strlen(trim($value['value'])) ? $sValue = '&nbsp;' : $sValue = $value['value'];

                $list->setCell($count, 1, $key);
                $list->setCell($count, 2, $type);
                $list->setCell($count, 3, $sValue);
            }

            if (!in_array($key . '_' . $type, $aManagedValues)) {
                if (!(strpos($auth->auth["perm"], "sysadmin") === false)) {
                    $list->setCell($count, 4, $spacer->render() . $link->render() . $spacer->render() . $dlink . $spacer->render());
                }
                $count++;
            }
        }
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
$inputbox->setWidth(10);
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
    if (strpos($auth->auth["perm"], "sysadmin") === false) {
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

if (strpos($auth->auth["perm"], "sysadmin") !== false) {
    $renderobj[] = $spacer;
    $renderobj[] = $form;
}

$page->setContent($renderobj);
$page->render();
