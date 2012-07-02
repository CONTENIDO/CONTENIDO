<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Client Settings
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


$oPage = new cPage;
$oList = new cScrollList;

$idclient = $_GET['idclient'];
if (strlen($idclient) == 0)
{
    $idclient = $_POST['idclient'];
}

$oFrmRange = new UI_Table_Form('range');
$oFrmRange->setVar('area',$area);
$oFrmRange->setVar('frame', $frame);
$oFrmRange->setVar('idclient', $idclient);
$oFrmRange->addHeader(i18n('Select range'));

$oSelRange     = new cHTMLSelectElement ('idclientslang');
$oOption    = new cHTMLOptionElement(i18n("Language independent"), 0);
$oSelRange->addOptionElement(0, $oOption);

$sSQL = "SELECT A.name AS name, A.idlang AS idlang, B.idclientslang AS idclientslang
        FROM
        ".$cfg["tab"]["lang"]." AS A,
        ".$cfg["tab"]["clients_lang"]." AS B
        WHERE
        A.idlang=B.idlang AND
        B.idclient='".Contenido_Security::toInteger($idclient)."'
        ORDER BY A.idlang";

$db->query($sSQL);

while ($db->next_record()) {
    $iID = $db->f("idclientslang");
    $oOption = new cHTMLOptionElement($db->f("name")." (".$db->f("idlang").")", $iID);
    $oSelRange->addOptionElement($iID, $oOption);
}

if (is_numeric($_REQUEST["idclientslang"])) {
    $oSelRange->setDefault($_REQUEST["idclientslang"]);
}

$oSelRange->setEvent("onchange", "document.forms.range.submit();");
$oFrmRange->add(i18n('Range'),$oSelRange->render());

if (!is_numeric($_REQUEST["idclientslang"]) || $_REQUEST["idclientslang"] == 0) {
    $oClient = new cApiClient($idclient);
} else {
    $oClient = new cApiClientLanguage();
    $oClient->loadByPrimaryKey($_REQUEST["idclientslang"]);
}

if ($_POST['action'] == 'clientsettings_save_item')
{
    $oClient->setProperty($_POST['cstype'], $_POST['csname'], $_POST['csvalue'], $_POST['csidproperty']);
    $notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Save changes successfully!"));
}

if ($_GET['action'] == 'clientsettings_delete_item')
{
    $oClient->deleteProperty($_GET['idprop']);
    $notification->displayNotification(Contenido_Notification::LEVEL_INFO, i18n("Deleted item successfully!"));

}

$oList->setHeader(i18n('Type'), i18n('Name'), i18n('Value'), '&nbsp;');
$oList->objHeaderItem->updateAttributes(array('width' => 52));
$oList->objRow->updateAttributes(array('valign' => 'top'));

$aItems = $oClient->getProperties();

if ($aItems !== false)
{
    $oLnkDelete = new Link;
    $oLnkDelete->setCLink($area, $frame, "clientsettings_delete_item");
    $oLnkDelete->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'delete.gif" alt="'.i18n("Delete").'" title="'.i18n("Delete").'">');
    $oLnkDelete->setCustom("idclient", $idclient);
    $oLnkDelete->setCustom("idclientslang", $_REQUEST["idclientslang"]);

    $oLnkEdit = new Link;
    $oLnkEdit->setCLink($area, $frame, "clientsettings_edit_item");
    $oLnkEdit->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'editieren.gif" alt="'.i18n("Edit").'" title="'.i18n("Edit").'">');
    $oLnkEdit->setCustom("idclient", $idclient);
    $oLnkEdit->setCustom("idclientslang", $_REQUEST["idclientslang"]);

    $iCounter = 0;
    foreach ($aItems as $iKey => $aValue) {
        $oLnkDelete->setCustom("idprop", $iKey);
        $oLnkEdit->setCustom("idprop", $iKey);

        if (($_GET['action'] == "clientsettings_edit_item") && ($_GET['idprop'] == $iKey))
        {
                $oForm = new UI_Form("clientsettings");
                $oForm->setVar("area", $area);
                $oForm->setVar("frame", $frame);
                $oForm->setVar("action", "clientsettings_save_item");
                $oForm->setVar("idclient", $idclient);
                $oForm->setVar("idclientslang", $_REQUEST["idclientslang"]);

                $oInputboxValue = new cHTMLTextbox("csvalue", $aValue['value']);
                $oInputboxName = new cHTMLTextbox("csname", $aValue['name']);
                $oInputboxType = new cHTMLTextbox("cstype", $aValue['type']);

                $hidden = '<input type="hidden" name="csidproperty" value="'.$iKey.'">';
                $sSubmit = ' <input type="image" style="vertical-align:top;" value="submit" src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'submit.gif">';

                $oList->setData($iCounter, $oInputboxType->render(), $oInputboxName->render(), $oInputboxValue->render().$hidden.$sSubmit, $oLnkEdit->render() . '&nbsp;&nbsp;&nbsp;' . $oLnkDelete->render());
        } else
        {
            $sMouseoverTemplate = '<span class="tooltip" title="%1$s">%2$s</span>';

            if (strlen($aValue['type']) > 35) {
                $sShort = htmlspecialchars(cApiStrTrimHard($aValue['type'], 35));
                $aValue['type'] = sprintf($sMouseoverTemplate, htmlspecialchars(addslashes($aValue['type']), ENT_QUOTES), $sShort);
            }

            if (strlen($aValue['value']) > 35) {
                $sShort = htmlspecialchars(cApiStrTrimHard($aValue['value'], 35));
                $aValue['value'] = sprintf($sMouseoverTemplate, htmlspecialchars(addslashes($aValue['value']), ENT_QUOTES), $sShort);
            }

            if (strlen($aValue['name']) > 35) {
                $sShort = htmlspecialchars(cApiStrTrimHard($aValue['name'], 35));
                $aValue['name'] = sprintf($sMouseoverTemplate, htmlspecialchars(addslashes($aValue['name']), ENT_QUOTES), $sShort);
            }

            $oList->setData($iCounter, $aValue['type'], $aValue['name'], $aValue['value'], $oLnkEdit->render() . '&nbsp;&nbsp;&nbsp;' . $oLnkDelete->render());
        }
        $iCounter++;
    }
} else
{
    $oList->objItem->updateAttributes(array('colspan' => 4));
    $oList->setData(0, i18n("No defined properties"));
}

$oForm = new UI_Table_Form('clientsettings');
$oForm->setVar('area',$area);
$oForm->setVar('frame', $frame);
$oForm->setVar('action', 'clientsettings_save_item');
$oForm->setVar('idclient', $idclient);
$oForm->setVar('idclientslang', $_REQUEST["idclientslang"]);
$oForm->addHeader(i18n('Add new variable'));

$oInputbox = new cHTMLTextbox('cstype');
$oForm->add(i18n('Type'), $oInputbox->render());

$oInputbox = new cHTMLTextbox('csname');
$oForm->add(i18n('Name'), $oInputbox->render());

$oInputbox = new cHTMLTextbox('csvalue');
$oForm->add(i18n('Value'), $oInputbox->render());

if (($_GET['action'] == "clientsettings_edit_item"))
{
    $oForm2 = new UI_Form("clientsettings");
    $oForm2->setVar("area",$area);
    $oForm2->setVar("frame", $frame);
    $oForm2->setVar("action", "clientsettings_save_item");
    $oForm2->setVar("idclient", $idclient);
    $oForm2->setVar("idclientslang", $_REQUEST["idclientslang"]);

    $oForm2->add('list', $oList->render());
    $sSettingsList = $oForm2->render();
} else {
    $sSettingsList = $oList->render();
}

$sTooltippScript = '<script type="text/javascript" src="scripts/jquery/jquery.js"></script>
                    <script type="text/javascript" src="scripts/jquery.tipsy.js"></script>
                    <script type="text/javascript" src="scripts/registerTipsy.js"></script>';

$oPage->addScript('tooltippstyle', '<link rel="stylesheet" type="text/css" href="styles/tipsy.css" />');
$oPage->addScript('tooltip-js', $sTooltippScript);
$oPage->setContent("\n".$oFrmRange->render() . '<br>' . $sSettingsList . '<br>' . $oForm->render());
$oPage->render();
?>