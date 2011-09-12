<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Defines the "mod" related functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend includes
 * @version    1.0.2
 * @author     Olaf Niemann, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2003
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2010-08-13, Dominik Ziegler, fixed CON-337 - added update of lastmodified
 *   modified 2011-01-11, Rusmir Jusufovic
 *   	- save and load input and output of moduls from/in files
 *   	- save the modul infos in to the xml file
 *   
 *   modified 2011-06-22, Rusmir Jusufovic add alias as modul names
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude ("includes", "functions.tpl.php");
cInclude ("includes", "functions.con.php");

function modEditModule($idmod, $name, $description, $input, $output, $template, $type = "")
{
    global $db, $client, $cfgClient, $auth, $cfg, $sess, $area_tree, $perm, $frame;

    $date   = date("Y-m-d H:i:s");
    $author = $auth->auth["uname"];
	$contenidoModuleHandler="";
	$notification = new Contenido_Notification();
	$messageIfError = "";
	
	#alias for modul name for the file system 
	$alias = capiStrCleanURLCharacters($name);
	
	
	
    /**
	* START TRACK VERSION
	**/
	$oVersion = new VersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);
	// Create new Module Version in cms/version/module/
	$oVersion->createNewVersion();

	/**
	* END TRACK VERSION
	**/
    
	if (!$idmod)
	{
		$cApiModuleCollection = new cApiModuleCollection;
		$cApiModule = $cApiModuleCollection->create($name);

		$idmod = $cApiModule->get("idmod");
		
        cInclude ("includes", "functions.rights.php");
        createRightsForElement("mod", $idmod);
		$contenidoModuleHandler = new Contenido_Module_Handler($idmod);
	} else {
		$cApiModule = new cApiModule;
		$cApiModule->loadByPrimaryKey($idmod);	
		$contenidoModuleHandler = new Contenido_Module_Handler($idmod);	
	}
    
      #save contents of input or output 
	    $contenidoModuleHandler->saveInput(stripslashes($input));
	    $contenidoModuleHandler->saveOutput(stripslashes($output));
	    
    if (	$cApiModule->get("alias") != stripslashes($alias) || $cApiModule->get("template") != stripslashes($template) ||
    		$cApiModule->get("description") != stripslashes($description) ||$cApiModule->get("type") != stripslashes($type))
    {
		#rename the module if the name changed
		 $change=false;
		 $oldName =$cApiModule->get("alias");
		
        if($cApiModule->get("alias") != $alias) {
            $change=true;
            
            #if modul exist show massage 
            if( Contenido_Module_Handler::existModulInDirectory($alias, $cfgClient)) {
             	$notification->displayNotification('error',i18n("Modul name exist in modul directory, pleas choos an other name."));
		        die();
            }
        }
           
       
	     		
		
		
		
		#name of modul changed
		if($change == true)
		{		
		       #the new name of modul dont exist im modul dir
		       if( $contenidoModuleHandler->renameModul($oldName,$alias) == false) {
		       	$notification->displayNotification('error',i18n("Can't rename modul, is a modul file open ??? !"));
		       	die();
		       } else {
		       		$cApiModule->set("name", $name);
					$cApiModule->set("template", $template);
					$cApiModule->set("description", $description);
					$cApiModule->set("type",$type);
					$cApiModule->set("lastmodified",$date);
      				$cApiModule->set("alias", $alias);
					$cApiModule->store();
		       	
		       }

		       #set the new module name
		       $contenidoModuleHandler->setNewModulName($alias);
		       #save input and output in file
		       if( $contenidoModuleHandler->saveInput(stripslashes($input)) == false)
		       $messageIfError .= "<br/>". i18n("Can't save input !");
		       
		       	if($contenidoModuleHandler->saveOutput(stripslashes($output)) == false)
		       	$messageIfError .= "<br/>". i18n("Can't save output !");
		       	
		       
		       if($contenidoModuleHandler->saveInfoXML($oldName , $description, $type) == false)
		       		$messageIfError .= "<br/>". i18n("Can't save xml modul info file!");
		       
		       
		       	#display error
		       	if( 	$messageIfError != "") {
		       		$notification->displayNotification('error',$messageIfError);
		       		#set the old name because modul could not rename 
		        	$cApiModule->set("name",$oldName);
		        	$cApiModule->store();
		       	}
		       	
		    
		    
		    
		    
		} else {
			
				$cApiModule->set("name", $name);
				$cApiModule->set("template", $template);
				$cApiModule->set("description", $description);
				$cApiModule->set("type",$type);
				$cApiModule->set("lastmodified",$date);
      			$cApiModule->set("alias", $alias);
				$cApiModule->store();
		    
		     if($contenidoModuleHandler->saveInfoXML($oldName , $description, $type) == false)
		       		$notification->displayNotification('error',i18n("Can't save xml modul info file!"));
		}
    }

	
    return $idmod;
}

function modDeleteModule($idmod)
{
    # Global vars
    global $db, $sess, $client, $cfg, $area_tree, $perm;

    $sql = "DELETE FROM ".$cfg["tab"]["mod"]." WHERE idmod = '".Contenido_Security::toInteger($idmod)."' AND idclient = '".Contenido_Security::toInteger($client)."'";
    $db->query($sql);


    // delete rights for element
    cInclude ("includes", "functions.rights.php");
    deleteRightsForElement("mod", $idmod); 
}

// $code: Code to evaluate
// $id: Unique ID for the test function
// $mode: true if start in php mode, otherwise false
// Returns true or false

function modTestModule ($code, $id, $output = false)
{
	global $cfg, $modErrorMessage;

	$magicvalue = 0;

    $db = new DB_Contenido;

	/* Put a $ in front of all CMS variables
	   to prevent PHP error messages */
    $sql = "SELECT type FROM ".$cfg["tab"]["type"];
    $db->query($sql);

    while ($db->next_record())
    {
       $code = str_replace($db->f("type").'[','$'.$db->f("type").'[', $code);
    }

    $code = preg_replace(',\[(\d+)?CMS_VALUE\[(\d+)\](\d+)?\],i', '[\1\2\3]', $code);

    $code = str_replace('CMS_VALUE','$CMS_VALUE', $code);
    $code = str_replace('CMS_VAR','$CMS_VAR', $code); 

	/* If the module is an output module, escape PHP since
       all output modules enter php mode */
    if ($output == true)
    {
    	$code = "?>\n" . $code . "\n<?php";
    }


    /* Looks ugly: Paste a function declarator
       in front of the code */
    $code = "function foo".$id." () {" . $code;
    $code .= "\n}\n";


    /* Set the magic value */
    $code .= '$magicvalue = 941;';

    /* To parse the error message, we prepend and
       append a phperror tag in front of the output */
	$sErs = ini_get("error_prepend_string"); // Save current setting (see below)
	$sEas = ini_get("error_append_string");  // Save current setting (see below)
    @ini_set("error_prepend_string","<phperror>");
    @ini_set("error_append_string","</phperror>");

    /* Turn off output buffering and error reporting, eval the code */
	ob_start();
	$display_errors = ini_get("display_errors");
	@ini_set("display_errors", true);
    $output = eval($code);
    @ini_set("display_errors", $display_errors);

    /* Get the buffer contents and turn it on again */
	$output = ob_get_contents();
    ob_end_clean();

    /* Remove the prepend and append settings */
    /* 19.09.2006: Following lines have been disabled, as ini_restore has been disabled
       by some hosters as there is a security leak in PHP (PHP <= 5.1.6 & <= 4.4.4) */
    //ini_restore("error_prepend_string");
    //ini_restore("error_append_string");
	@ini_set("error_prepend_string", $sErs); // Restoring settings (see above)
	@ini_set("error_append_string",  $sEas); // Restoring settings (see above)

    /* Strip out the error message */
    $start = strpos($output, "<phperror>");
    $end = strpos($output, "</phperror>");

    /* More stripping: Users shouldnt see where the file
       is located, but they should see the error line */
    if ($start !== false)
    {
    	$start = strpos($output, "eval()");

    	$modErrorMessage = substr($output, $start, $end - $start);

    	/* Kill that HTML formatting */
    	$modErrorMessage = str_replace("<b>","",$modErrorMessage);
    	$modErrorMessage = str_replace("</b>","",$modErrorMessage);
    	$modErrorMessage = str_replace("<br>","",$modErrorMessage);
    	$modErrorMessage = str_replace("<br />","",$modErrorMessage);
    }

    /* check if there are any php short tags in code, and display error*/
    $bHasShortTags = false;
    if (preg_match('/<\?\s+/', $code) && $magicvalue == 941) {
        $bHasShortTags = true;
        $modErrorMessage = i18n('Please do not use short open Tags. (Use <?php instead of <?).');
    }
    
    
    /* Now, check if the magic value is 941. If not, the function
       didn't compile */
    if ($magicvalue != 941 || $bHasShortTags)
    {
    	return false;
    } else {
    	return true;
    }
}

?>