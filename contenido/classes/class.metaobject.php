<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Base Object
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.4
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.metaobject.php 531 2008-07-02 13:30:54Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/* Introduction to metaobjects
 *
 * A metaobject is an object which combines pure API objects with presentation logic.
 * An API object contains API functions for doing actions or data manipulation in general.
 *
 * A metaobject can define the following things:
 *
 * - Map data objects to widgets
 * - Map data into groups
 * - Map actions (from the API objects) to visible actions
 * - Map data fields with flags (searchable, sortable)
 *
 */
 
define("CMETAOBJECT_BASE", 1);
define("CMETAOBJECT_PLUGIN", 2);

class cMetaObject
{
	
	/**
	 * Type of this plugin
     * @var int Contains the object type 
     * @access private
	 */
	var $_objectType;
	
	/**
	 * Object icon
     * @var string Icon filename 
     * @access private
	 */
	var $_iconFilename;
	
	var $_objectInvalid;
	
	var $_payloadObject;
		
	function cMetaObject ($payload = false)
	{
		$this->_actions = array();
		$this->_fields = array();
		$this->_objectInvalid = false;
		
		$this->setPayloadObject($payload);
	}
	
    /**
     * setObjectType ($type)
	 * 
     * Sets the object type (either COBJECT_BASE or COBJECT_PLUGIN).
	 *
	 * @param int $type Constant with the object type
	 *
     */		
	function setObjectType ($type)
	{
		if ($type != COBJECT_BASE &&
			$type != COBJECT_PLUGIN)
		{
			cDie(__FILE__, __LINE__, "When calling setObjectType, please use either COBJECT_BASE or COBJECT_PLUGIN");
		}
		
		$this->_objectType = $type;
	}

    /**
     * setIcon ($icon)
     * Defines the icon for this object
	 * @param string $icon Icon path
	 * @return none
     */	
	function setIcon ($icon = false)
	{
		$icon = Contenido_Security::escapeDB($icon, null);
		$this->_iconFilename = $icon;
	}
	
    /**
     * getIcon ($icon)
     * Returns the icon for this object
	 * @param string $icon Icon path
	 * @return none
     */		
	function getIcon ()
	{
		return $this->_iconFilename;
	}
	
	function setPayloadObject ($object)
	{
		if (is_object($object))
		{
			$this->_payloadObject = $object;
	    	$this->defineActions();
		} else {
			if (class_exists($object))
			{
				$this->_payloadObject = new $object;
				$this->defineActions();
			}	
		}
	}
	
	function getName ()
	{
		return;
	}
	
	function getDescription ()
	{
		return;	
	}
	
	function defineActions ()
	{
	}
	
	function assignField ($field, $name, $editwidget, $parameters = array(), $group = "default", $readonly = false)
	{
		if (!array_key_exists("default", $parameters))
		{
			$parameters["default"] = $this->_payloadObject->get($field);
			$parameters["_payload"] = $this->_payloadObject;
		}
		
		$this->_fields[$group][$field]["name"] = $name;
		$this->_fields[$group][$field]["editwidget"] = $editwidget;
		$this->_fields[$group][$field]["readonly"] = $readonly;	
		$this->_fields[$group][$field]["parameters"] = $parameters;
	}
	
	function setEditAction ($actionclass)
	{
		$args = func_num_args();
		
		unset($this->_editAction);
		
		$this->_editAction = $actionclass;
		$this->_editParams = array();
		
		for ($i=1; $i< $args; $i++)
		{
			$this->_editParams[$i] = func_get_arg($i);	
		}
	}
	
	function setCreateAction ($actionclass)
	{
		$args = func_num_args();
		
		unset($this->_createAction);
		
		$this->_createAction = $actionclass;
		$this->_createParams = array();
		
		for ($i=1; $i< $args; $i++)
		{
			$this->_createParams[$i] = func_get_arg($i);	
		}
	}	
	
	
	function addAction ($actionclass)
	{
		$args = func_num_args();
		
		$this->_actions[$actionclass] = array();
		
		for ($i=1; $i< $args; $i++)
		{
			$this->_actions[$actionclass][$i] = func_get_arg($i);	
		}
	}
	
	function defineFields ()
	{
	}
	
	function defineEditAction ()
	{
	}
	
	function defineCreateAction ()
	{
	}
	
	function processActions ()
	{
		$reload = false;
		
		$actions = $this->getActions();
		
		if (is_array($actions))
		{
    		foreach ($actions as $action)
    		{
    			if ($_GET["action"] == $action->_namedAction)
    			{
    				
	   				/* Collect parameters */
    				foreach ($action->_wantParameters as $parameter)
    				{
    					/* Mangle parameter */
    					if (get_magic_quotes_gpc())
    					{    					
    						$parameters[$parameter] = stripslashes($_GET[$parameter]);
    					} else {
    						$parameters[$parameter] = $_GET[$parameter];
    					}
    				}
    				
    				foreach ($action->_parameters as $parameter => $value)
    				{
    					if (get_magic_quotes_gpc())
    					{
    						$parameters[$parameter] = stripslashes($_GET[$parameter]);
    					} else {
    						$parameters[$parameter] = $_GET[$parameter];
    					}	
    				}
    				
    				if ($action->process($parameters) == true)
    				{
    					$reload = true;	
    				}
    				
    				if ($action->_objectInvalid)
    				{
    					$this->_objectInvalid = true;	
    				}
    			}
    			
    		}
		}
		
		return $reload;
	}
	
	function processEdit ()
	{
		$this->defineFields();
		$modified = false;
		foreach ($this->_fields as $group)
		{
			
			foreach ($group as $field => $params)
			{
				$vname = get_class($this)."_".$field;
				if (array_key_exists($vname, $_GET))
				{
					if (get_magic_quotes_gpc())
    				{    					
						$this->_payloadObject->set($field, stripslashes($_GET[$vname]));
    				} else {
    					$this->_payloadObject->set($field, $_GET[$vname]);
    				}
					$modified = true;
				}	
			}
		}
		
		if ($modified == true)
		{
			$this->_payloadObject->store();	
		}
	}
	
	function processCreate ()
	{
		if ($this->_actionsDefined == false)
		{
			$this->defineActions();
			$this->_actionsDefined = true;
		}
				
		/* Get create action */
		$createaction = $this->getAction($this->_createAction);
		
		if ($createaction != false)
		{
			if ($_GET["action"] == $createaction->_namedAction)
    		{
    			return $createaction->process();
    		}
		} 
		
	}	
	
	function getActions ()
	{
		if ($this->_actionsDefined == false)
		{
			$this->defineActions();
			$this->_actionsDefined = true;
		}

		foreach ($this->_actions as $action => $params)
		{
			$i = $this->getAction($action);
			
			if ($i !== false)
			{
				$actions[] = $i;
			}
		}
		return ($actions);	
	}	
	
	function getAction ($action)
	{
		if (!array_key_exists($action, $this->_actions))
		{
			if ($this->_editAction == $action)
			{
				$params = $this->_editParams;
			} else {
				if ($this->_createAction == $action)
				{
					$params = $this->_createParams;	
				} else {
					return false;	
				}
			}
		} else {
			$params = $this->_actions[$action];	
		}
		
		$myparams = array();
		if (!is_array($params))
		{
			$params = array();
		}
		
		foreach ($params as $param)
		{
			switch (gettype($param))
			{
				case "string" : $myparams[] = '"'.addslashes($param).'"'; break;
				case "boolean": if ($param == true)
								{
									$myparams[] = "true";
								} else {
									$myparams[] = "false";	
								}
								break;
				default:		$myparams[] = $param;
			}
		}
		$statement = '$action = new '.$action."(".implode(", ",$myparams).");";
		eval($statement);
		
		return ($action);	
	}
}
?>