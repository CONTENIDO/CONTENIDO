<?php
/*****************************************
* File      :   $RCSfile: include.tpl_visualedit.php,v $
* Project   :   Contenido
* Descr     :   Visual Template Editor
*
* Author    :   Timo A. Hummel
*               
* Created   :   15.12.2003
* Modified  :   $Date: 2007/07/29 17:32:13 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.tpl_visualedit.php,v 1.10 2007/07/29 17:32:13 bjoern.behrens Exp $
******************************************/


cInclude("classes","contenido/class.module.history.php");
cInclude("classes","class.ui.php");
cInclude("classes","class.htmlelements.php");
cInclude("includes", "functions.tpl.php");

$sql = "SELECT
        a.idtpl, a.name as name, a.description, a.idlay, b.description as laydescription, defaulttemplate
        FROM
        ".$cfg["tab"]["tpl"]." AS a
        LEFT JOIN
        ".$cfg["tab"]["lay"]." AS b
        ON a.idlay=b.idlay
        WHERE a.idtpl='$idtpl'
        ORDER BY name";

$db->query($sql);

$db->next_record();

$idtpl          = $db->f("idtpl");
$tplname        = $db->f("name");
$description    = $db->f("description");
$idlay          = $db->f("idlay");
$laydescription = nl2br($db->f("laydescription"));
$bIsDefault       = $db->f("defaulttemplate");


$sql = "SELECT
        number, idmod
        FROM
        ".$cfg["tab"]["container"]."
        WHERE
        idtpl='$idtpl'";

$db->query($sql);
while( $db->next_record() ) {
	$a_c[$db->f("number")] = $db->f("idmod");
}

$sql = "SELECT
        idmod, name, type
        FROM
        ".$cfg["tab"]["mod"]."
        WHERE
        idclient='$client'
        ORDER BY name";
        
$db->query($sql);

$modules = Array();

while ($db->next_record())
{
	$modules[$db->f("idmod")]["name"] = $db->f("name");
	$modules[$db->f("idmod")]["type"] = $db->f("type");	
}


$sql = "SELECT code FROM ".$cfg["tab"]["lay"]." WHERE idlay='$idlay'";
$db->query($sql);

if (!$db->next_record())
{
	echo i18n("No such layout");	
} else {
	
	$code = $db->f("code");
	
	/* Insert base href */
	$base = '<base href="'.$cfgClient[$client]["path"]["htmlpath"].'">';
	$tags = $base;
	
	$code = str_replace("<head>", "<head>\n".$tags ."\n", $code);
	
	tplPreparseLayout($idlay);
	$containers = tplBrowseLayoutForContainers($idlay);
	
	$a_container = explode("&",$containers);
	
	foreach ($a_container as $key=>$value)
	{

		if ($value != 0)
		{
			//*************** Loop through containers ****************
			$name = tplGetContainerName($idlay, $value);
			
			$modselect = new cHTMLSelectElement("c[".$value."]");
			$modselect->setAlt("Container $value ($name)");

			
			

			if ($name != "")
			{
				$tpl->set('d', 'CAPTION', 'Container '.$value." ($name)");
			} else {
				$tpl->set('d', 'CAPTION', 'Container '.$value);				
			}

			$mode = tplGetContainerMode($idlay, $value);

			if ($mode == "fixed")
			{
				$default = tplGetContainerDefault($idlay, $value);
				
				foreach ($modules as $key => $val)
    			{
    				if ($val["name"] == $default)
    				{
        				$option = new cHTMLOptionElement($val["name"], $key);
        				$option->setAlt("Container $value ($name)");
        				
        				if ($a_c[$value] == $key)
        				{
        					$option->setSelected(true);
        				}
        				
        				$modselect->addOptionElement($key, $option);
    				}
    			}								
			} else {
				
				$default = tplGetContainerDefault($idlay, $value);
				
				if ($mode == "optional" || $mode == "")
				{
    				$option = new cHTMLOptionElement("-- ".i18n("none")." --", 0);
    				
        			if (isset($a_c[$value]) && $a_c[$value] != "0")
        			{
        				$option->setSelected(false);
        			} else {
        				$option->setSelected(true);
        			}
				
    			
    				$modselect->addOptionElement(0, $option);
				}
    			
    			$allowedtypes = tplGetContainerTypes($idlay, $value);

    			foreach ($modules as $key => $val)
    			{
    				$option = new cHTMLOptionElement($val["name"], $key);
    				
    				if ($a_c[$value] == $key || ($a_c[$value] == 0 && $val["name"] == $default))
    				{
    					$option->setSelected(true);
    				}
    				
    				if (count($allowedtypes) > 0)
    				{
    					if (in_array($val["type"], $allowedtypes) || $val["type"] == "")
    					{
    						$modselect->addOptionElement($key, $option);
    					}
    				} else {
    					$modselect->addOptionElement($key, $option);
    				}
    			}
			}

			$code = str_replace("CMS_CONTAINER[$value]","$value:".$modselect->render(), $code);	
			
			/* Try to find a container */
			$code = preg_replace("/<container(.*)id=\"$value\"(.*)>/i", "$value:".$modselect->render(), $code);
			
		}
		
	}
	
	/* Get rid of any forms */
	$code = preg_replace("/<form(.*)>/i", "", $code);
	$code = preg_replace("/<\/form(.*)>/i", "", $code);
	
	$form = '<form style="height: 100%; padding: 0; margin: 0;" name="tpl_visedit" action="'.$cfg['path']['contenido_fullhtml'].'main.php">';
	$form .= $sess->hidden_session(1);
	$form .= '<input type="hidden" name="idtpl" value="'.$idtpl.'">';
	$form .= '<input type="hidden" name="frame" value="'.$frame.'">';
	$form .= '<input type="hidden" name="area" value="'.$area.'">';
	$form .= '<input type="hidden" name="description" value="'.$description.'">';
	$form .= '<input type="hidden" name="tplname" value="'.$tplname.'">';
	$form .= '<input type="hidden" name="idlay" value="'.$idlay.'">';
	$form .= '<input type="hidden" name="tplisdefault" value="'.$bIsDefault.'">';
	$form .= '<input type="hidden" name="action" value="tpl_visedit">';
	
	
	$button = '<table border="0" width="100%"><tr><td align="right"><input type="image" src="'.$cfg['path']['contenido_fullhtml']. $cfg["path"]["images"]."but_ok.gif".'"></td></tr></table>';
	$code = preg_replace("/<body(.*)>/i", "<body\\1>".$form.$button, $code);
	$code = preg_replace("/<\/body(.*)>/i", '</form></body>', $code);
	eval("?>\n".$code."\n<?php\n");
}

?>