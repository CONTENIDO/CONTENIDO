<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Custom properties
 *
 * Code is taken over from file contenido/classes/class.properties.php in favor of
 * normalizing API.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    0.1
 * @author     Timo A. Hummel
 * @author     Murat Purc
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2011-10-11
 *   created  2011-11-10, Murat Purc, added caching method getValuesOnlyByTypeName()
 *
 *   $Id: $:
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
 * in CONTENIDO and underlaying websites.
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
 *
 *
 * If caching is enabled, see $cfg['properties']['properties']['enable_cache'],
 * configured entries will be loaded at first time.
 * If enabled, each call of cApiPropertyCollection functions to retrieve cacheable
 * properties will return the cached entries without stressing the database.
 *
 * The cApiPropertyCollection class keeps also track of changed and deleted
 * properties and synchronizes them with cached values, as long as you use the
 * interface of cApiPropertyCollection to manage the properties.
 */

/**
 * Property collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiPropertyCollection extends ItemCollection
{
    /**
     * Client id
     * @var int
     */
    public $client;

    /**
     * List of cached entries
     * @var array
     */
    protected static $_entries;

    /**
     * Flag to enable caching.
     * @var bool
     */
    protected static $_enableCache;

    /**
     * Itemtypes and itemids array
     * @var array
     */
    protected static $_cacheItemtypes;


    /**
     * Constructor Function
     */
    public function __construct()
    {
        global $cfg, $client, $lang;
        $this->client = Contenido_Security::toInteger($client);
        parent::__construct($cfg['tab']['properties'], 'idproperty');
        $this->_setItemClass('cApiProperty');

        if (!isset(self::$_enableCache)) {
            if (isset($cfg['properties']) && isset($cfg['properties']['properties'])
                && isset($cfg['properties']['properties']['enable_cache']))
            {
                self::$_enableCache = (bool) $cfg['properties']['properties']['enable_cache'];

                if (isset($cfg['properties']['properties']['itemtypes'])
                    && is_array($cfg['properties']['properties']['itemtypes']))
                {
                    self::$_cacheItemtypes = $cfg['properties']['properties']['itemtypes'];
                    foreach (self::$_cacheItemtypes as $name => $value) {
                        if ('%client%' == $value) {
                            self::$_cacheItemtypes[$name] = (int) $client;
                        } elseif ('%lang%' == $value) {
                            self::$_cacheItemtypes[$name] = (int) $lang;
                        } else {
                            unset(self::$_cacheItemtypes[$name]);
                        }
                    }
                }
            } else {
                self::$_enableCache = false;
            }
        }

        if (self::$_enableCache && !isset(self::$_entries)) {
            $this->_loadFromCache();
        }
    }


    /**
     * Resets the states of static properties.
     */
    public static function reset()
    {
        unset(self::$_enableCache, self::$_entries, self::$_cacheItemtypes);
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
     * @return  cApiProperty
     */
    public function create($itemtype, $itemid, $type, $name, $value, $bInternally = false)
    {
        global $cfg, $auth;

        $item = parent::create();

        if (!$bInternally) {
            $itemtype   = $this->db->escape($itemtype);
            $itemid     = $this->db->escape($itemid);
            $value      = $this->db->escape($value);
            $type       = $this->db->escape($type);
            $name       = $this->db->escape($name);
        }

        $item->set('idclient', $this->client);
        $item->set('itemtype', $itemtype, false);
        $item->set('itemid', $itemid, false);
        $item->set('type', $type);
        $item->set('name', $name);
        $item->set('value', $value);

        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->set('author', $this->db->escape($auth->auth['uid']));
        $item->store();

        if ($this->_useCache($itemtype, $itemid)) {
            $this->_addToCache($item);
        }

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
        if ($this->_useCache($itemtype, $itemid)) {
            return $this->_getValueFromCache($itemtype, $itemid, $type, $name, $default);
        }

        $itemtype = $this->db->escape($itemtype);
        $itemid   = $this->db->escape($itemid);
        $type     = $this->db->escape($type);
        $name     = $this->db->escape($name);

        if (isset($this->client)) {
            $this->select("idclient = ".(int)$this->client." AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
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
        if ($this->_useCache($itemtype, $itemid)) {
            return $this->_getValuesByTypeFromCache($itemtype, $itemid, $type);
        }

        $aResult  = array();
        $itemtype = $this->db->escape($itemtype);
        $itemid   = $this->db->escape($itemid);
        $type     = $this->db->escape($type);

        if (isset($this->client)) {
            $this->select("idclient = ".(int)$this->client." AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."'");
        } else {
            $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."'");
        }

        while ($item = $this->next()) {
            $aResult[$item->get('name')] = Contenido_Security::unescapeDB($item->get('value'));
        }

        return $aResult;
    }


    /**
     * Returns the values only by type and name.
     *
     * Example:
     *
     * $file = $properties->getValuesOnlyByTypeName('note', 'category');
     *
     * @param   mixed  $itemtype  Type of the item (example: idcat)
     * @param   mixed  $name      Type of the data to store (arbitary data)
     * @return  array  Value
     **/
    public function getValuesOnlyByTypeName($type, $name)
    {
        $aResult = array();
        $type = $this->db->escape($type);
        $name = $this->db->escape($name);

        $this->select("type = '" . $type . "' AND name = '" . $name . "");

        while ($item = $this->next()) {
            $aResult[] = Contenido_Security::unescapeDB($item->get('value'));
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
        $itemtype = $this->db->escape($itemtype);
        $itemid   = $this->db->escape($itemid);
        $type     = $this->db->escape($type);
        $name     = $this->db->escape($name);
        $value    = $this->db->escape($value);
        $idProp   = (int)$idProp;

        if ($idProp == 0) {
            $this->select("idclient = ".(int)$this->client." AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
        } else {
            $this->select("idclient = ".(int)$this->client." AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND idproperty = ".$idProp);
        }

        if ($item = $this->next()) {
            $item->set('value', $value);
            $item->set('name', $name);
            $item->set('type', $type);
            $item->store();

            if ($this->_useCache($itemtype, $itemid)) {
                $this->_addToCache($item);
            }
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
        $itemtype = $this->db->escape($itemtype);
        $itemid   = $this->db->escape($itemid);
        $type     = $this->db->escape($type);
        $name     = $this->db->escape($name);

        if (isset($this->client)) {
            $this->select("idclient = ".(int)$this->client." AND itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
        } else {
            $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."' AND type = '".$type."' AND name = '".$name."'");
        }

        if ($item = $this->next()) {
            $this->delete($item->get('idproperty'));
            if ($this->_useCache()) {
                $this->_deleteFromCache($item->get('idproperty'));
            }
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
        if ($this->_useCache($itemtype, $itemid)) {
            return $this->_getPropertiesFromCache($itemtype, $itemid, $type);
        }

        $itemtype = $this->db->escape($itemtype);
        $itemid   = $this->db->escape($itemid);

        if (isset($this->client)) {
            $this->select("idclient = ".(int)$this->client." AND itemtype = '".$itemtype."' AND itemid = '".$itemid."'");
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
            $this->select("idclient = " . (int) $this->client . " AND " . $field . " = '" . $fieldValue . "'" . $authString, '' ,'itemid');
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
        $itemtype = $this->db->escape($itemtype);
        $itemid   = $this->db->escape($itemid);

        if (isset($this->client)) {
            $this->select("idclient = ".(int)$this->client." AND itemtype = '".$itemtype."' AND itemid = '".$itemid."'");
        } else {
            $this->select("itemtype = '".$itemtype."' AND itemid = '".$itemid."'");
        }

        $deleteProperties = array();

        while ($item = $this->next()) {
            $deleteProperties[] = $item->get('idproperty');
        }

        foreach($deleteProperties as $idproperty) {
            $this->delete($idproperty);
            if ($this->_useCache()) {
                $this->_deleteFromCache($idproperty);
            }
        }
    }


    /**
     * Changes the client
     *
     * @param  int  $idclient
     */
    public function changeClient($idclient)
    {
        $this->client = (int) $idclient;
    }


    /**
     * Loads/Caches configured properties.
     */
    protected function _loadFromCache()
    {
        global $client;
        if (!isset(self::$_entries)) {
            self::$_entries = array();
        }

        $where = array();
        foreach (self::$_cacheItemtypes as $itemtype => $itemid) {
            if (is_numeric($itemid)) {
                $where[] = "(itemtype = '" . $itemtype . "' AND itemid = " . $itemid . ")";
            } else {
                $where[] = "(itemtype = '" . $itemtype . "' AND itemid = '" . $itemid . "')";
            }
        }

        if (count($where) == 0) {
            return;
        }

        $where = "idclient = " . (int) $client . ' AND ' . implode(' OR ', $where);
        $this->select($where);
        while ($property = $this->next()) {
            $this->_addToCache($property);
        }
    }

    protected function _useCache($itemtype = null, $itemid = null)
    {
        global $client;
        $ok = (self::$_enableCache && $this->client == $client);
        if (!$ok) {
            return $ok;
        } elseif ($itemtype == null || $itemid == null) {
            return $ok;
        }

        foreach (self::$_cacheItemtypes as $name => $value) {
            if ($itemtype == $itemtype || $itemid == $itemid) {
                return true;
            }
        }
    }


    /**
     * Adds a entry to the cache.
     * @param  cApiUserProperty  $entry
     */
    protected function _addToCache($entry)
    {
        global $client;
        $data = $entry->toArray();
        self::$_entries[$data['idproperty']] = $data;
    }


    /**
     * Removes a entry from cache.
     * @param   int  $id
     */
    protected function _deleteFromCache($id)
    {
        if (isset(self::$_entries[$id])) {
            unset(self::$_entries[$id]);
        }
    }


    /**
     * Returns the value for a given item from cache.
     *
     * @param   mixed  $itemtype  Type of the item (example: idcat)
     * @param   mixed  $itemid    ID of the item (example: 31)
     * @param   mixed  $type      Type of the data to store (arbitary data)
     * @param   mixed  $name      Entry name
     * @return  mixed  Value
     */
    protected function _getValueFromCache($itemtype, $itemid, $type, $name, $default = false)
    {
        foreach (self::$_entries as $id => $entry) {
            if ($entry['itemtype'] == $itemtype && $entry['itemid'] == $itemid
                && $entry['type'] == $type && $entry['name'] == $name)
            {
                return Contenido_Security::unescapeDB($entry['value']);
            }
        }

        return $default;
    }


    /**
     * Returns the values for a given item by its type from cache.
     *
     * @param   mixed  $itemtype  Type of the item (example: idcat)
     * @param   mixed  $itemid    ID of the item (example: 31)
     * @param   mixed  $type      Type of the data to store (arbitary data)
     * @return  array  Value
     **/
    protected function _getValuesByTypeFromCache($itemtype, $itemid, $type)
    {
        $result = array();

        foreach (self::$_entries as $id => $entry) {
            if ($entry['itemtype'] == $itemtype && $entry['itemid'] == $itemid
                && $entry['type'] == $type)
            {
                $result[$entry['name']] = Contenido_Security::unescapeDB($entry['value']);
            }
        }

        return $result;
    }


    /**
     * Returns poperties for given item are available.
     *
     * @param   mixed  $itemtype  Type of the item (example: idcat)
     * @param   mixed  $itemid    ID of the item (example: 31)
     * @return  array  For each given item
     */
    public function _getPropertiesFromCache($itemtype, $itemid)
    {
        $result = array();
        $result[$itemid] = false;

        foreach (self::$_entries as $id => $entry) {
            if ($entry['itemtype'] == $itemtype && $entry['itemid'] == $itemid) {
                // enable accessing property values per number and field name
                $result[$entry['itemid']][$entry['idproperty']] = array(
                    0 => $entry['type'],  'type' =>  $entry['type'],
                    1 => $entry['name'],  'name' =>  $entry['name'],
                    2 => $entry['value'], 'value' => $entry['value']
                );
            }
        }

        return $result;
    }

}


/**
 * Property item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiProperty extends Item
{
    /**
     * Array which stores the maximum string length of each field
     * @var  array
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


    /**
     * Stores changed cApiProperty
     * @return  bool
     */
    public function store()
    {
        global $auth;

        $this->set('modified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', $auth->auth['uid']);

        return parent::store();
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


################################################################################
# Old versions of property item collection and property item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in 
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.


/**
 * Property collection
 * @deprecated  [2011-10-11] Use cApiPropertyCollection instead of this class.
 */
class PropertyCollection extends cApiPropertyCollection
{
    public function __construct()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct();
    }
    public function PropertyCollection()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct();
    }
}


/**
 * Single property item
 * @deprecated  [2011-10-11] Use cApiProperty instead of this class.
 */
class PropertyItem extends cApiProperty
{
    public function __construct($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct($mId);
    }
    public function PropertyItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }
}

?>