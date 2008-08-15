<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Visual Template Editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.1.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-12-15
 *   modified 2008-06-27, Dominik Ziegler, add security fix
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
cInclude("includes", "functions.tpl.php");

$sql = "SELECT
        a.idtpl, a.name as name, a.description, a.idlay, b.description as laydescription, defaulttemplate
        FROM
        ".$cfg["tab"]["tpl"]." AS a
        LEFT JOIN
        ".$cfg["tab"]["lay"]." AS b
        ON a.idlay=b.idlay
        WHERE a.idtpl='".Contenido_Security::toInteger($idtpl)."'
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
        idtpl='".Contenido_Security::toInteger($idtpl)."'";

$db->query($sql);
while( $db->next_record() ) {
	$a_c[$db->f("number")] = $db->f("idmod");
}

$sql = "SELECT
        idmod, name, type
        FROM
        ".$cfg["tab"]["mod"]."
        WHERE
        idclient='".Contenido_Security::toInteger($client)."'
        ORDER BY name";
        
$db->query($sql);

$modules = Array();

while ($db->next_record())
{
	$modules[$db->f("idmod")]["name"] = $db->f("name");
	$modules[$db->f("idmod")]["type"] = $db->f("type");	
}


$sql = "SELECT code FROM ".$cfg["tab"]["lay"]." WHERE idlay='".Contenido_Security::toInteger($idlay)."'";
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
                        if (strlen($val["name"]) > 20) {
                            $short_name = capiStrTrimHard($val["name"], 20);
                            $option = new cHTMLOptionElement($short_name, $key);
            				$option->setAlt("Container $value ($name) ".$val["name"]);
                        } else {
            				$option = new cHTMLOptionElement($val["name"], $key);
            				$option->setAlt("Container $value ($name)");
                        }
        				
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
                    $short_name = $val["name"];
                    if (strlen($val["name"]) > 20) {
                        $short_name = capiStrTrimHard($val["name"], 20);
                    }
                    
                    $option = new cHTMLOptionElement($short_name, $key);
                    
                    if (strlen($val["name"]) > 20) {
                        $option->setAlt("Container $value ($name) ".$val["name"]);
                    }
    				
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
            
			$code = str_replace("CMS_CONTAINER[$value]","<div style=\"position:relative; height:26px;white-space:nowrap;font-size:12px;\" onmouseover=\"this.style.zIndex = '20'\" onmouseout=\"this.style.zIndex = '10'\"> $value:".$modselect->render() .'</div>', $code);	
			
			/* Try to find a container */
			$code = preg_replace("/<container(.*)id=\"$value\"(.*)>/i", "<div style=\"position:relative; height:26px;white-space:nowrap;font-size:12px;\" onmouseover=\"this.style.zIndex = '20'\" onmouseout=\"this.style.zIndex = '10'\"> $value:".$modselect->render()  .'</div>', $code);
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