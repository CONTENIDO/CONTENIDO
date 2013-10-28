<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Contenido Client Article Specifications
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-26, Dominik Ziegler, add security fix
 *
 *   $Id: include.client_artspec.php 338 2008-06-27 09:02:23Z frederic.schneider $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if ($action == "client_artspec_save")
{
	if(!$perm->have_perm_area_action($area, $action))
	{
  		$notification->displayNotification("error", i18n("Permission denied"));
	} else {
		addArtspec($_POST['artspectext'], $online);
	}
}

if ($action == "client_artspec_delete")
{
	if(!$perm->have_perm_area_action($area, $action))
	{
  		$notification->displayNotification("error", i18n("Permission denied"));
	} else {
		deleteArtspec($_GET['idartspec']);
	}
}

if ($action == "client_artspec_online")
{
	if(!$perm->have_perm_area_action($area, "client_artspec_save"))
	{
  		$notification->displayNotification("error", i18n("Permission denied"));
	} else {
		setArtspecOnline($_GET['idartspec'], $online);
	}
}

if ($action == "client_artspec_default")
{
	if(!$perm->have_perm_area_action($area, "client_artspec_save"))
	{
  		$notification->displayNotification("error", i18n("Permission denied"));
	} else {
		setArtspecDefault($_GET['idartspec'], $online);
	}
}

$page = new UI_Page;
$list = new UI_List;

$artspec = getArtspec();

if(!empty($artspec)) {

    $list->setCell(1,1, i18n("Article specification"));
    $list->setCell(1,2, i18n("Options"));

    $list->setBgColor(1,$cfg['color']['table_header']);
    $list->setBorder(1);
    $list->setWidth ("250px");

    $count = 2;

    $link = new Link;
    $link->setCLink($area, $frame, "client_artspec_edit");
    $link->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'editieren.gif" alt="'.i18n("Edit").'" title="'.i18n("Edit").'">');

    $dlink = new Link;
    $dlink->setCLink($area, $frame, "client_artspec_delete");
    $dlink->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'delete.gif" alt="'.i18n("Delete").'" title="'.i18n("Delete").'">');

    $olink = new Link;
    $olink->setCLink($area, $frame, "client_artspec_online");

    $defLink = new Link;
    $defLink->setCLink($area, $frame, "client_artspec_default");

    if (is_array($artspec))
    {
    	foreach ($artspec as $id => $tmp_artspec)
    	{
    		$link->setCustom("idartspec", $id);
    		$link->updateAttributes(array ('style' => 'padding:3'));

       		$dlink->setCustom("idartspec", $id);
    		$dlink->updateAttributes(array ('style' => 'padding:3'));

    		$olink->setCustom("idartspec", $id);
    		$olink->updateAttributes(array ('style' => 'padding:3'));

      	 	$defLink->setCustom("idartspec", $id);
    		$defLink->updateAttributes(array ('style' => 'padding:3'));

    		if (($action == "client_artspec_edit") && ($idartspec == $id))
        		{
    				$form = new UI_Form("artspec");
    				$form->setVar("area",$area);
    				$form->setVar("frame", $frame);
    				$form->setVar("idartspec", $id);
    				$form->setVar("action", "client_artspec_save");
    				$form->setVar("online", $artspec[$id]['online']);
    				$inputbox = new cHTMLTextbox ("artspectext", urldecode($artspec[$id]['artspec']));
    				$inputbox->setStyle("border:1px;border-style:solid;border-color:black;");
    				$form->add("name",$inputbox->render());
    				$form->add("submit", '<input type="image" value="submit" src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'submit.gif" alt="'.i18n("Save").'" title="'.i18n("Save").'">');

       			$list->setCell($count,1, $form->render(true));
        		} else {
    				$list->setCell($count,1, urldecode($artspec[$id]['artspec']));
        		}

        	if ($artspec[$id]['online'] == 0)
        	{ //it is offline (std!)
    				$olink->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'offline.gif" alt="'.i18n("Make online").'" title="'.i18n("Make online").'">');
    				$olink->setCustom("online", 1);
        	} else
        	{
        		$olink->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'online.gif" alt="'.i18n("Make offline").'" title="'.i18n("Make offline").'">');
        		$olink->setCustom("online", 0);
        	}

        	if ($artspec[$id]['default'] == 0)
        	{
        		$defLink->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'artikel_spez_inakt.gif" title="'.i18n("Make this article specification default").'">');
        		$list->setCell($count,2, $link->render().$dlink->render().$olink->render().$defLink->render());
        	} else
        	{
    	   		$defLinkText = '<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'artikel_spez_akt.gif" title="'.i18n("This article specification is default").'" style="padding-left:3px;">';
        		$list->setCell($count,2, $link->render().$dlink->render().$olink->render().$defLinkText);
        	}

    		$count++;
    	}

    } else
    {
    	$list->setCell($count,1, i18n("No article specifications found!"));
    	$list->setCell($count,2, '');
    }
}

unset($form);

$form = new UI_Table_Form("artspec");
$form->setVar("area",$area);
$form->setVar("frame", $frame);
$form->setVar("action", "client_artspec_save");
$form->addHeader(i18n("Create new article specification"));
$inputbox = new cHTMLTextbox ("artspectext");
$inputbox->setStyle("border:1px;border-style:solid;border-color:black;");
$form->add(i18n("Specification name"),$inputbox->render());

$page->setContent($list->render()."<br>".$form->render());
$page->render();
?>