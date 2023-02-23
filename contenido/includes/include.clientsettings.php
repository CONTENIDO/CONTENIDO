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

$oPage = new cGuiPage("clientsettings");
$oList = new cGuiScrollList();

// @TODO Find a general solution for this!
$request = $_REQUEST;

$idclientslang = isset($request["idclientslang"]) ? cSecurity::toInteger($request["idclientslang"]) : 0;
$action = isset($request["action"]) ? $request["action"] : '';
$idclient = isset($request["idclient"]) ? cSecurity::toInteger($request["idclient"]) : 0;

$oFrmRange = new cGuiTableForm('range');
$oFrmRange->setVar('area', $area);
$oFrmRange->setVar('frame', $frame);
$oFrmRange->setVar('idclient', $idclient);
$oFrmRange->addHeader(i18n('Select range'));

$oSelRange = new cHTMLSelectElement('idclientslang');
$oOption = new cHTMLOptionElement(i18n("Language independent"), 0);
$oSelRange->addOptionElement(0, $oOption);

// Get all client languages and fill the language dependent settings select box
$oClientLangColl = new cApiClientLanguageCollection();
$aLanguages = $oClientLangColl->getAllLanguagesByClient($idclient);
foreach ($aLanguages as $curIdLang => $aItem) {
    $iID = $aItem['idclientslang'];
    $languageName = conHtmlSpecialChars($aItem['name']);
    $oOption = new cHTMLOptionElement("{$languageName} ({$aItem['idlang']})", $iID);
    $oSelRange->addOptionElement($iID, $oOption);
}

if ($idclientslang) {
    $oSelRange->setDefault($idclientslang);
}

$oSelRange->setEvent("onchange", "document.forms.range.submit();");
$oFrmRange->add(i18n('Range'), $oSelRange->render());

if (!$idclientslang) {
    $oClient = new cApiClient($idclient);
} else {
    $oClient = new cApiClientLanguage();
    $oClient->loadByPrimaryKey($idclientslang);
}

if ($action == 'clientsettings_save_item') {
    $oClient->setProperty(trim($request['cstype']), trim($request['csname']), trim($request['csvalue']), $request['csidproperty']);
    $oPage->displayOk(i18n("Save changes successfully!"));
}

if ($action == 'clientsettings_delete_item') {
    $oClient->deleteProperty($request['idprop']);
    $oPage->displayOk(i18n("Deleted item successfully!"));
}

$oList->setHeader(i18n('Type'), i18n('Name'), i18n('Value'), '&nbsp;');
$oList->objHeaderItem->updateAttributes([
    'width' => 52
]);
$oList->objRow->updateAttributes([
    'valign' => 'top'
]);

$aItems = $oClient->getProperties();

if ($aItems !== false) {
    $oLnkDelete = new cHTMLLink();
    $oLnkDelete->setCLink($area, $frame, "clientsettings_delete_item");
    $oLnkDelete->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'delete.gif" alt="' . i18n("Delete") . '" title="' . i18n("Delete") . '">');
    $oLnkDelete->setCustom("idclient", $idclient);
    $oLnkDelete->setCustom("idclientslang", $idclientslang);

    $oLnkEdit = new cHTMLLink();
    $oLnkEdit->setCLink($area, $frame, "clientsettings_edit_item");
    $oLnkEdit->setContent('<img src="' . $backendUrl . $cfg['path']['images'] . 'editieren.gif" alt="' . i18n("Edit") . '" title="' . i18n("Edit") . '">');
    $oLnkEdit->setCustom("idclient", $idclient);
    $oLnkEdit->setCustom("idclientslang", $idclientslang);

    $sSubmit = ' <input type="image" class="vAlignMiddle" value="submit" src="' . $backendUrl . $cfg['path']['images'] . 'submit.gif">';
    $sMouseoverTemplate = '<span class="tooltip" title="%1$s">%2$s</span>';

    $iCounter = 0;
    foreach ($aItems as $iKey => $aValue) {
        $settingType  = conHtmlentities($aValue['type']);
        $settingName  = conHtmlentities($aValue['name']);
        $settingValue = conHtmlentities($aValue['value']);

        $oLnkDelete->setCustom("idprop", $iKey);
        $oLnkEdit->setCustom("idprop", $iKey);

        if (($action == "clientsettings_edit_item") && ($request['idprop'] == $iKey)) {

            $oInputboxType = new cHTMLTextbox("cstype", $settingType);
            $oInputboxType->setWidth(15);
            $oInputboxName = new cHTMLTextbox("csname", $settingName);
            $oInputboxName->setWidth(15);
            $oInputboxValue = new cHTMLTextbox("csvalue", $settingValue);
            $oInputboxValue->setWidth(30);

            $hidden = '<input type="hidden" name="csidproperty" value="' . $iKey . '">';

            $oList->setData(
                $iCounter,
                $oInputboxType->render(),
                $oInputboxName->render(),
                $oInputboxValue->render() . $sSubmit . $hidden,
                $oLnkEdit->render() . '&nbsp;&nbsp;&nbsp;' . $oLnkDelete->render() . '&nbsp;&nbsp;&nbsp;'
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
                $oLnkEdit->render() . '&nbsp;&nbsp;&nbsp;' . $oLnkDelete->render()
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
$oForm->setVar('area', $area);
$oForm->setVar('frame', $frame);
$oForm->setVar('action', 'clientsettings_save_item');
$oForm->setVar('idclient', $idclient);
$oForm->setVar('idclientslang', $idclientslang);
$oForm->addHeader(i18n('Add new variable'));

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

if ($action == "clientsettings_edit_item") {
    $oForm2 = new cHTMLForm("clientsettings", "main.php");
    $oForm2->setVar("area", $area);
    $oForm2->setVar("frame", $frame);
    $oForm2->setVar("action", "clientsettings_save_item");
    $oForm2->setVar("idclient", $idclient);
    $oForm2->setVar("idclientslang", $idclientslang);

    $oForm2->appendContent($oList->render());
    $oPage->setContent([
        $oFrmRange,
        $spacer,
        $oForm2,
        $spacer,
        $oForm
    ]);
} else {
    $oPage->setContent([
        $oFrmRange,
        $spacer,
        $oList,
        $spacer,
        $oForm
    ]);
}

$oPage->render();