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
 *   modified 2008-11-13,  Timo Trautmann - Fixed wron escaping of chars
 *
 *   $Id: include.systemsettings.php 983 2009-02-05 11:13:41Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$aManagedValues = array('versioning_prune_limit', 'update_check', 'update_news_feed', 'versioning_path', 'versioning_activated', 
                        'update_check_period', 'system_clickmenu', 'system_mail_host', 'system_mail_sender',
                        'system_mail_sender_name', 'pw_request_enable', 'maintenance_mode', 'edit_area_activated',
                        'backend_preferred_idclient', 'generator_basehref', 'generator_xhtml', 'imagemagick_available',
						'system_insight_editing_activated');

if ($action == "systemsettings_save_item")
{
    if (!in_array($systype.'_'.$sysname, $aManagedValues)) {
        setSystemProperty ($systype, $sysname, $sysvalue, $csidsystemprop);
    } else {
       $sWarning = $notification->returnNotification("warning", i18n("Please set this property in systemsettings directly"), 1).'<br>';
    }
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

$oLinkEdit = new Link;
$oLinkEdit->setCLink($area, $frame, "systemsettings_edit_item");
$oLinkEdit->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'editieren.gif" alt="'.i18n("Edit").'" title="'.i18n("Edit").'">');

$oLinkForward = new Link;
$oLinkForward->setCLink('system_configuration', $frame, "");
$oLinkForward->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'editieren.gif" alt="'.i18n("Edit").'" title="'.i18n("Edit").'">');

$oLinkDelete = new Link;
$oLinkDelete->setCLink($area, $frame, "systemsettings_delete_item");
$oLinkDelete->setContent('<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'delete.gif" alt="'.i18n("Delete").'" title="'.i18n("Delete").'">');

$oLinkDeleteForward = '<img src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'delete_inact.gif" alt="'.i18n("Delete").'" title="'.i18n("Delete").'">';

$spacer = new cHTMLImage;
$spacer->setWidth(5);

if (is_array($settings))
{
    foreach ($settings as $key => $types)
    {
    	foreach ($types as $type => $value)
    	{
    		$oLinkEdit->setCustom("sysname", urlencode($type));
    		$oLinkEdit->setCustom("systype", urlencode($key));
    		
			$oLinkDelete->setCustom("sysname", urlencode($type));
    		$oLinkDelete->setCustom("systype", urlencode($key));

            $link = $oLinkEdit;
            $dlink = $oLinkDelete->render();

            if (in_array($key.'_'.$type, $aManagedValues)) {
                #ignore record
				
            } else if (($action == "systemsettings_edit_item") && (stripslashes($systype) == $key) && (stripslashes($sysname) == $type)) {
                $oInputboxValue = new cHTMLTextbox ("sysvalue", $value['value']);
    			$oInputboxValue->setStyle("border:1px;border-style:solid;border-color:black;width:200px;");
                
                $oInputboxName = new cHTMLTextbox ("sysname", $type);
    			$oInputboxName->setStyle("border:1px;border-style:solid;border-color:black;width:200px;");
                
                $oInputboxType = new cHTMLTextbox ("systype", $key);
    			$oInputboxType->setStyle("border:1px;border-style:solid;border-color:black;width:200px;");
                
                $hidden = '<input type="hidden" name="csidsystemprop" value="'.$value['idsystemprop'].'">';
                $sSubmit = '<input type="image" style="vertical-align:top;" value="submit" src="'.$cfg["path"]["contenido_fullhtml"].$cfg['path']['images'].'submit.gif">';
                
                $list->setCell($count,1, $oInputboxType->render(true));
    		    $list->setCell($count,2, $oInputboxName->render(true));
                $list->setCell($count,3, $oInputboxValue->render(true).$hidden.$sSubmit);
                
    		} else {
                $sMouseoverTemplate = '<span onmouseover="Tip(\'%s\', BALLOON, true, ABOVE, true);">%s</span>';
            
                if (strlen($type) > 35) {
                    $sShort = conHtmlSpecialChars(capiStrTrimHard($type, 35));
                    $type = sprintf($sMouseoverTemplate, conHtmlSpecialChars(addslashes($type), ENT_QUOTES), $sShort);
                }
                
                if (strlen($value['value']) > 35) {
                    $sShort = conHtmlSpecialChars(capiStrTrimHard($value['value'], 35));
                    $value['value'] = sprintf($sMouseoverTemplate, conHtmlSpecialChars(addslashes($value['value']), ENT_QUOTES), $sShort);
                }
                
                if (strlen($key) > 35) {
                    $sShort = conHtmlSpecialChars(capiStrTrimHard($key, 35));
                    $key = sprintf($sMouseoverTemplate, conHtmlSpecialChars(addslashes($key), ENT_QUOTES), $sShort);
                }
				
				!strlen(trim($value['value'])) ? $sValue = '&nbsp;' : $sValue = $value['value'];
				
                $list->setCell($count,1, $key);
                $list->setCell($count,2, $type);
                $list->setCell($count,3, $sValue);	
    		}
            
            if (!in_array($key.'_'.$type, $aManagedValues)) {
                $list->setCell($count,4, $spacer->render().$link->render().$spacer->render().$dlink.$spacer->render());
                $count++;
            }
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
$sTooltippScript = '<script type="text/javascript" src="scripts/wz_tooltip.js"></script>
                    <script type="text/javascript" src="scripts/tip_balloon.js"></script>';

$page->addScript('tooltippstyle', '<link rel="stylesheet" type="text/css" href="styles/tip_balloon.css" />');
$page->setContent($sWarning.$sTooltippScript."\n".$sListstring."<br>".$form->render());
$page->render();

?>