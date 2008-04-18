<?php

/******************************************
* File      :   functions.mod.php
* Project   :   Contenido
* Descr     :   Defines the 'mod' related
*               functions
*
* Author    :   Olaf Niemann, Jan Lengowski
* Created   :   21.01.2003
* Modified  :   25.03.2003
*
* © four for business AG
******************************************/
cInclude ("includes", "functions.tpl.php");
cInclude ("includes", "functions.con.php");
cInclude ("classes", "contenido/class.module.history.php");
cInclude ("classes", "contenido/class.module.php");

function modEditModule($idmod, $name, $description, $input, $output, $template, $type = "")
{
    global $db, $client, $auth, $cfg, $sess, $area_tree, $perm;

    $date   = date("Y-m-d H:i:s");
    $author = $auth->auth["uname"];

	if (!$idmod)
	{
		$cApiModuleCollection = new cApiModuleCollection;
		$cApiModule = $cApiModuleCollection->create($name);

		$idmod = $cApiModule->get("idmod");
		
        cInclude ("includes", "functions.rights.php");
        createRightsForElement("mod", $idmod);
	} else {
		$cApiModule = new cApiModule;
		$cApiModule->loadByPrimaryKey($idmod);	
	}
    
    if (	$cApiModule->get("name") != stripslashes($name) ||
    		$cApiModule->get("output") != stripslashes($output) ||
    		$cApiModule->get("template") != stripslashes($template) ||
    		$cApiModule->get("description") != stripslashes($description) ||
    		$cApiModule->get("input") != stripslashes($input) ||
    		$cApiModule->get("type") != stripslashes($type))
    {
		$cApiModule->set("name", $name);
		$cApiModule->set("output", $output);
		$cApiModule->set("template", $template);
		$cApiModule->set("description", $description);
		$cApiModule->set("input", $input);
		$cApiModule->set("type", $type);
		
		$cApiModule->store();
    }
	
    return $idmod;
}

function modDeleteModule($idmod)
{
    # Global vars
    global $db, $sess, $client, $cfg, $area_tree, $perm;

    $sql = "DELETE FROM ".$cfg["tab"]["mod"]." WHERE idmod = '".$idmod."' AND idclient = '".$client."'";
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
