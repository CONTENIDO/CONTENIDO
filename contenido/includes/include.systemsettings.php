<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido System Settings Screen
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.7.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-11-18
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes","class.ui.php");
cInclude("classes","class.htmlelements.php");

if ($action == "systemsettings_save_item")
{
	setSystemProperty ($systype, $sysname, $sysvalue, $csidsystemprop);
}

if ($action == "systemsettings_delete_item")
{
	deleteSystemProperty($systype, $sysname);	
}

$settings = getSystemProperties(1);

$list = new UI_List;
$list->setSolidBorder(true);
$list->setCell(1,1, i18n("Type"));
$list->setCell(1,2, i18n("Name"));
$list->setCell(1,3, i18n("Value"));
$list->setCell(1,4, "&nbsp;");
$list->setBgColor(1,$cfg['color']['table_header']);
$list->setBorder(1);

$count = 2;

$link = new Link;
$link->setCLink($area, $frame, "systemsettings_edit_item");
$link->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'editieren.gif" alt="'.i18n("Edit").'" title="'.i18n("Edit").'">');

$dlink = new Link;
$dlink->setCLink($area, $frame, "systemsettings_delete_item");
$dlink->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'delete.gif" alt="'.i18n("Delete").'" title="'.i18n("Delete").'">');

$spacer = new cHTMLImage;
$spacer->setWidth(5);

if (is_array($settings))
{
    foreach ($settings as $key => $types)
    {
    	foreach ($types as $type => $value)
    	{
    		$link->setCustom("sysname", $type);
    		$link->setCustom("systype", $key);
    		
				$dlink->setCustom("sysname", $type);
    		$dlink->setCustom("systype", $key);
				
    		$list->setCell($count,1, $key);
    		$list->setCell($count,2, $type);
    		
    		if (($action == "systemsettings_edit_item") && ($systype == $key) && ($sysname == $type))
    		{
                $oInputboxValue = new cHTMLTextbox ("sysvalue", $value['value']);
    			$oInputboxValue->setStyle("border:1px;border-style:solid;border-color:black;width:200px;");
                
                $oInputboxName = new cHTMLTextbox ("sysname", $type);
    			$oInputboxName->setStyle("border:1px;border-style:solid;border-color:black;width:200px;");
                
                $oInputboxType = new cHTMLTextbox ("systype", $key);
    			$oInputboxType->setStyle("border:1px;border-style:solid;border-color:black;width:200px;");
                
                $hidden = '<input type="hidden" name="csidsystemprop" value="'.$value['idsystemprop'].'">';
                $sSubmit = ' <input type="image" style="vertical-align:top;" value="submit" src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'submit.gif">';

                $list->setCell($count,1, $oInputboxType->render(true));
    		    $list->setCell($count,2, $oInputboxName->render(true));
                $list->setCell($count,3, $oInputboxValue->render(true).$hidden.$sSubmit);
    		} else {
                $list->setCell($count,1, $key);
    		    $list->setCell($count,2, $type);
    			$list->setCell($count,3, $value['value']);	
    		}
            $list->setCell($count,4, $spacer->render().$link->render().$spacer->render().$dlink->render().$spacer->render());
    		$count++;
    	}
    }
}

if ($count == 2)
{
	$list->setCell($count, 4, "");
	$list->setCell($count, 1, i18n("No defined properties"));
	$list->setCell($count, 2, "");
	$list->setCell($count, 3, "");
}
unset($form);

$form = new UI_Table_Form("systemsettings");
$form->setVar("area",$area);
$form->setVar("frame", $frame);
$form->setVar("action", "systemsettings_save_item");
$form->addHeader(i18n("Add new variable"));
$inputbox = new cHTMLTextbox ("systype");
$inputbox->setStyle("border:1px;border-style:solid;border-color:black;");
$form->add(i18n("Type"),$inputbox->render());

$inputbox = new cHTMLTextbox ("sysname");
$inputbox->setStyle("border:1px;border-style:solid;border-color:black;");
$form->add(i18n("Name"),$inputbox->render());

$inputbox = new cHTMLTextbox ("sysvalue");
$inputbox->setStyle("border:1px;border-style:solid;border-color:black;");
$form->add(i18n("Value"),$inputbox->render());

if ($action == "systemsettings_edit_item")
{
    $form2 = new UI_Form("systemsettings");
    $form2->setVar("area",$area);
    $form2->setVar("frame", $frame);
    $form2->setVar("action", "systemsettings_save_item");
    $form2->add('list', $list->render());
    $sListstring = $form2->render();
} else {
    $sListstring = $list->render();
}


$page = new UI_Page;
$page->setContent($sListstring."<br>".$form->render());
$page->render();

?>