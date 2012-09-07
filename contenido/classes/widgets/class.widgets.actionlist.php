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
 * @version    1.0
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 
 *   
 *   $Id: class.widgets.actionlist.php 738 2008-08-27 10:21:19Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
class cWidgetMenuActionList extends cFoldingRow
{
	function cWidgetMenuActionList($uuid, $title, $dataClassName)
	{ 
		global $cfg;
		
		if (!class_exists($dataClassName))
		{
			cWarning(__FILE__, __LINE__, "Could not instanciate class [$dataClassName] for use in class ".get_class($this));
			return false;
		} else {
			$dataClass = new $dataClassName;
			
			if (!is_subclass_of($dataClass, "Item"))
			{
				cWarning(__FILE__, __LINE__, "Passed class [$dataClassName] should be a subclass of [Item]. Parent class is ".get_parent_class($dataClass));
				return;
			}
			
			$this->_metaClass = $dataClass->getMetaObject();
		}
		
		cFoldingRow::cFoldingRow($uuid, $title);
		
		$this->_headerData->setBackgroundColor($cfg['color']['table_subheader']);
		$this->_headerData->setStyle("font-weight: bold; text-decoration: none; border-bottom: 1px solid ".$cfg['color']['table_border'].";");
		$this->_headerData->setHeight(18);
		$this->_headerData->setWidth("100%");
		$this->_contentData->setWidth("100%");
		$this->_link->setStyle("text-decoration: none;");
		$this->_contentData->setStyle("font-weight: bold; border-bottom: 1px solid ".$cfg['color']['table_border'].";");
		
		$this->_dark = true;
		
		
		$actions = array($this->_metaClass->_createAction);
		
		$row = array();
		
		foreach ($actions as $action)
		{
			$row[] = $this->buildAction($action);	
		}
		$t = new cHTMLTable;
		$t->setContent($row);
		$t->setWidth("100%");
		
		$this->_contentData->setContent($t);
		
	}
	
	function buildAction ($action)
	{
		global $cfg;
		
		if (class_exists($action))
		{
			$this->_dark = !$this->_dark;
			$class = $this->_metaClass->getAction($action);
		
			$row = new cHTMLTableRow;
			$l = new cHTMLTableData;
			$r = new cHTMLTableData;
			
			$l->setContent($class->render());
			$r->setContent($class->renderText());
			$l->setStyle("padding-left: 14px");
			$r->setStyle("padding-left: 4px");
			$l->setHeight(18);
			$r->setHeight(18);
			$r->setWidth("100%");
			
			if ($this->_dark)
			{
				$l->setBackgroundColor($cfg["color"]["table_dark"]);
				$r->setBackgroundColor($cfg["color"]["table_dark"]);
			} else {
				$l->setBackgroundColor($cfg["color"]["table_light"]);	
				$r->setBackgroundColor($cfg["color"]["table_light"]);
			}
			
			$row->setContent(array($l,$r));
			
			return $row;
			
		}	
	}
	
	
	
}

?>