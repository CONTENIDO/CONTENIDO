<?php

/**
 * This file contains the backend page for client settings.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$backendUrl = cRegistry::getBackendUrl();
$cfg = cRegistry::getConfig();
$area = cRegistry::getArea();
$frame = cRegistry::getFrame();

$page = new cGuiPage('clientsettings');

$selectedClientLanguageId = cSecurity::toInteger($_REQUEST['idclientslang'] ?? '0');
$selectedClientId = cSecurity::toInteger($_REQUEST['idclient'] ?? '0');
$action = $_REQUEST['action'] ?? '';
$csidproperty = cSecurity::toInteger($_REQUEST['csidproperty'] ?? '0');

$selectedClient = cBackendClientHelper::requireClient($selectedClientId);
if (!$selectedClient) {
    return;
}

if (!cBackendClientHelper::requireClientHasLanguages($selectedClient)) {
    return;
}

if ($selectedClientLanguageId >= 1) {
    $selectedClientLanguage = cBackendClientHelper::requireClientLanguage($selectedClientLanguageId);
    if (!$selectedClientLanguage) {
        return;
    }
} else {
    $selectedClientLanguage = null;
}

// Store settings as client language property (language-dependent) or client property (language-independent)
$itemInstance = $selectedClientLanguage ?: $selectedClient;

if ($action == 'clientsettings_save_item') {
    $itemInstance->setProperty(
        trim($_REQUEST['cstype']),
        trim($_REQUEST['csname']),
        trim($_REQUEST['csvalue']),
        $csidproperty
    );
    $page->displayOk(i18n("Save changes successfully!"));
}

if ($action == 'clientsettings_delete_item') {
    $itemInstance->deleteProperty($_REQUEST['idprop']);
    $page->displayOk(i18n("Deleted item successfully!"));
}

// Language selection form
$clientLanguageForm = new cGuiClientLanguageForm($selectedClient, $selectedClientLanguage);

$oList = new cGuiScrollList();
$oList->objTable->setClass('generic col_md');

$oList->setHeader(i18n('Type'), i18n('Name'), i18n('Value'), i18n('Actions'));
$oList->objHeaderItem->updateAttributes([
    'width' => 52
]);
$oList->objRow->updateAttributes([
    'valign' => 'top'
]);

$imagesPath = $backendUrl . $cfg['path']['images'];

$aItems = $itemInstance->getProperties();

if ($aItems !== false) {
    // Wrapper for the buttons
    $controls = new cHTMLDiv('', 'con_form_action_control');

    $oLnkDelete = new cHTMLLink();
    $oLnkDelete->setClass('con_img_button')
        ->setCLink($area, $frame, "clientsettings_delete_item")
        ->setContent(cHTMLImage::img($imagesPath . 'delete.gif', i18n("Delete")))
        ->setCustom('idclient', $selectedClientId)
        ->setCustom('idclientslang', $selectedClientLanguageId);

    $oLnkEdit = new cHTMLLink();
    $oLnkEdit->setClass('con_img_button')
        ->setCLink($area, $frame, "clientsettings_edit_item")
        ->setContent(cHTMLImage::img($imagesPath . 'editieren.gif', i18n("Edit")))
        ->setCustom('idclient', $selectedClientId)
        ->setCustom('idclientslang', $selectedClientLanguageId);

    $sSubmit = cHTMLButton::image($imagesPath . 'submit.gif', i18n("Save"), ['class' => 'con_img_button']);
    $sMouseoverTemplate = '<span class="tooltip" title="%1$s">%2$s</span>';

    $iCounter = 0;
    foreach ($aItems as $iKey => $aValue) {
        $settingType = conHtmlentities($aValue['type']);
        $settingName = conHtmlentities($aValue['name']);
        $settingValue = conHtmlentities($aValue['value']);

        foreach ([$oLnkDelete, $oLnkEdit] as $_link) {
            $_link->setCustom('idprop', $iKey);
            $_link->setCustom('idclient', $selectedClient->getId());
            $_link->setCustom('idclientslang', $selectedClientLanguage ? $selectedClientLanguage->getId() : 0);
        }

        $controls->setContent([
            $oLnkEdit->render(), $oLnkDelete->render()
        ]);

        if (($action == 'clientsettings_edit_item') && ($_REQUEST['idprop'] == $iKey)) {
            $oInputboxType = new cHTMLTextbox('cstype', $settingType);
            $oInputboxType->setWidth(15);
            $oInputboxName = new cHTMLTextbox('csname', $settingName);
            $oInputboxName->setWidth(15);
            $oInputboxValue = new cHTMLTextbox('csvalue', $settingValue);
            $oInputboxValue->setClass('mgr5')
                ->setWidth(30);

            $hidden = '<input type="hidden" name="csidproperty" value="' . $iKey . '">';

            $oList->setData(
                $iCounter,
                $oInputboxType->render(),
                $oInputboxName->render(),
                $oInputboxValue->render() . $sSubmit . $hidden,
                $controls->render()
            );
        } else {
            if (cString::getStringLength($aValue['type']) > 35) {
                $sShort = conHtmlentities(cString::trimHard($aValue['type'], 35));
                $settingType = sprintf($sMouseoverTemplate, $settingType, $sShort);
            }

            if (cString::getStringLength($aValue['name']) > 35) {
                $sShort = conHtmlentities(cString::trimHard($aValue['name'], 35));
                $settingName = sprintf($sMouseoverTemplate, $settingName, $sShort);
            }

            if (cString::getStringLength($aValue['value']) > 35) {
                $sShort = conHtmlentities(cString::trimHard($aValue['value'], 35));
                $settingValue = sprintf($sMouseoverTemplate, $settingValue, $sShort);
            }

            $oList->setData(
                $iCounter,
                $settingType,
                $settingName,
                $settingValue,
                $controls->render()
            );
        }
        $iCounter++;
    }
} else {
    $oList->objItem->updateAttributes([
        'colspan' => 4
    ]);
    $oList->setData(0, i18n("No defined properties"));
}

$oForm = new cGuiTableForm('clientsettings');
$oForm->setTableClass('generic col_sm');
$oForm->setVar('area', $area);
$oForm->setVar('frame', $frame);
$oForm->setVar('action', 'clientsettings_save_item');
$oForm->setVar('idclient', $selectedClientId);
$oForm->setVar('idclientslang', $selectedClientLanguageId);
$oForm->setHeader(i18n('Add new variable'));

$oInputbox = new cHTMLTextbox('cstype');
$oInputbox->setWidth(15);
$oForm->add(i18n('Type'), $oInputbox->render());

$oInputbox = new cHTMLTextbox('csname');
$oInputbox->setWidth(15);
$oForm->add(i18n('Name'), $oInputbox->render());

$oInputbox = new cHTMLTextbox('csvalue');
$oInputbox->setWidth(30);
$oForm->add(i18n('Value'), $oInputbox->render());

$spacer = new cHTMLDiv();
$spacer->setContent("<br>");

if ($action == 'clientsettings_edit_item') {
    $oForm2 = new cHTMLForm('clientsettings', "main.php");
    $oForm2->setVar('area', $area);
    $oForm2->setVar('frame', $frame);
    $oForm2->setVar('action', 'clientsettings_save_item');
    $oForm2->setVar('idclient', $selectedClientId);
    $oForm2->setVar('idclientslang', $selectedClientLanguageId);

    $oForm2->appendContent($oList->render());
    $page->setContent([
        $clientLanguageForm->render(),
        $spacer,
        $oForm2,
        $spacer,
        $oForm
    ]);
} else {
    $page->setContent([
        $clientLanguageForm->render(),
        $spacer,
        $oList,
        $spacer,
        $oForm
    ]);
}

$page->render();
