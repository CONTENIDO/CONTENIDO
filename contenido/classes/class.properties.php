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
 * @version    1.2
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
 *   modified 2011-02-05, Murat Purc, cleanup, formatting and documentation.
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


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
 * Field        Size            Description
 * -----        ----            -----------
 * idproperty   int(10)         idproperty (automatically handled by this class)
 * itemtype     varchar(32)     Custom item type (e.g. idcat, idart, idartlang, custom)
 * itemid       varchar(32)     ID of the item
 * type         varchar(32)     Property type
 * name         varchar(32)     Property name
 * value        text            Property value
 * author       varchar(32)     Author (md5-hash of the username)
 * created      datetime        Created date and time
 * modified     datetime        Modified date and time
 * modifiedby   varchar(32)     Modified by (md5-hash of the username)
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
 * type:     category
 * name:     image
 * value:    images/category01.gif
 *
 * idproperty, author, created, modified and modifiedby are automatically handled by
 * the class.
 */


class PropertyCollection extends ItemCollection
{
    public $client;

    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg, $client;
        $this->client = Contenido_Security::toInteger($client);
        parent::__construct($cfg['tab']['properties'], 'idproperty');
        $this->_setItemClass('PropertyItem');
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function PropertyCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    /**
     * Creates a new property item.
     *
     * Example:
     *
     * $properties->create('idcat', 27, 'visual', 'image', 'images/tool.gif');
     *
     * @param   mixed  $itemtype     Type of the item (example: idcat)
     * @param   mixed  $itemid       ID of the item (example: 31)
     * @param   mixed  $type         Type of the data to store (arbitary data)
     * @param   mixed  $name         Entry name
     * @param   mixed  $value        Value
     * @param   bool   $bInternally  Optionally default false (on internal call do not escape parameters again
     * @return  PropertyItem
     */
    public function create($itemtype, $itemid, $type, $name, $value, $bInternally = false)
    {
        global $cfg, $auth;

        $item = parent::create();

        if (!$bInternally) {
            $itemtype   = Contenido_Security::escapeDB($itemtype, null);
            $itemid     = Contenido_Security::escapeDB($itemid, null);
            $value      = Contenido_Security::escapeDB($value, null);
            $type       = Contenido_Security::escapeDB($type, null);
            $name       = Contenido_Security::escapeDB($name, null);
        }

        $item->set('idclient', $this->client);
        $item->set('itemtype', $itemtype, false);
        $item->set('itemid', $itemid, false);
        $item->set('type', $type);
        $item->set('name', $name);
        $item->set('value', $value);

        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->set('author', Contenido_Security::escapeDB($auth->auth['uid'], null));
        $item->store();

        return ($item);
    }


    /**
     * Returns the value for a given item.
     *
     * Example:
     *
     * $file = $properties->getValue('idcat', 27, 'visual', 'image');
     *
     * @param   mixed  $itemtype  Type of the item (example: idcat)
     * @param   mixed  $itemid    ID of the item (example: 31)
     * @param   mixed  $type      Type of the data to store (arbitary data)
     * @param   mixed  $name      Entry name
     * @return  mixed  Value
     */
    public function getValue($itemtype, $itemid, $type, $name, $default = false)
    {
        $itemtype = Contenido_Security::escapeDB($itemtype, null);
        $itemid   = Contenido_Security::escapeDB($itemid, null);
        $type     = Contenido_Security::escapeDB($type, null);
        $name     = Contenido_Security::escapeDB($name, null);

        if (isset($this->client)) {
            $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
        } else {
            $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
        }

        if ($item = $this->next()) {
            return (Contenido_Security::unescapeDB($item->get('value')));
        }

        return $default;
    }


    /**
     * Returns the value for a given item.
     *
     * Example:
     *
     * $file = $properties->getValuesByType('idcat', 27, 'visual');
     *
     * @param   mixed  $itemtype  Type of the item (example: idcat)
     * @param   mixed  $itemid    ID of the item (example: 31)
     * @param   mixed  $type      Type of the data to store (arbitary data)
     * @return  array  Value
     **/
    public function getValuesByType($itemtype, $itemid, $type)
    {
        $aResult  = array();
        $itemtype = Contenido_Security::escapeDB($itemtype, null);
        $itemid   = Contenido_Security::escapeDB($itemid, null);
        $type     = Contenido_Security::escapeDB($type, null);

        if (isset($this->client)) {
            $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."'");
        } else {
            $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."'");
        }

        while ($item = $this->next()) {
            $aResult[$item->get('name')] = Contenido_Security::unescapeDB($item->get('value'));
        }

        return $aResult;
    }


    /**
     * Sets a property item. Handles creation and updating.
     *
     * Example:
     *
     * $properties->setValue('idcat', 27, 'visual', 'image', 'images/tool.gif');
     *
     * @param   mixed  $itemtype  Type of the item (example: idcat)
     * @param   mixed  $itemid    ID of the item (example: 31)
     * @param   mixed  $type      Type of the data to store (arbitary data)
     * @param   mixed  $name      Entry name
     * @param   mixed  $value     Value
     * @param   int    $idProp    Id of database record (if set, update on this basis (possiblity to update name value and type))
     */
    public function setValue($itemtype, $itemid, $type, $name, $value, $idProp = 0)
    {
        $itemtype = Contenido_Security::escapeDB($itemtype, null);
        $itemid   = Contenido_Security::escapeDB($itemid, null);
        $type     = Contenido_Security::escapeDB($type, null);
        $name     = Contenido_Security::escapeDB($name, null);
        $value    = Contenido_Security::escapeDB($value, null);
        $idProp   = Contenido_Security::toInteger($idProp);

        if ($idProp == 0) {
            $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
        } else {
            $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND idproperty = '".$idProp."'");
        }

        if ($item = $this->next()) {
            $item->set('value', $value);
            $item->set('name', $name);
            $item->set('type', $type);
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
     * $properties->deleteValue('idcat', 27, 'visual', 'image');
     *
     * @param  mixed  $itemtype  Type of the item (example: idcat)
     * @param  mixed  $itemid    ID of the item (example: 31)
     * @param  mixed  $type      Type of the data to store (arbitary data)
     * @param  mixed  $name      Entry name
     */
    public function deleteValue($itemtype, $itemid, $type, $name)
    {
        $itemtype = Contenido_Security::escapeDB($itemtype, null);
        $itemid   = Contenido_Security::escapeDB($itemid, null);
        $type     = Contenido_Security::escapeDB($type, null);
        $name     = Contenido_Security::escapeDB($name, null);

        if (isset($this->client)) {
            $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
        } else {
            $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
        }

        if ($item = $this->next()) {
            $this->delete($item->get('idproperty'));
        }
    }


    /**
     * Checks if values for a given item are available.
     *
     * @param   mixed  $itemtype  Type of the item (example: idcat)
     * @param   mixed  $itemid    ID of the item (example: 31)
     * @return  array  For each given item
     */
    public function getProperties($itemtype, $itemid)
    {
        $itemtype = Contenido_Security::escapeDB($itemtype, null);
        $itemid   = Contenido_Security::escapeDB($itemid, null);

        if (isset($this->client)) {
            $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."'");
        } else {
            $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."'");
        }

        $result[$itemid] = false;

        while ($item = $this->next()) {
            // enable accessing property values per number and field name
            $result[$item->get('itemid')][$item->get('idproperty')] = array(
                0=> $item->get('type'),  'type'=>  $item->get('type'),
                1=> $item->get('name'),  'name'=>  $item->get('name'),
                2=> $item->get('value'), 'value'=> $item->get('value')
            );
        }
        return $result;
    }


    /**
     * Returns all datasets selected by given field and value combination
     *
     * @param   mixed  $field       Field to search in
     * @param   mixed  $fieldValue  Value to search for
     * @param   Contenido_Auth  $auth  Narrow result down to user in auth objext
     * @return  array  For each given item
     */
    public function getAllValues($field, $fieldValue, $auth=NULL)
    {
        $authString = '';
        if (!is_null($auth) && sizeof($auth) > 0) {
            $authString .= " AND author = '" . $auth->auth["uid"] . "'";
        }

        if (isset($this->client)) {
            $this->select("idclient = '" . $this->client . "' AND " . $field . " = '" . $fieldValue . "'" . $authString, '' ,'itemid');
        } else {
            $this->select($field . " = '" . $fieldValue . "'" . $authString);
        }

        $retValue = array();
        while ($item = $this->next()) {
            $dbLine = array(
                'idproperty'  => $item->get('idproperty'),
                'idclient'    => $item->get('idclient'),
                'itemtype'    => $item->get('itemtype'),
                'itemid'      => $item->get('itemid'),
                'type'        => $item->get('type'),
                'name'        => $item->get('name'),
                'value'       => $item->get('value'),
                'author'      => $item->get('author'),
                'created'     => $item->get('created'),
                'modified'    => $item->get('modified'),
                'modifiedby'  => $item->get('modifiedby')
            );
            $retValue[] = $dbLine;
        }
        return $retValue;
    }


    /**
     * Delete all properties which match itemtype and itemid
     *
     * @param  mixed  $itemtype  Type of the item (example: idcat)
     * @param  mixed  $itemid    ID of the item (example: 31)
     */
    public function deleteProperties($itemtype, $itemid)
    { 
        $itemtype = Contenido_Security::escapeDB($itemtype, null);
        $itemid   = Contenido_Security::escapeDB($itemid, null);

        if (isset($this->client)) {
            $this->select("idclient = '".$this->client."' AND itemtype = '".$itemtype."' AND itemid = '".$itemid."'");
        } else {
            $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."'");
        }

        $deleteProperties = array();

        while ($item = $this->next()) {
            $deleteProperties[] = $item->get('idproperty');
        }

        foreach($deleteProperties as $idproperty) {
            $this->delete($idproperty);
        }
    }


    public function changeClient($idclient)
    {
        $this->client = $idclient;
    }
}


class PropertyItem extends Item
{
    /**
     * maximumLength: Array which stores the maximum string length of each field
     */
    public $maximumLength;

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['properties'], 'idproperty');

        // Initialize maximum lengths for each column
        $this->maximumLength = array();
        $this->maximumLength['itemtype'] = 64;
        $this->maximumLength['itemid'] = 255;
        $this->maximumLength['type'] = 96;
        $this->maximumLength['name'] = 96;

        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function PropertyItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }

    /**
     * Stores changed PropertyItem
     */
    public function store()
    {
        global $auth;

        $this->set('modified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', $auth->auth['uid']);

        parent::store();
    }

    /**
     * Sets value of a field
     *
	 * @param  string  $field
	 * @param  string  $value
	 * @param  bool    $safe  Flag to run filter on passed value
     */
    public function setField($field, $value, $safe)
    {
        if (array_key_exists($field, $this->maximumLength)) {
            if (strlen($value) > $this->maximumLength[$field]) {
                cWarning(__FILE__, __LINE__, "Tried to set field $field to value $value, but the field is too small. Truncated.");
            }
        }

        parent::setField($field, $value, $safe);
    }
}

?>