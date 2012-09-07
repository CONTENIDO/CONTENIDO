<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Edit clients
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-04-30
 *   modified 2008-06-26, Dominik Ziegler, add security fix
 *
 *   $Id: include.client_edit.php 433 2008-06-30 16:28:27Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.htmlelements.php");
cInclude('classes', 'contenido/class.client.php');

$properties = new PropertyCollection;

$db2 = new DB_Contenido;

if ($action == "client_new")
{
    $nextid = $db->nextid($cfg["tab"]["clients"]);
    $idclient = $nextid;
    $new = true;
}
if(!$perm->have_perm_area_action($area))
{
	$notification->displayNotification("error", i18n("Permission denied"));
} else {
	if ( !isset($idclient) )
	{
	  $notification->displayNotification("error", i18n("No client ID passed"));
	} else {
	    if (($action == "client_edit") && ($perm->have_perm_area_action($area, $action)))
	    {
	    	$sNewNotification = '';
	        if ($active != "1")
	        {
	            $active = "0";
	        }
	        
	        if ($new == true)
	        {
	        	
	        	$sLangNotification = i18n('Notice: In order to use this client, you must create a new language for it.');
           	    $sTarget = $sess->url('frameset.php?area=lang');
                $sJsLink = "parent.parent.location.href='".$sTarget."';
                            top.header.markActive(top.header.document.getElementById('sub_lang'));";
                $sLangNotificationLink = sprintf(i18n('Please click %shere%s to create a new language.'), '<a href="javascript://" onclick="'.$sJsLink.'">', '</a>');
            	$sNewNotification = '<br>'.$sLangNotification.'<br>'.$sLangNotificationLink;
	             if (substr($frontendpath, strlen($frontendpath)-1) != "/")
	             {
	                $frontendpath .= "/";
	             }
	
	             if (substr($htmlpath, strlen($htmlpath)-1) != "/")
	             {
	                $htmlpath .= "/";
	             }
	             
	             $sql = "INSERT INTO
	                ".$cfg["tab"]["clients"]."
	                SET
	                    name = '".Contenido_Security::escapeDB($clientname, $db)."',
	                    frontendpath = '".Contenido_Security::escapeDB($frontendpath, $db)."',
	                    htmlpath = '".Contenido_Security::escapeDB($htmlpath, $db)."',
	                    errsite_cat = '".Contenido_Security::toInteger($errsite_cat)."',
	                    errsite_art = '".Contenido_Security::toInteger($errsite_art)."',
	                    idclient = '".Contenido_Security::toInteger($idclient)."'";
	                 
				$properties->setValue("idclient", $idclient, "backend", "clientimage", $clientlogo);
				
				// Copy the client template to the real location
				$destPath = $frontendpath;
				$sourcePath = $cfg['path']['contenido'] . $cfg['path']['frontendtemplate'];
	
	            if ($copytemplate)
	            {
	                if (!file_exists($destPath))
	                {
	                    recursive_copy($sourcePath, $destPath);
	                    $res = fopen($destPath."config.php","rb+");
	                    $res2 = fopen($destPath."config.php.new", "ab+");
	                    
	                    if ($res && $res2)
	                    {
	                    	while (!feof($res))
	                    	{
		                        $buffer = fgets($res, 4096);
	                        	$buffer = str_replace("!CLIENT!", $idclient, $buffer);
	                        	$buffer = str_replace("!PATH!", $cfg["path"]["contenido"], $buffer);
	                        	fwrite($res2, $buffer);
	                    	}
	                    	
	                    } else {
	                  		$notification->displayNotification("error",i18n("Couldn't write the file config.php."));
	                    }
	
	                    fclose($res);
	                    fclose($res2);
	
	                    unlink($destPath."config.php");
	                    rename($destPath."config.php.new", $destPath."config.php");
	
	                } else {
	                	$message = sprintf(i18n("The directory %s already exists. The client was created, but you have to copy the frontend-template yourself"),$destPath);
	                	$notification->displayNotification("warning", $message);
	                }
	            }
				rereadClients();
	        } else {
	            $pathwithoutslash = $frontendpath;
	            if (substr($frontendpath, strlen($frontendpath)-1) != "/")
	            {
	               $frontendpath .= "/";
	            }
	            
	            if (substr($htmlpath, strlen($htmlpath)-1) != "/")
	            {
	               $htmlpath .= "/";
	            }            
	
	            if (($oldpath != $frontendpath) && ($oldpath != $pathwithoutslash))
	            {
	                $notification->displayNotification("warning", i18n("You changed the client path. You might need to copy the frontend to the new location"));
	
	            }
	            $sql = "UPDATE 
	                    ".$cfg["tab"]["clients"]."
	                    SET
							name = '".Contenido_Security::escapeDB($clientname, $db)."',
							frontendpath = '".Contenido_Security::escapeDB($frontendpath, $db)."',
							htmlpath = '".Contenido_Security::escapeDB($htmlpath, $db)."',
							errsite_cat = '".Contenido_Security::toInteger($errsite_cat)."',
							errsite_art = '".Contenido_Security::toInteger($errsite_art)."'
						WHERE
							idclient = '".Contenido_Security::toInteger($idclient)."'";
	        }
	
	        $db->query($sql);
	        $new = false;
	        rereadClients();
	        
	        $properties->setValue("idclient", $idclient, "backend", "clientimage", $clientlogo);
	        
	        /* Clear the con_code table */
	        $sql = "DELETE FROM ".$cfg["tab"]["code"]." WHERE idclient = '".Contenido_Security::toInteger($idclient)."'";
	        $db->query($sql);
	        
            $notification->displayNotification("info", i18n("Changes saved").$sNewNotification);
	
	    	$cApiClient = new cApiClient;
	    	$cApiClient->loadByPrimaryKey($idclient);
	
	        if ($_REQUEST["generate_xhtml"] == "no")
	        {
	        	$cApiClient->setProperty("generator", "xhtml", "false");
	        } else {
	        	$cApiClient->setProperty("generator", "xhtml", "true");	
	        }        
	    } 
	
	
	    $tpl->reset();
	    
	    $sql = "SELECT
	                idclient, name, frontendpath, htmlpath, errsite_cat, errsite_art
	            FROM
	                ".$cfg["tab"]["clients"]."
	            WHERE
	                idclient = '".Contenido_Security::toInteger($idclient)."'";
	
	    $db->query($sql);
	
	    $db->next_record();
	
	    $form = '<form name="client_properties" method="post" action="'.$sess->url("main.php?").'">
	                 '.$sess->hidden_session().'
	                 <input type="hidden" name="area" value="'.$area.'">
	                 <input type="hidden" name="action" value="client_edit">
	                 <input type="hidden" name="frame" value="'.$frame.'">
	                 <input type="hidden" name="new" value="'.$new.'">
	                 <input type="hidden" name="oldpath" value="'.$db->f("frontendpath").'">
	                 <input type="hidden" name="idclient" value="'.$idclient.'">';
	    
	    $tpl->set('s', 'JAVASCRIPT', $javascript);
	    $tpl->set('s', 'FORM', $form);
	    $tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
	    $tpl->set('s', 'BGCOLOR', $cfg["color"]["table_dark"]);
	    $tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
	    $tpl->set('s', 'CANCELTEXT', i18n("Discard changes"));
	    $tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&idclient=$idclient"));
	
	    if ($error)
	    {
	        echo $error;
	    }
	
	    $tpl->set('d', 'CATNAME', i18n("Property"));
	    $tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
	    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
	    $tpl->set('d', 'CATFIELD', i18n("Value"));
			$tpl->set('d', 'BRDRT', 1);
			$tpl->set('d', 'BRDRB', 0);
			$tpl->set('d', 'FONT', 'textg_medium');
	    $tpl->next();
	    
	    $tpl->set('d', 'CATNAME', i18n("Client name"));
	    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
	    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
	    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "clientname", htmlspecialchars($db->f("name")), 50, 255));
			$tpl->set('d', 'BRDRT', 0);
			$tpl->set('d', 'BRDRB', 1);
			$tpl->set('d', 'FONT', 'text_medium');
	    $tpl->next();
	
	    $serverpath = $db->f("frontendpath");
	
	    if ($serverpath == "")
	    {
	        $serverpath = $cfg['path']['frontend'];
	    }
	    
	    $tpl->set('d', 'CATNAME', i18n("Server path"));
	    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
	    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
	    $tpl->set('d', 'CATFIELD',  formGenerateField ("text", "frontendpath", htmlspecialchars($serverpath), 50, 255));
			$tpl->set('d', 'BRDRT', 0);
			$tpl->set('d', 'BRDRB', 1);
			$tpl->set('d', 'FONT', 'text_medium');
	    $tpl->next();   
	
	    $htmlpath = $db->f("htmlpath");
	
	    if ($htmlpath == "")
	    {
	        $htmlpath = "http://";
	    }
	    
	    $tpl->set('d', 'CATNAME', i18n("Web address"));
	    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
	    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
	    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "htmlpath", htmlspecialchars($htmlpath), 50, 255));
			$tpl->set('d', 'BRDRT', 0);
			$tpl->set('d', 'BRDRB', 1);
			$tpl->set('d', 'FONT', 'text_medium');
	    $tpl->next();      
	
	    $tpl->set('d', 'CATNAME', i18n("Error page category"));
	    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
	    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
	    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "errsite_cat", $db->f("errsite_cat"), 10, 10));
			$tpl->set('d', 'BRDRT', 0);
			$tpl->set('d', 'BRDRB', 1);
			$tpl->set('d', 'FONT', 'text_medium');
	    $tpl->next();  
	
	    $tpl->set('d', 'CATNAME', i18n("Error page article"));
	    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
	    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
	    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "errsite_art", $db->f("errsite_art"), 10, 10));
		$tpl->set('d', 'BRDRT', 0);
		$tpl->set('d', 'BRDRB', 1);
		$tpl->set('d', 'FONT', 'text_medium');
	    $tpl->next(); 
	    
	    $clientLogo = $properties->getValue ("idclient", $idclient, "backend", "clientimage");
	    
	    $tpl->set('d', 'CATNAME', i18n("Client logo"));
	    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
	    $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
	    $tpl->set('d', 'CATFIELD', formGenerateField ("text", "clientlogo", $clientLogo, 50, 255));
			$tpl->set('d', 'BRDRT', 0);
			$tpl->set('d', 'BRDRB', 1);
			$tpl->set('d', 'FONT', 'text_medium');
	    $tpl->next();
	
	    $aChoices = array("no" => i18n("No"), "yes" => i18n("Yes"));
	    				  
	    $oXHTMLSelect = new cHTMLSelectElement("generate_xhtml");
	    $oXHTMLSelect->autoFill($aChoices);
	    
		$cApiClient = new cApiClient; 
		$cApiClient->loadByPrimaryKey($idclient);     
		if ($cApiClient->getProperty("generator", "xhtml") == "true") 
		{
			$oXHTMLSelect->setDefault("yes");
		} else { 
			$oXHTMLSelect->setDefault("no");
		}
	    	    
	    $tpl->set('d', 'CATNAME', i18n("Generate XHTML"));
	    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
	    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
	    $tpl->set('d', 'CATFIELD', $oXHTMLSelect->render());
		$tpl->set('d', 'BRDRT', 0);
	    $tpl->set('d', 'BRDRB', 1);
		$tpl->set('d', 'FONT', 'text_medium');
	    $tpl->next();
	
	    if ($new == true)
	    {
	        $tpl->set('d', 'CATNAME', i18n("Copy frontend template"));
	        $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
	        $tpl->set('d', "BORDERCOLOR", $cfg["color"]["table_border"]);
	        $tpl->set('d', 'CATFIELD', formGenerateCheckbox ("copytemplate", "checked", 1));
	        $tpl->next();
	    }
        
        $tpl->set('s', 'IDCLIENT', $idclient);
        
	    # Generate template
	    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['client_edit']);
	}
}
?>
