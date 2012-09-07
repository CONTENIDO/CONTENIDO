<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Custom properties
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.1.6
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-12-21
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2009-09-27, Dominik Ziegler, fixed wrong (un)escaping
 *
 *   $Id: class.properties.php 1058 2009-09-28 12:19:15Z dominik.ziegler $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.genericdb.php");

/* Custom properties 
 * -----------------
 *
 * Custom properties are properties which can be assigned to virtually any element
 * in Contenido and underlaying websites.
 *
 * 
 * Table structure
 * ---------------
 *
 * Field		Size			Description
 * -----		----			-----------
 * idproperty	int(10)			idproperty (automatically handled by this class)
 * itemtype		varchar(32)		Custom item type (e.g. idcat, idart, idartlang, custom)
 * itemid		varchar(32)		ID of the item
 * type			varchar(32)		Property type
 * name			varchar(32)		Property name
 * value		text			Property value
 * author		varchar(32)		Author (md5-hash of the username)
 * created		datetime		Created date and time
 * modified		datetime		Modified date and time
 * modifiedby	varchar(32)		Modified by (md5-hash of the username)
 *
 *
 * Example:
 * -------- 
 * A module needs to store custom properties for categories. Modifying the database
 * would be a bad thing, since the changes might get lost during an upgrade or
 * reinstall.
 * 
 * If the custom property for a category would be the path to a category image,
 * we would fill a row as follows:
 * 
 * itemtype: idcat
 * itemid:   <number of your category>
 * type:	 category
 * name:	 image
 * value:	 images/category01.gif
 *
 * idproperty, author, created, modified and modifiedby are automatically handled by
 * the class.
 */
 

class PropertyCollection extends ItemCollection
{
	var $client;
	
	/**
     * Constructor Function
     * @param none
     */
	function PropertyCollection ()
	{
		global $cfg, $client;
		
		$this->client = Contenido_Security::toInteger($client);
		
		parent::ItemCollection($cfg["tab"]["properties"], "idproperty");
		
		$this->_setItemClass("PropertyItem");
	
	}
	
	/*
	 * Creates a new property item.
	 *
	 * Example:
	 *
	 *
	 * $properties->create("idcat", 27, "visual", "image", "images/tool.gif");
	 *
	 * @param itemtype 	mixed Type of the item (example: idcat)
 	 * @param itemid 	mixed ID of the item (example: 31)
 	 * @param type	 	mixed Type of the data to store (arbitary data)
 	 * @param name		mixed Entry name
	 * @param value		mixed Value
	 * @param bInternally boolean - optionally default false (on internal call do not escape parameters again
	 */
	function create ($itemtype, $itemid, $type, $name, $value, $bInternally = false)
	{
		global $cfg, $auth;
		
		$item = parent::create();
		
		if (!$bInternally) {
			$itemtype 	= Contenido_Security::escapeDB($itemtype, null);
			$itemid 	= Contenido_Security::escapeDB($itemid, null);
			$value 		= Contenido_Security::escapeDB($value, null);
			$type 		= Contenido_Security::escapeDB($type, null);
			$name 		= Contenido_Security::escapeDB($name, null);
		}

		$item->set("idclient", $this->client);
		$item->set("itemtype", $itemtype, false);
		$item->set("itemid", $itemid, false);
		$item->set("type", $type);
		$item->set("name", $name);
		$item->set("value", $value);

		$item->set("created", date("Y-m-d H:i:s"), false);
		$item->set("author", Contenido_Security::escapeDB($auth->auth["uid"], null));
		$item->store();

		return ($item);	
	}
	
	function delete ($id)
	{
		return parent::delete($id);
	}
	
	/**
	 * Returns the value for a given item.
	 *
	 * Example:
	 *
	 * $file = $properties->getValue("idcat", 27, "visual", "image");
	 *
	 * @param itemtype 	mixed Type of the item (example: idcat)
 	 * @param itemid 	mixed ID of the item (example: 31)
 	 * @param type	 	mixed Type of the data to store (arbitary data)
 	 * @param name		mixed Entry name
	 * @return mixed Value
	 **/	
	function getValue ($itemtype, $itemid, $type, $name, $default = false)
	{
		$itemtype 	= Contenido_Security::escapeDB($itemtype, null);
		$itemid 	= Contenido_Security::escapeDB($itemid, null);
		$type 		= Contenido_Security::escapeDB($type, null);
		$name 		= Contenido_Security::escapeDB($name, null);
		
		if (isset($this->client))
		{		
			$this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
		} else {
			$this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");			
		}
		
		if ($item = $this->next())
		{
			return (Contenido_Security::unescapeDB($item->get("value")));
		}
		
		return $default;
	}
	
	/**
	 * Returns the value for a given item.
	 * 
	 * Example:
	 *
	 * $file = $properties->getValuesByType("idcat", 27, "visual");
	 *
	 * @param itemtype	mixed Type of the item (example: idcat)
	 * @param itemid	mixed ID of the item (example: 31)
	 * @param type		mixed Type of the data to store (arbitary data)
	 * @return array Value
	 **/
	function getValuesByType ($itemtype, $itemid, $type)
	{
		$aResult = array();
		$itemtype 	= Contenido_Security::escapeDB($itemtype, null);
		$itemid 	= Contenido_Security::escapeDB($itemid, null);
		$type 		= Contenido_Security::escapeDB($type, null);
		
		if (isset($this->client))
		{		
			$this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."'");
		} else {
			$this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."'");			
		}
		
		while ($item = $this->next())
		{
			$aResult[$item->get("name")] = Contenido_Security::unescapeDB($item->get("value"));
		}
		
		return $aResult;
	}
	
	/**
	 * Sets a property item. Handles creation and updating.
	 *
	 * Example:
	 *
	 * $properties->setValue("idcat", 27, "visual", "image", "images/tool.gif");
	 *
	 * @param itemtype 	 mixed Type of the item (example: idcat)
 	 * @param itemid 	 mixed ID of the item (example: 31)
 	 * @param type	 	 mixed Type of the data to store (arbitary data)
 	 * @param name		 mixed Entry name
	 * @param value		 mixed Value
      * @param idProp	 int id of database record (if set, update on this basis (possiblity to update name value and type))
	 **/	
	function setValue ($itemtype, $itemid, $type, $name, $value, $idProp = 0)
	{

		$itemtype 	= Contenido_Security::escapeDB($itemtype, null);
		$itemid 	= Contenido_Security::escapeDB($itemid, null);
		$type 		= Contenido_Security::escapeDB($type, null);
		$name 		= Contenido_Security::escapeDB($name, null);
		$value 		= Contenido_Security::escapeDB($value, null);
		$idProp 	= Contenido_Security::toInteger($idProp);
		
        if ($idProp == 0) {
            $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
		} else {
            $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND idproperty = '".$idProp."'");
        }
        if ($item = $this->next())
		{
			$item->set("value", $value);
            $item->set("name", $name);
            $item->set("type", $type);
			$item->store();
		} else {
			$this->create($itemtype, $itemid, $type, $name, $value, true);	
		}
	}
	
	/**
    * Delete a property item. 
    * 
    * Example: 
    * 
    * $properties->deleteValue("idcat", 27, "visual", "image"); 
    * 
    * @param itemtype   mixed Type of the item (example: idcat) 
    * @param itemid   	mixed ID of the item (example: 31) 
    * @param type      	mixed Type of the data to store (arbitary data) 
    * @param name      	mixed Entry name 
    */ 
   function deleteValue ($itemtype, $itemid, $type, $name) 
   { 
   		$itemtype 	= Contenido_Security::escapeDB($itemtype, null);
		$itemid 	= Contenido_Security::escapeDB($itemid, null);
		$type 		= Contenido_Security::escapeDB($type, null);
		$name 		= Contenido_Security::escapeDB($name, null);
		
      if (isset($this->client)) 
      { 
         $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'"); 
      } else { 
         $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'"); 
      } 

      if ($item = $this->next()) 
      { 
         $this->delete($item->get("idproperty")); 
      } 
   } 
	
	/** 
    * Checks if values for a given item are available. 
    * 
    * @param itemtype   mixed Type of the item (example: idcat) 
    * @param itemid   mixed ID of the item (example: 31) 
    * 
    * @return         array for each given item 
    */ 
   function getProperties ($itemtype, $itemid) 
   { 
		$itemtype 	= Contenido_Security::escapeDB($itemtype, null);
		$itemid 	= Contenido_Security::escapeDB($itemid, null);

      if (isset($this->client)) 
      { 
         $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."'"); 
      } else { 
         $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."'"); 
      } 

      $result[$itemid] = false; 

      while ($item = $this->next()) 
      { 
         // enable accessing property values per number and field name 
         $result[$item->get("itemid")][$item->get("idproperty")] = Array (0=>$item->get("type"),  "type"=>$item->get("type"), 
                                                                1=>$item->get("name"),  "name"=>$item->get("name"), 
                                                                2=>$item->get("value"), "value"=>$item->get("value")); 
      } 
      return $result; 
   } 

	/** 
    * Returns all datasets selected by given field and value combination
    * 
    * @param $field 			Field to search in 
    * @param $fieldValue 	Value to search for
    * @param $auth				Narrow result down to user in auth objext
    * 
    * @return         array for each given item 
    */ 
   function getAllValues($field, $fieldValue, $auth=NULL) 
   {
   		$authString = '';
			if( !is_null($auth) && sizeof($auth) > 0 )
   		{
   			$authString .= " AND author = '" . $auth->auth["uid"] . "'"; 
   		}
   	
      if (isset($this->client)) 
      { 
         $this->select("idclient = '" . $this->client . "' AND " . $field . " = '" . $fieldValue . "'" . $authString, '' ,'itemid'); 
      } 
      else 
      { 
         $this->select($field . " = '" . $fieldValue . "'" . $authString);
      } 

      $retValue = array();
      while ($item = $this->next()) 
      {
      	$dbLine = array(
      		'idproperty' 	=> $item->get("idproperty"),
      		'idclient' 		=> $item->get("idclient"),
      		'itemtype' 		=> $item->get("itemtype"),
      		'itemid' 		=> $item->get("itemid"),
      		'type' 			=> $item->get("type"),
      		'name' 			=> $item->get("name"),
      		'value' 		=> $item->get("value"),
      		'author' 		=> $item->get("author"),
      		'created' 		=> $item->get("created"),
      		'modified' 		=> $item->get("modified"),
      		'modifiedby'	=> $item->get("modifiedby")
      	);
      	$retValue[] = $dbLine;
      }
   	return $retValue;
   } 
   
   /** 
    * Delete all properties which match itemtype and itemid 
    * 
    * @param itemtype   mixed Type of the item (example: idcat) 
    * @param itemid   mixed ID of the item (example: 31) 
    */ 
   function deleteProperties ($itemtype, $itemid) 
   { 
   		$itemtype 	= Contenido_Security::escapeDB($itemtype, null);
		$itemid 	= Contenido_Security::escapeDB($itemid, null);
		
      if (isset($this->client)) 
      { 
         $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."'"); 
      } else { 
         $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."'"); 
      } 

		$deleteProperties = Array(); 

		while ($item = $this->next()) 
		{ 
			$deleteProperties[] = $item->get("idproperty"); 
		} 

		foreach($deleteProperties as $idproperty) { 
			$this->delete($idproperty); 
		} 
	}
	
	function changeClient($idclient)
	{
		$this->client = $idclient;
	}
}

class PropertyItem extends Item
{
	/**
	 * maximumLength: Array which stores the maximum string length of each field
	 */
	var $maximumLength;	

	/**
	  * Constructor Function
	  * @param $id int Specifies the ID to load
	  */
	function PropertyItem ()
	{
		global $cfg;
		parent::Item($cfg["tab"]["properties"], "idproperty");
		
		/* Initialize maximum lengths for each column */
		$this->maximumLength = array();
		$this->maximumLength["itemtype"] = 64;
		$this->maximumLength["itemid"] = 255;
		$this->maximumLength["type"] = 96;
		$this->maximumLength["name"] = 96;		
	}
	
	function store ()
	{
		global $auth;
		
		$this->set("modified", date("Y-m-d H:i:s"), false);
		$this->set("modifiedby", $auth->auth["uid"]);
		
		parent::store();
	}
	
	function setField ($field, $value, $safe)
	{
		if (array_key_exists($field, $this->maximumLength))
		{
			if (strlen($value) > $this->maximumLength[$field])
			{
				cWarning(__FILE__, __LINE__, "Tried to set field $field to value $value, but the field is too small. Truncated.");	
			}	
		}
		
		parent::setField($field, $value, $safe);
	}
}
?>