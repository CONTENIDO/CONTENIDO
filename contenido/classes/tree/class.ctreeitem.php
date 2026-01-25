<?php

/**
 * This file contains the tree item class.
 *
 * @package    Core
 * @subpackage GUI
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Tree item class.
 *
 * @package    Core
 * @subpackage GUI
 */
#[AllowDynamicProperties]
class cTreeItem
{

    /**
     * @var cTreeItem[]|object[] Sub Items of this tree item
     */
    protected $_subitems = [];

    /**
     * @var bool Determinate if this tree item is collapsed
     */
    protected $_collapsed;

    /**
     * @var string|int ID of this tree item
     */
    protected $_id;

    /**
     * @var string Name of this tree item
     */
    protected $_name;

    /**
     * @var int Level of this tree item
     */
    protected $_level;

    /**
     * @var array Contains custom entries
     */
    protected $_attributes = [];

    /**
     * @var array|false Parent of this tree item
     */
    protected $_parent = false;

    /**
     * @var array|false Next sibling of this tree item
     */
    protected $_next = false;

    /**
     * @var array|false Previous sibling of this tree item
     */
    protected $_previous = false;

    /**
     * @var ?object
     */
    protected $payload;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string|int $id [optional]
     * @param string $name [optional]
     * @param bool $collapsed [optional]
     */
    public function __construct($id = "", $name = "", bool $collapsed = false)
    {
        $this->_id = $id;
        $this->_name = $name;
        $this->_collapsed = $collapsed;
    }

    /**
     * Id getter.
     *
     * @return string|int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Name getter.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets the name for this item.
     *
     * @param string $name New name for this item
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Collapsed state getter.
     *
     * @return bool
     */
    public function getCollapsed(): bool
    {
        return $this->_collapsed;
    }

    /**
     * Imports a table from an array of arrays.
     *
     * The entries "collapsed" and "attributes" are optional!
     *
     * @param array|mixed $flat_array
     *      <pre>
     *      [
     *          [
     *              "id" => "Item ID",
     *              "name" => "Item name",
     *              "level" => 1,
     *              "collapsed" => true|false,
     *              "attributes" => [
     *                  "attr_name" => "attr_value"
     *              ]
     *          ]
     *       ]
     *      </pre>
     */
    public function importTable($flat_array): bool
    {
        $lastObjId[0] = $this->_id;
        $currentLevel = 1;

        if (!is_array($flat_array)) {
            return false;
        }

        $mItem = [];
        foreach ($flat_array as $item) {
            $mItem[$item['id']] = new cTreeItem($item['id'], $item['name']);

            if ($item['level'] > $currentLevel) {
                $currentLevel++;
            }

            if ($item['level'] < $currentLevel) {
                $currentLevel = $item['level'];
            }

            if (is_array($item['attributes'])) {
                $mItem[$item['id']]->setAttributes($item['attributes']);
            }

            if (array_key_exists("collapsed", $item)) {
                $mItem[$item['id']]->setCollapsed($item['collapsed']);
            }

            /* Set payload object */
            if (array_key_exists("payload", $item)) {
                $mItem[$item['id']]->setPayloadObject($item['payload']);
            }

            if (is_object($mItem[$lastObjId[$currentLevel - 1]])) {
                $mItem[$lastObjId[$currentLevel - 1]]->addItem($mItem[$item['id']]);
            } else {
                $this->addItemToID($lastObjId[$currentLevel - 1], $mItem[$item['id']]);
            }

            $lastObjId[$currentLevel] = $item['id'];
        }

        return true;
    }

    public function importStructuredArray(array $array)
    {
        $i = [];

        $lastid = 1;
        $level = 1;

        $this->_flattenArray($array, $i, $lastid, $level);

        $this->importTable($i);
    }

    /**
     *
     * @param array $sourceArray
     * @param array $destArray
     * @param int $lastId
     * @param int $level
     */
    protected function _flattenArray($sourceArray, &$destArray, &$lastId, &$level): bool
    {
        if (!$lastId) {
            $lastId = 1;
        }

        if (!$level) {
            $level = 1;
        }

        if (!is_array($sourceArray)) {
            return false;
        }

        foreach ($sourceArray as $id => $item) {
            $lastId++;
            $destArray[$lastId]['id'] = $item['class'] . "." . $id;

            // Name should be fetched via the meta object
            $meta = $item['object']->getMetaObject();

            if (is_object($meta)) {
                $destArray[$lastId]['name'] = $meta->getName();
            }

            $destArray[$lastId]['level'] = $level;
            $destArray[$lastId]['payload'] = $item['object'];

            if (count($item['items']) > 0) {
                $level++;
                $this->_flattenArray($item['items'], $destArray, $lastId, $level);
                $level--;
            }
        }

        return true;
    }

    /**
     * Adds an item as a subitem to the current item.
     *
     * @param cTreeItem|object $item Item object to add
     */
    public function addItem(&$item)
    {
        // Update last item
        if (($lastitem = end($this->_subitems)) !== false) {
            $this->_subitems[key($this->_subitems)]->_next = $item->_id;
        }

        $this->_subitems[count($this->_subitems)] = &$item;
        $item->_parent = $this->_id;
        $item->_previous = $lastitem->_id;
    }

    /**
     * Adds an item to a specific ID.
     *
     * @param string|int $id ID to add the item to
     * @param cTreeItem|object $item Item to add
     */
    public function addItemToID($id, &$item): bool
    {
        if ($this->_id == $id) {
            // Update last item
            /** @var cTreeItem|object $lastitem */
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
                if ($result) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Moves an item to another object.
     *
     * @param cTreeItem|object $targetItem Item to move the subitem to
     * @param mixed $itemToMove cTreeItem-Object or id of object to move
     */
    public function moveItem($targetItem, $itemToMove)
    {
    }

    /**
     * Deletes a subitem.
     *
     * @param string|int $id Item object or ID to delete
     * @return cTreeItem|object|null Deleted object
     */
    public function deleteItem($id)
    {
        foreach (array_keys($this->_subitems) as $key) {
            if ($this->_subitems[$key]->_id == $id) {
                // Fetch next item, reset to current item
                $nextItem = next($this->_subitems);
                $nkey = key($this->_subitems);
                prev($this->_subitems);

                $prevItem = prev($this->_subitems);
                $pkey = key($this->_subitems);
                next($this->_subitems);

                if ($nextItem !== false) {
                    if ($prevItem !== false) {
                        $this->_subitems[$nkey]->_previous = $this->_subitems[$pkey]->_id;
                    }
                }

                if ($prevItem !== false) {
                    if ($nextItem !== false) {
                        $this->_subitems[$pkey]->_next = $this->_subitems[$nkey]->_id;
                    }
                }

                $itemCopy = $this->_subitems[$key];
                unset($this->_subitems[$key]);

                return $itemCopy;
            } else {
                $this->_subitems[$key]->deleteItem($id);
            }
        }

        return null;
    }

    /**
     * Retrieves a specific item by its ID.
     * Note that this function traverses all subitems to find the correct item.
     *
     * @param string|int $id ID to retrieve
     * @return cTreeItem|object|null
     */
    public function getItemByID($id)
    {
        if ($this->_id == $id) {
            return $this;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $retObj = $this->_subitems[$key]->getItemByID($id);
            if ($retObj && $retObj->_id == $id) {
                return $retObj;
            }
        }

        return null;
    }

    /**
     * Sets a custom attribute for this TreeItem.
     *
     * @param string $attributeName
     * @param array $attributeValue The value(s) of the attribute
     */
    public function setAttribute($attributeName, $attributeValue)
    {
        $this->_attributes[$attributeName] = $attributeValue;
    }

    /**
     * Sets a bunch of attributes.
     */
    public function setAttributes(array $aAttributeArray)
    {
        $this->_attributes = array_merge($aAttributeArray, $this->_attributes);
    }

    /**
     * Returns an attribute.
     *
     * @param string $attributeName
     * @return mixed
     */
    public function getAttribute($attributeName)
    {
        return $this->_attributes[$attributeName] ?? false;
    }

    /**
     * Deletes an attribute.
     *
     * @param string $attributeName
     */
    public function deleteAttribute($attributeName): bool
    {
        if (array_key_exists($attributeName, $this->_attributes)) {
            unset($this->_attributes[$attributeName]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $attributeName
     */
    public function hasAttribute($attributeName, bool $recursive = false): bool
    {
        if (array_key_exists($attributeName, $this->_attributes)) {
            return true;
        }

        if ($recursive) {
            if (count($this->_subitems) > 0) {
                foreach ($this->_subitems as $oSubitem) {
                    if ($oSubitem->hasAttribute($attributeName, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param string|int|array $id Expand ID of item to expand or array of item ID's to expand
     */
    public function setExpanded($id): bool
    {
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

        return false;
    }

    /**
     *
     * @param string|int|array $id Collapse ID to collapse or an array with items to collapse
     */
    public function setCollapsed($id): bool
    {
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

        return false;
    }

    /**
     * @param int $levelOffset Level offset. Ignores all expand operations below the offset.
     */
    protected function _expandBelowLevel(int $levelOffset)
    {
        if ($levelOffset > 0) {
            $levelOffset--;
        } else {
            $this->_collapsed = false;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->expandBelowLevel($levelOffset);
        }
    }

    /**
     * @param int $levelOffset Level offset. Ignores all expand operations below the offset.
     */
    protected function _collapseBelowLevel(int $levelOffset)
    {
        if ($levelOffset > 0) {
            $levelOffset--;
        } else {
            $this->_collapsed = true;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->collapseBelowLevel($levelOffset);
        }
    }

    /**
     * @param string|int $id
     */
    protected function _expandBelowID($id, bool $found = false)
    {
        if ($found) {
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
     * @param string|int $id
     */
    protected function _collapseBelowID($id, bool $found = false)
    {
        if ($found) {
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
     * Returns all items (as ID array) which are collapsed.
     *
     * @param array $list Contains the list with ids of all collapsed items
     */
    public function getCollapsedList(array &$list)
    {
        if ($this->_collapsed) {
            $list[] = $this->_id;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->getCollapsedList($list);
        }
    }

    /**
     * Returns all items (as ID array) which are expanded.
     *
     * @param array $list Contains the list with ids of all expanded items
     */
    public function getExpandedList(array &$list)
    {
        if (!$this->_collapsed && !in_array($this->_id, $list)) {
            $list[] = $this->_id;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->getExpandedList($list);
        }
    }

    /**
     * Sets a payload object for later reference.
     *
     * @param object|mixed $payload The object to payload
     */
    public function setPayloadObject($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Unsets a payload object.
     */
    public function unsetPayloadObject()
    {
        unset($this->payload);
    }

    /**
     * Traverses the tree starting from this item, and returning all
     * objects as $objects in a nested array.
     *
     * @param cTreeItem[]|object[] $objects All found objects
     * @param int $level Level to start on
     */
    public function traverse(&$objects, int $level = 0)
    {
        $objects[count($objects)] = &$this;
        $this->_level = $level;

        if (!$this->_collapsed) {
            foreach (array_keys($this->_subitems) as $key) {
                $this->_subitems[$key]->traverse($objects, $level + 1);
            }
        }
    }

    /**
     * Traverses the tree starting from this item, and returning
     * all objects as $objects in a flat array.
     *
     * @param int $level Level to start on
     * @return cTreeItem[]|object[]
     */
    public function flatTraverse(int $level = 0): array
    {
        $objects[] = &$this;
        $this->_level = $level;

        if (!$this->_collapsed) {
            foreach (array_keys($this->_subitems) as $key) {
                $objects = array_merge($objects, $this->_subitems[$key]->flatTraverse($level + 1));
            }
        }

        return $objects;
    }

}
