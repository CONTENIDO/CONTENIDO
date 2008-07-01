<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Button Widgets
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.12
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2004-08-04
 *   
 *   $Id: class.widgets.views.php,v 1.1 2004/08/04 07:15:30 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


/**
 * Contenido Table view
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cTableView
{
	var $items;
	var $captions;
	var $id;
	var $rownames;
	
	var $formname;
	var $formmethod;
	var $formaction;
	var $formvars;
	
	var $tableid;
	var $tablebordercolor;
	
	var $header;
	var $cancelLink;
	var $submitjs;
	
	
	function UI_Table_Form ($name, $action = "", $method = "post")
	{
		global $sess, $cfg;
		
		$this->formname = $name;
		
		if ($action == "")
		{
			$this->formaction = "main.php";
		} else {
			$this->formaction = $action;
		}
		
		$this->formmethod = $method;

		$this->tableid = "";
		$this->tablebordercolor = $cfg['color']['table_border'];
		$this->setAccessKey('s');
		$this->custom = array();

		$this->setActionButton("submit", "images/but_ok.gif", i18n("Save changes"), "s");
		
	}
	
	function setVar ($name, $value)
	{
		$this->formvars[$name] = $value;
	}
	
	function add ($caption, $field, $rowname = "")
	{
		if (is_array($field))
		{
			
			foreach ($field as $value)
			{
        		if (is_object($value) && method_exists($value, "render"))
        		{
        			$n .= $value->render();
        		} else {
        			$n .= $value;	
        		}				
			}
			
			$field = $n;
		}
		if (is_object($field) && method_exists($field, "render"))
		{
			$n = $field->render();
			$field = $n;
		}
		if ($field == "")
		{
			$field = "&nbsp;";
		}
		
		if ($caption == "")
		{
			$caption = "&nbsp;";
		}
		
		$this->id++;
		$this->items[$this->id] = $field;
		$this->captions[$this->id] = $caption; 
		
		if ($rowname == "")
		{
			$rowname = $this->id;
		}
		
		$this->rownames[$this->id] = $rowname;
	}

	function addCancel ($link)
	{
		$this->cancelLink = $link;
	}
		
	function addHeader ($header)
	{
		$this->header = $header;
	}
	
	function setSubmitJS ($js)
	{
		$this->submitjs = $js;
	}
	
	function setAccessKey ($key)
	{
		$this->accessKey = $key;
	}
	
	function setActionEvent ($id, $event)
	{
		$this->custom[$id]["event"] = $event;	
	}
	
	function setActionButton ($id, $image, $description = "", $accesskey = false, $action = false)
	{
		$this->custom[$id]["image"] = $image;
		$this->custom[$id]["type"] = "actionsetter";
		$this->custom[$id]["action"] = $action;
		$this->custom[$id]["description"] = $description;
		$this->custom[$id]["accesskey"] = $accesskey;
		$this->custom[$id]["event"] = "";
	}
	
	function unsetActionButton ($id)
	{
		unset($this->custom[$id]);
	}
	
	
	function render ($return = true)
	{
		global $sess, $cfg;
		
		$tpl = new Template;
		
		$extra = "";
		
		
		$form  = '<form enctype="multipart/form-data" style="margin:0px" name="'.$this->formname.'" method="'.$this->formmethod.'" action="'.$this->formaction.'">'."\n";
		$this->formvars["contenido"] = $sess->id;
		
		if (is_array($this->formvars))
		{
			foreach ($this->formvars as $key => $value)
			{
                 $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'">'."\n";
			}
		}
		
		if (!array_key_exists("action", $this->formvars))
		{
			$form .= '<input type="hidden" name="action" value="">';
		}
		
		$tpl->set('s', 'FORM', $form);
		$tpl->set('s', 'ID', $this->tableid);
		$tpl->set('s', 'BORDERCOLOR', $this->tablebordercolor);
		
		if ($this->header != "")
		{
			$header  = '<tr class="text_medium" style="background-color: '.$cfg["color"]["table_header"].';">';
			$header .= '<td colspan="2" valign="top" style="border: 0px; border-top:1px; border-right:1px;border-color: '.$cfg["color"]["table_border"].'; border-style: solid;">'.$this->header.'</td></tr>';
		}
		
		$tpl->set('s', 'HEADER', $header);
		
		$dark = false;
		
		if (is_array($this->items))
		{
			foreach ($this->items as $key => $value)
			{
				$tpl->set('d', 'CATNAME', $this->captions[$key]);
				$tpl->set('d', 'CATFIELD', $value);
				$tpl->set('d', 'ROWNAME', $this->rownames[$key]);
				
				$dark = !$dark;
            	if ($dark) {
                	$bgColor = $cfg["color"]["table_dark"];
            	} else {
    	            $bgColor = $cfg["color"]["table_light"];
            	}
            	
            	$tpl->set('d', 'BGCOLOR', $bgColor);
            	$tpl->set('d', 'BORDERCOLOR', $this->tablebordercolor);
				$tpl->next();
			}
		}	
		
		$tpl->set('s', 'CONTENIDOPATH',$cfg["path"]["contenido_fullhtml"]);

		if ($this->cancelLink != "")
		{
			$img = '<img src="'.$cfg["path"]["contenido_fullhtml"].'images/but_cancel.gif" border="0">';
			
			$tpl->set('s', 'CANCELLINK', '<a href="'.$this->cancelLink.'">'.$img.'</a>'); 	
		} else {
			$tpl->set('s', 'CANCELLINK','');
		}
		
		if ($this->submitjs != "")
		{
			$extra .= 'onclick="'.$this->submitjs.'"';	
		}
		
		if ($this->accesskey != "")
		{
			$tpl->set('s', 'KEY', $this->accesskey);
		} else {
			$tpl->set('s', 'KEY', '');
		}
		 
		$tpl->set('s', 'EXTRA', $extra);
		
		$custombuttons = "";
		
		foreach ($this->custom as $key => $value)
		{
			if ($value["accesskey"] != "")
			{
				$accesskey = 'accesskey="'.$value["accesskey"].'"';
			} else {
				$accesskey = "";
			}
			
			$onclick = "";
			if ($value["action"] !== false)
			{
				$onclick = 'document.forms[\''.$this->formname.'\'].elements[\'action\'].value = \''.$value["action"].'\';';
			}
			
			if ($value["event"] != "")
			{
				echo "foo";
				$onclick .= $value["event"];
			}
			
			$custombuttons .= '<input style="margin-left: 5px;" title="'.$value["description"].'" alt="'.$value["description"].'" type="image" src="'.$value["image"].'" name="submit" onclick="'.$onclick.'" '.$accesskey.'>';
		}
		
		$tpl->set('s', 'EXTRABUTTONS', $custombuttons);
		
		$rendered = $tpl->generate($cfg["path"]["contenido"].$cfg['path']['templates'] . $cfg['templates']['generic_table_form'],true);
		
		if ($return == true)
		{
			return ($rendered);
		} else {
			echo $rendered;
		}
	}

		
}

?>