<?php

cInclude('classes', 'contenido/class.client.php');
cInclude('classes', 'contenido/class.clientslang.php');
cInclude('classes', 'contenido/class.lang.php');
cInclude('classes', 'class.ui.php');
cInclude('classes', 'widgets/class.widgets.page.php');
cInclude('classes', 'class.htmlelements.php');

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

$oSelRange 	= new cHTMLSelectElement ('idclientslang');
$oOption	= new cHTMLOptionElement(i18n("Language independent"), 0);
$oSelRange->addOptionElement(0, $oOption);

$sSQL = "SELECT A.name AS name, A.idlang AS idlang, B.idclientslang AS idclientslang 
        FROM
        ".$cfg["tab"]["lang"]." AS A,
        ".$cfg["tab"]["clients_lang"]." AS B
        WHERE
        A.idlang=B.idlang AND
        B.idclient='$idclient'
        ORDER BY A.idlang";
        
$db->query($sSQL);

/* Doesn't work as for unknown reasons: idclientslang will be identified
 * as link between both tables... and anyway, we are not getting all needed 
 * fields from the genericdb (no language name)...
$oLanguages = new cApiLanguageCollection;
$oLanguages->link("cApiClientLanguageCollection");
$oLanguages->setWhere("capiclientlanguagecollection.idclient", $idclient);
$oLanguages->setOrder("capilanguagecollection.idlang");
$oLanguages->query(); */

while ($db->next_record()) {
	$iID = $db->f("idclientslang");
	$oOption = new cHTMLOptionElement($db->f("name")." (".$db->f("idlang").")", $iID);
	$oSelRange->addOptionElement($iID, $oOption);
}

if (is_numeric($_REQUEST["idclientslang"])) {
	$oSelRange->setDefault($_REQUEST["idclientslang"]);
}

$oSelRange->setStyle('border:1px;border-style:solid;border-color:black;');
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
}

if ($_GET['action'] == 'clientsettings_delete_item')
{
	$oClient->deleteProperty($_GET['idprop']);
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
    foreach($aItems as $iKey => $aValue)
    {
    	$oLnkDelete->setCustom("idprop", $iKey);
    	$oLnkEdit->setCustom("idprop", $iKey);
   	
    	if (($_GET['action'] == "clientsettings_edit_item") && ($_GET['idprop'] == $iKey))
    	{
    			$oForm = new UI_Form("clientsettings");
    			$oForm->setVar("area",$area);
    			$oForm->setVar("frame", $frame);
    			$oForm->setVar("action", "clientsettings_save_item");
    			$oForm->setVar("idclient", $idclient);
    			$oForm->setVar("idclientslang", $_REQUEST["idclientslang"]);
    			
    			$oInputboxValue = new cHTMLTextbox ("csvalue", $aValue['value']);
    			$oInputboxValue->setStyle("border:1px;border-style:solid;border-color:black;width:200px;");
                
                $oInputboxName = new cHTMLTextbox ("csname", $aValue['name']);
    			$oInputboxName->setStyle("border:1px;border-style:solid;border-color:black;width:200px;");
                
                $oInputboxType = new cHTMLTextbox ("cstype", $aValue['type']);
    			$oInputboxType->setStyle("border:1px;border-style:solid;border-color:black;width:200px;");
                
                $hidden = '<input type="hidden" name="csidproperty" value="'.$iKey.'">';
                $sSubmit = ' <input type="image" style="vertical-align:top;" value="submit" src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'submit.gif">';

				$oList->setData($iCounter, $oInputboxType->render(), $oInputboxName->render(), $oInputboxValue->render().$hidden.$sSubmit, $oLnkEdit->render() . '&nbsp;&nbsp;&nbsp;' . $oLnkDelete->render());
    	} else
    	{
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

$oInputbox = new cHTMLTextbox ('cstype');
$oInputbox->setStyle('border:1px;border-style:solid;border-color:black;');
$oForm->add(i18n('Type'),$oInputbox->render());

$oInputbox = new cHTMLTextbox ('csname');
$oInputbox->setStyle('border:1px;border-style:solid;border-color:black;');
$oForm->add(i18n('Name'),$oInputbox->render());

$oInputbox = new cHTMLTextbox ('csvalue');
$oInputbox->setStyle('border:1px;border-style:solid;border-color:black;');
$oForm->add(i18n('Value'),$oInputbox->render());

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

$oPage->setContent($oFrmRange->render() . '<br>' . $sSettingsList . '<br>' . $oForm->render());
$oPage->render();
?>