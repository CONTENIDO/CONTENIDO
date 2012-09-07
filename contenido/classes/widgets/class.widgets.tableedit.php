<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1,0
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 
 *   
 *   $Id: class.widgets.tableedit.php 738 2008-08-27 10:21:19Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


class cWidgetTableEdit
{
	function cWidgetTableEdit ($metaobject, $title)
	{
		$this->_metaobject = $metaobject;
		$this->_title = $title;
		
		if ($_GET["edit"] == get_class($this->_metaobject->_payloadObject))
		{
			$this->_metaobject->processEdit();	
		}
	}
	
	function render ()
	{
		global $cfg, $sess, $action, $area, $frame;
		
		if ($this->_metaobject->_objectInvalid) { return; }
		$this->_metaobject->defineFields();
		
		$form = new cHTMLForm;
		$form->setVar("contenido", $sess->id);
		
		/* Fetch the edit action from the metaobject */
		$editaction = $this->_metaobject->getAction($this->_metaobject->_editAction);
		
		$form->setVar("action", $editaction->_namedAction);
		$form->setVar("area", $area);
		$form->setVar("frame", $frame);
		$form->setVar("edit", get_class($this->_metaobject->_payloadObject));
		$form->setVar($this->_metaobject->_payloadObject->primaryKey, $this->_metaobject->_payloadObject->get($this->_metaobject->_payloadObject->primaryKey));
		
		
		$table = new cHTMLTable;
		$table->setStyle("border: 1px solid ".$cfg['color']['table_border'].";");
		$row = new cHTMLTableRow;
		
		$row->setContent($this->renderHeader());
		
		$out = "";
		
		if (count($this->_metaobject->_fields) == 1)
		{
			foreach ($this->_metaobject->_fields as $key => $value)
			{
				$out .= $this->renderRows($key,$this->_iconWidth+6);
			}
		} else {
			foreach ($this->_metaobject->_fields as $key => $value)
			{
				$out .= $this->renderGroup($key);
			}
		}
		
		$out .= $this->renderButtons();
			
		
		$table->setContent($row->render() . $out);
		
		$form->setContent($table);
		
		return ($form->render());
	}
	
	function renderHeader ()
	{
		global $cfg;
		$td = new cHTMLTableData;
		$td->setColSpan(2);
		$td->setVerticalAlignment("middle");
		
		/* Check for icon */
		if ($this->_metaobject->getIcon() != "")
		{
			$img = new cHTMLImage;
			$img->setSrc($this->_metaobject->getIcon());
			$img->applyDimensions();
			$this->_iconWidth = $img->_width;
			$img->setAlignment("absmiddle");
			$image = $img->render();
		}
		$td->setStyle("padding-left: 2px;");
		
		$a = new cHTMLAlignmentTable($image, '<b style="margin: 0px 4px 0px 4px;">'.$this->_title."</b>");
		
		$td->setContent($a);
		$td->setHeight(18);
		$td->setBackgroundColor($cfg['color']['table_header']);
		
		return ($td);
			
	}
	
	function renderGroup ($group)
	{
		return renderRows($group);
		
	}
	
	function renderRows ($group, $padding = 2)
	{
		global $cfg;
		
		foreach ($this->_metaobject->_fields[$group] as $field => $parameters)
		{
			$this->_darkShading = ! $this->_darkShading;
			
			$c = new cHTMLTableRow;
			$b = new cHTMLTableData;
			$l = new cHTMLTableData;
			$r = new cHTMLTableData;
			
			$l->setContent($parameters["name"]);
			$paramname = get_class($this->_metaobject)."_".$field;
			
			$widget = new $parameters["editwidget"]($paramname, $parameters["parameters"]);
			
			$r->setContent($widget);	
			
			$r->setStyle("padding: 2px; border-top: 1px solid ". $cfg["color"]["table_border"]);
			$l->setStyle("padding: 4px; padding-left: {$padding}px; border-top: 1px solid ". $cfg["color"]["table_border"]."; border-right: 1px solid ". $cfg["color"]["table_border"]);
			$l->setVerticalAlignment("top");
			if ($this->_darkShading)
			{
				$l->setBackgroundColor($cfg["color"]["table_dark"]);
				$r->setBackgroundColor($cfg["color"]["table_dark"]);
			} else {
				$l->setBackgroundColor($cfg["color"]["table_light"]);
				$r->setBackgroundColor($cfg["color"]["table_light"]);
			}
			
			$c->setContent(array($l, $r));
			
			$out .= $c->render();
		}
		
		return $out;
	}
	
	function renderButtons ()
	{
		global $cfg;
		
		$c = new cHTMLTableRow;
		$b = new cHTMLTableData;
		
		$b->setStyle("padding: 2px; border-top: 1px solid ".$cfg["color"]["table_border"]);
		$b->setAlignment("right");
		
		$submit = new cHTMLButton("submit");
		$submit->setMode("image");
		$submit->setAccessKey("s");
		$submit->setImageSource("images/buttons/but_ok.gif");
		$submit->setStyle("margin: 0px 1px 0px 1px;");
		$submit->setAlt(i18n("Save changes"));
		
		$b->setColSpan(2);
		$b->setContent($submit);
		$c->setContent($b);
		
		return ($c->render());
			
	}
}
?>