<?php
/**
 * This file contains the tree item class.
 *
 * @package    Core
 * @subpackage GUI
 * @version    SVN Revision $Rev:$
 *
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Tree item class
 *
 * @package    Core
 * @subpackage GUI
 */
class cTreeItem {

    /**
     * Sub Items for this tree item
     *
     * @var array
     */
    var $_subitems = array();

    /**
     * Determinates if this tree item is collapsed
     *
     * @var boolean
     */
    var $_collapsed;

    /**
     * ID for this item
     *
     * @var string
     */
    var $_id;

    /**
     * Name for this item
     *
     * @var string
     */
    var $_name;

    /**
     * Contains the level of this item
     *
     * @var integer
     */
    var $_level;

    /**
     * Contains custom entries
     *
     * @var array
     */
    var $_attributes = array();

    /**
     * Contains the parent of this item
     *
     * @var array
     */
    var $_parent = false;

    /**
     * Contains the next item
     *
     * @var array
     */
    var $_next = false;

    /**
     * Contains the previous item
     *
     * @var array
     */
    var $_previous = false;

    public function __construct($id = "", $name = "", $collapsed = false) {
        $this->_id = $id;
        $this->_name = $name;
        $this->_collapsed = $collapsed;
    }

    /**
     * Id getter
     *
     * @return string
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Name getter
     *
     * @return string
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Collapsed state getter
     *
     * @return bool
     */
    public function getCollapsed() {
        return $this->_collapsed;
    }

    /**
     * Imports a table from an array of arrays.
     * Array format:
     * array(
     * array("id" => "Item ID", "name" => "Item name", "level" => 1, "collapsed"
     * => true|false, "attributes" => array("attr_name" => "attr_value"))
     * );
     *
     * The entries "collapsed" and "attributes" are optional!
     *
     * @param array $flat_array See above
     */
    public function importTable($flat_array) {
        $lastobj[0] = $this->_id;
        $currentlevel = 1;

        if (!is_array($flat_array)) {
            return false;
        }

        foreach ($flat_array as $item) {
            $mitem[$item["id"]] = new cTreeItem($item["id"], $item["name"]);

            if ($item["level"] > $currentlevel) {
                $currentlevel++;
            }

            if ($item["level"] < $currentlevel) {
                $currentlevel = $item["level"];
            }

            if (is_array($item["attributes"])) {
                $mitem[$item["id"]]->setAttributes($item["attributes"]);
            }

            if (array_key_exists("collapsed", $item)) {
                $mitem[$item["id"]]->setCollapsed($item["collapsed"]);
            }

            /* Set payload object */
            if (array_key_exists("payload", $item)) {
                $mitem[$item["id"]]->setPayloadObject($item["payload"]);
            }

            if (is_object($mitem[$lastobj[$currentlevel - 1]])) {
                $mitem[$lastobj[$currentlevel - 1]]->addItem($mitem[$item["id"]]);
            } else {
                $this->addItemToID($lastobj[$currentlevel - 1], $mitem[$item["id"]]);
            }

            $lastobj[$currentlevel] = $item["id"];
        }
    }

    public function importStructuredArray($array) {
        $i = array();

        $lastid = 1;
        $level = 1;

        $this->_flattenArray($array, $i, $lastid, $level);

        $this->importTable($i);
    }

    protected function _flattenArray($sourcearray, &$destarray, &$lastid, &$level) {
        if ($lastid == false) {
            $lastid = 1;
        }

        if ($level == false) {
            $level = 1;
        }

        if (!is_array($sourcearray)) {
            return false;
        }

        foreach ($sourcearray as $id => $item) {
            $lastid++;
            $destarray[$lastid]["id"] = $item["class"] . "." . $id;

            // Name should be fetched via the meta object
            $meta = $item["object"]->getMetaObject();

            if (is_object($meta)) {
                $destarray[$lastid]["name"] = $meta->getName();
            }

            $destarray[$lastid]["level"] = $level;
            $destarray[$lastid]["payload"] = $item["object"];

            if (count($item["items"]) > 0) {
                $level++;
                $this->_flattenArray($item["items"], $destarray, $lastid, $level);
                $level--;
            }
        }
    }

    /**
     * adds an item as a subitem to the current item
     *
     * @param cTreeItem item item object to add
     */
    public function addItem(&$item) {
        // Update last item
        if (($lastitem = end($this->_subitems)) !== false) {
            $this->_subitems[key($this->_subitems)]->_next = $item->_id;
        }

        $this->_subitems[count($this->_subitems)] = &$item;
        $item->_parent = $this->_id;
        $item->_previous = $lastitem->_id;
    }

    /**
     * adds an item to a specific ID
     *
     * @param string $id ID to add the item to
     * @param cTreeItem $item Item to add
     */
    public function addItemToID($id, &$item) {
        if ($this->_id == $id) {
            // Update last item
            if ($lastitem = end($this->_subitems) !== false) {
                $this->_subitems[key($this->_subitems)]->_next = $item->_id;
            }

            $this->_subitems[count($this->_subitems)] = &$item;
            $item->_parent = $this->_id;
            $item->_previous = $lastitem->_id;
            return true;
        } else {
            foreach (array_keys($this->_subitems) as $key) {
                $result = $this->_subitems[$key]->addItemToID($id, $item);
                if ($result == true) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * moves an item to another object
     *
     * @param cTreeItem targetItem Item to move the subitem to
     * @param mixed itemToMove cTreeItem-Object or id of object to move
     */
    public function moveItem($targetItem, $itemToMove) {
    }

    /**
     * deletes a subitem
     *
     * @param mixed item object or ID to delete
     * @return deleted object
     */
    public function deleteItem($id) {
        foreach (array_keys($this->_subitems) as $key) {
            if ($this->_subitems[$key]->_id == $id) {
                // Fetch next item, reset to current item
                $nextitem = next($this->_subitems);
                $nkey = key($this->_subitems);
                prev($this->_subitems);

                $previtem = &prev($this->_subitems);
                $pkey = key($this->_subitems);
                next($this->_subitems);

                if ($nextitem !== false) {
                    if ($previtem !== false) {
                        $this->_subitems[$nkey]->_previous = $this->_subitems[$pkey]->_id;
                    }
                }

                if ($previtem !== false) {
                    if ($nextitem !== false) {
                        $this->_subitems[$pkey]->_next = $this->_subitems[$nkey]->_id;
                    }
                }

                $itemcopy = $this->_subitems[$key];
                unset($this->_subitems[$key]);

                return ($itemcopy);
            } else {
                $this->_subitems[$key]->deleteItem($id);
            }
        }
    }

    /**
     * Retrieves a specific item by its ID.
     * Note that this
     * function traverses all subitems to find the correct item.
     *
     * @param string id ID to retrieve
     * @return cTreeItem
     */
    public function &getItemByID($id) {
        if ($this->_id == $id) {
            return $this;
        } else {
            foreach (array_keys($this->_subitems) as $key) {
                $retObj = &$this->_subitems[$key]->getItemByID($id);
                if ($retObj->_id == $id) {
                    return $retObj;
                }
            }
        }

        return false;
    }

    /**
     * sets a custom attribute for this TreeItem
     *
     * @param string attributeName
     * @param array attributeValue The value(s) of the attribute
     */
    public function setAttribute($attributeName, $attributeValue) {
        $this->_attributes[$attributeName] = $attributeValue;
    }

    /**
     * sets a bunch of attributes
     *
     * @param string attributeName
     * @param array attributeValue The value(s) of the attribute
     */
    public function setAttributes($aAttributeArray) {
        $this->_attributes = array_merge($aAttributeArray, $this->_attributes);
    }

    /**
     * returns an attribute
     *
     * @param string attributeName
     * @return mixed
     */
    public function getAttribute($attributeName) {
        if (array_key_exists($attributeName, $this->_attributes)) {
            return ($this->_attributes[$attributeName]);
        } else {
            return false;
        }
    }

    /**
     * deletes an attribute
     *
     * @param string attributeName
     */
    public function deleteAttribute($attributeName) {
        if (array_key_exists($attributeName, $this->_attributes)) {
            unset($this->_attributes[$attributeName]);
            return true;
        } else {
            return false;
        }
    }

    public function hasAttribute($attributeName, $bRecursive = false) {
        if (array_key_exists($attributeName, $this->_attributes)) {
            return true;
        } else {
            if ($bRecursive == true) {
                if (count($this->_subitems) > 0) {
                    foreach ($this->_subitems as $oSubitem) {
                        $bFound = $oSubitem->hasAttribute($attributeName, true);
                        if ($bFound == true) {
                            return true;
                        }
                    }
                }

                return false;
            } else {
                return false;
            }
        }
    }

    /**
     *
     * @param mixed expand ID of item to expand or array of item ID's to expand
     */
    public function setExpanded($id) {
        if (is_array($id)) {
            if (in_array($this->_id, $id, true)) {
                $this->_collapsed = false;
            }

            foreach (array_keys($this->_subitems) as $key) {
                $this->_subitems[$key]->setExpanded($id);
            }
        } else {
            if ($this->_id === $id) {
                $this->_collapsed = false;
                return true;
            } else {
                foreach (array_keys($this->_subitems) as $key) {
                    $this->_subitems[$key]->setExpanded($id);
                }
            }
        }
    }

    /**
     *
     * @param mixed collapse ID to collapse or an array with items to collapse
     */
    public function setCollapsed($id) {
        if (is_array($id)) {
            if (in_array($this->_id, $id, true)) {
                $this->_collapsed = true;
            }

            foreach (array_keys($this->_subitems) as $key) {
                $this->_subitems[$key]->setCollapsed($id);
            }
        } else {
            if ($this->_id === $id) {
                $this->_collapsed = true;
                return true;
            } else {
                foreach (array_keys($this->_subitems) as $key) {
                    $this->_subitems[$key]->setCollapsed($id);
                }
            }
        }
    }

    /**
     *
     * @param int leveloffset Level offset. Ignores all expand operations below
     *        the offset.
     */
    protected function _expandBelowLevel($leveloffset) {
        if ($leveloffset > 0) {
            $leveloffset--;
        } else {
            $this->_collapsed = false;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->expandBelowLevel($leveloffset);
        }
    }

    /**
     *
     * @param int leveloffset Level offset. Ignores all expand operations below
     *        the offset.
     */
    protected function _collapseBelowLevel($leveloffset) {
        if ($leveloffset > 0) {
            $leveloffset--;
        } else {
            $this->_collapsed = true;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->collapseBelowLevel($leveloffset);
        }
    }

    /**
     *
     * @param int leveloffset Level offset. Ignores all expand operations below
     *        the offset.
     */
    protected function _expandBelowID($id, $found = false) {
        if ($found === true) {
            $this->_collapsed = false;
        }

        if ($this->_id == $id) {
            $found = true;
            $this->_collapsed = false;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->expandBelowID($id, $found);
        }
    }

    /**
     *
     * @param int leveloffset Level offset. Ignores all expand operations below
     *        the offset.
     */
    protected function _collapseBelowID($id, $found = false) {
        if ($found === true) {
            $this->_collapsed = true;
        }

        if ($this->_id == $id) {
            $found = true;
            $this->_collapsed = true;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->collapseBelowID($id, $found);
        }
    }

    /**
     * getCollapsedList
     * Returns all items (as ID array) which are collapsed.
     *
     * @param array $list Contains the list with all collapsed items
     */
    public function getCollapsedList(&$list) {
        if (!is_array($list)) {
            $list = array();
        }

        if ($this->_collapsed == true) {
            $list[] = $this->_id;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->getCollapsedList($list);
        }
    }

    /**
     * getExpandedList
     * Returns all items (as ID array) which are expanded.
     *
     * @param array $list Contains the list with all expanded items
     */
    public function getExpandedList(&$list) {
        if (!is_array($list)) {
            $list = array();
        }

        if ($this->_collapsed == false && !in_array($this->_id, $list)) {
            $list[] = $this->_id;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->getExpandedList($list);
        }
    }

    /**
     * sets a payload object for later reference
     *
     * @param object payload The object to payload
     */
    public function setPayloadObject($payload) {
        $this->payload = $payload;
    }

    /**
     * unsets a payload object
     *
     * @return object
     */
    public function unsetPayloadObject() {
        // @todo implement!
    }

    /**
     * traverse
     * traverses the tree starting from this item, and returning
     * all objects as $objects in a nested array.
     *
     * @param object $objects all found objects
     * @param int $level Level to start on
     */
    public function traverse(&$objects, $level = 0) {
        $objects[count($objects)] = &$this;
        $this->_level = $level;

        if ($this->_collapsed == false) {
            foreach (array_keys($this->_subitems) as $key) {
                $this->_subitems[$key]->traverse($objects, $level + 1);
            }
        }
    }

    /**
     * flatTraverse
     * traverses the tree starting from this item, and returning
     * all objects as $objects in a flat array.
     *
     * @param object $objects all found objects
     * @param int $level Level to start on
     */
    public function flatTraverse($level = 0) {
        $objects[] = &$this;
        $this->_level = $level;

        if ($this->_collapsed == false) {
            foreach (array_keys($this->_subitems) as $key) {
                $objects = array_merge($objects, $this->_subitems[$key]->flatTraverse($level + 1));
            }
        }

        return $objects;
    }

    /**
     * setName
     * sets the Name for this item.
     *
     * @param string $name New name for this item
     * @return none
     */
    public function setName($name) {
        $this->_name = $name;
    }

}
