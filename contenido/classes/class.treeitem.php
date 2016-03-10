<?php
/**
 * This file contains the tree item storage class.
 *
 * @package Core
 * @subpackage Backend
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class TreeItem
 * Class to create tree-based items
 *
 * The treeitem class allows you to logically store
 * tree-based structures.
 *
 * Example:
 *
 * Let's have a tree with 3 nodes. It's important that
 * we always have a "root" key.
 *
 * $root = new TreeItem("root", 1);
 * $item1 = new TreeItem("node1",2);
 * $item2 = new TreeItem("node2",3);
 * $item3 = new TreeItem("node3",4);
 *
 * $root->addItem($item1);
 * $root->addItem($item2);
 * $root->addItem($item3);
 *
 * This represents the tree we described above.
 *
 * If you know the ID of the item you want to add
 * to, there's no need to have a specific item handy,
 * but rather you can use the "addItemToID" function.
 *
 * @package Core
 * @subpackage Backend
 */
class TreeItem {

    /**
     * Sub Items for this tree item
     *
     * @var array
     */
    protected $_subitems;

    /**
     * Determinates if this tree item is collapsed
     *
     * @var bool
     */
    protected $_collapsed;

    /**
     * ID for this item
     *
     * @var string
     */
    protected $_id;

    /**
     * Name for this item
     *
     * @var string
     */
    protected $_name;

    /**
     * Icon for the collapsed item
     *
     * @var string
     */
    protected $_collapsed_icon;

    /**
     * Icon for the expanded item
     *
     * @var string
     */
    protected $_expanded_icon;

    /**
     * Icon for last node in a branch
     *
     * @var string
     */
    protected $_lastnode_icon;

    /**
     * Contains the level of this item
     *
     * @var int
     */
    protected $_level;

    /**
     * Contains custom entries
     *
     * @var array
     */
    protected $_custom;

    /**
     * Contains the parent of this item
     *
     * @var array
     */
    protected $_parent;

    /**
     * Constructor Function
     * Creates a new, independant tree item.
     *
     * @param string $name [optional]
     *         The name of that item
     * @param string $id [optional]
     *         The unique ID of that item
     * @param bool $collapsed [optional]
     *         Is this item collapsed by default
     */
    public function __construct($name = "", $id = "", $collapsed = false) {
        $this->_name = $name;
        $this->_id = $id;
        $this->_collapsed = $collapsed;
        $this->_subitems = array();
        $this->setCollapsedIcon('images/but_plus.gif');
        $this->setExpandedIcon('images/but_minus.gif');
        $this->setLastnodeIcon('images/but_lastnode.gif');
        $this->_parent = -1;
    }

    /**
     * Get method for _collapsed_icon variable
     *
     * @return string
     */
    public function getCollapsedIcon() {
        return $this->_collapsed_icon;
    }

    /**
     * Get method for _costum variable
     *
     * @param string $key
     * @return string mixed
     */
    public function getCustom($key) {
        return $this->_custom[$key];
    }

    /**
     * Get method for _expanded_icon variable
     *
     * @return string
     */
    public function getExpandedIcon() {
        return $this->_expanded_icon;
    }

    /**
     * Get method for _id variable
     *
     * @return string
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Get method for _name variable
     *
     * @return string
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Get method for _subitems array
     *
     * @return array
     */
    public function getSubItems() {
        return $this->_subitems;
    }

    /**
     * Set method for custom array
     *
     * @param string $key
     * @param string|integer $content
     */
    public function setCustom($key, $content) {
        $this->_custom[$key] = $content;
    }

    /**
     * Set method for _collapsed_icon variable
     *
     * @param string $iconPath
     */
    public function setCollapsedIcon($iconPath) {
        if (cSecurity::isString($iconPath)) {
            $this->_collapsed_icon = $iconPath;
        }
    }

    /**
     * Set method for _expanded_icon variable
     *
     * @param string $iconPath
     */
    public function setExpandedIcon($iconPath) {
        if (cSecurity::isString($iconPath)) {
            $this->_expanded_icon = $iconPath;
        }
    }

    /**
     * Set method for _lastnode_icon variable
     *
     * @param string $iconPath
     */
    public function setLastnodeIcon($iconPath) {
        if (cSecurity::isString($iconPath)) {
            $this->_lastnode_icon = $iconPath;
        }
    }

    /**
     * Set method for name variable
     *
     * @param string $name
     */
    public function setName($name) {
        $this->_name = $name;
    }

    /**
     * Get status of collapsed (_collapsed variable)
     *
     * @return bool
     */
    public function isCollapsed() {
        return $this->_collapsed;
    }

    /**
     * Adds a new subitem to this item.
     *
     * @param object $item
     *         the item to add
     */
    public function addItem(&$item) {
        $this->_subitems[count($this->_subitems)] = &$item;
        $item->parent = $this->_id;
    }

    /**
     * Adds a new subitem to a specific item with an ID.
     * Traverses all subitems to find the correct item.
     *
     * @param object $item
     *         the item to add
     * @param string $id
     *         the ID to add the item to
     */
    protected function _addItemToID($item, $id) {
        if ($this->_id == $id) {
            $this->_subitems[count($this->_subitems)] = &$item;
            $item->parent = $this->_id;
        } else {
            foreach (array_keys($this->_subitems) as $key) {
                $this->_subitems[$key]->_addItemToID($item, $id);
            }
        }
    }

    /**
     * Retrieves a specific item by its ID.
     * Note that this
     * function traverses all subitems to find the correct item.
     *
     * @param string $id
     *         the ID to find
     * @return object
     *         The item, or false if nothing was found
     */
    public function &getItemByID($id) {
        if ($this->_id == $id) {
            return $this;
        } else {
            foreach (array_keys($this->_subitems) as $key) {
                $retObj = &$this->_subitems[$key]->getItemByID($id);
                if ($retObj->id == $id) {
                    return $retObj;
                }
            }
        }

        return false;
    }

    /**
     * Removes an item with a specific ID.
     *
     * @param string $id
     *         the ID to find
     */
    public function removeItem($id) {
        foreach (array_keys($this->_subitems) as $key) {
            if ($this->_subitems[$key]->id == $id) {
                unset($this->_subitems[$key]);
            }
        }
    }

    /**
     * Checks if a specific custom attribute is set
     *
     * @param string $item
     *         the attribute name to find
     * @return bool
     */
    protected function _isCustomAttributeSet($item) {
        if (array_key_exists($item, $this->_custom)) {
            return true;
        } else {
            foreach (array_keys($this->_subitems) as $key) {
                if ($this->_subitems[$key]->_isCustomAttributeSet($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Marks an item as expanded.
     * Traverses all subitems to find the ID. Note that only the item with $id
     * is expanded, but not its childs.
     *
     * @param string $id
     *         the ID to expand, or an array with all id's
     * @return bool
     */
    public function markExpanded($id) {
        if (is_array($id)) {
            if (in_array($this->_id, $id)) {
                $this->_collapsed = false;
            }

            foreach (array_keys($this->_subitems) as $key) {
                $this->_subitems[$key]->markExpanded($id);
            }
        } else {
            if ($this->_id == $id) {
                $this->_collapsed = false;
                return true;
            } else {
                foreach (array_keys($this->_subitems) as $key) {
                    $this->_subitems[$key]->markExpanded($id);
                }
            }
        }
    }

    /**
     * Expands all items, starting from the $start item.
     *
     * @param string $start [optional]
     *         the ID to start expanding from
     */
    public function expandAll($start = -2) {
        if ($start != $this->_id) {
            $this->_collapsed = false;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->expandAll();
        }
    }

    /**
     * Collapses all items, starting from the $start item.
     *
     * @param string $start [optional]
     *         the ID to start collapsing from
     */
    public function collapseAll($start = -2) {
        if ($start != $this->_id) {
            $this->_collapsed = true;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->collapseAll();
        }
    }

    /**
     * Marks an item as collpased.
     * Traverses all subitems
     * to find the ID. Note that only the item with $id is
     * collapsed, but not its childs.
     *
     * @param string $id
     *         the ID to collapse
     */
    public function markCollapsed($id) {
        if ($this->_id == $id) {
            $this->_collapsed = true;
        } else {
            foreach (array_keys($this->_subitems) as $key) {
                $this->_subitems[$key]->markCollapsed($id);
            }
        }
    }

    /**
     * Traverses the tree starting from this item, and returning
     * all objects as $objects.
     *
     * @param object $objects
     *         all found objects
     * @param int $level [optional]
     *         Level to start on
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
     * Starts iterating at root node and flattens the tree into an array
     *
     * @param unknown_type $item
     * @param unknown_type $flat_tree
     */
    public function getFlatTree($item, &$flat_tree) {
        foreach ($item->subitems as $curItem) {
            $curItem->custom['vertline'] = array();
            $flat_tree[] = $curItem;
            $this->getFlatTree($curItem, $flat_tree);
        }
    }

    /**
     *
     * @param unknown_type $item_id
     * @return bool
     */
    public function hasCollapsedNode($item_id) {
        $parentNodeList = array();
        $this->getTreeParentNodes($parentNodeList, $item_id);
        $collapsedList = array();
        $this->getRealCollapsedList($collapsedList);

        if (sizeof(array_intersect($parentNodeList, $collapsedList)) > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Returns a list of the id of all parent nodes of the given node
     *
     * @param unknown_type $parentNodes
     * @param unknown_type $id
     */
    public function getTreeParentNodes(&$parentNodes, $id) {
        $curItem = $this->_getItemByID($id);
        $parentId = $curItem->parent;

        if ($parentId && $parentId != -1) {
            $parentNodes[] = $parentId;
            $this->getTreeParentNodes($parentNodes, $parentId);
        }
    }

    /**
     * Returns a list of the id of all parent nodes of the given node
     * Not using the nodes of hierarchical tree, but flat tree !!
     *
     * @param unknown_type $parentNodes
     * @param unknown_type $stop_id
     */
    protected function _getParentNodes(&$parentNodes, $stop_id) {
        $flat_tree = array();
        $this->getFlatTree($this, $flat_tree);

        foreach ($flat_tree as $key => $value) {
            if ($value->id != $stop_id) {
                $parentNodes[] = $value->id;
            } else {
                break;
            }
        }
    }

    /**
     * getCollapsedList thinks if a node has no subnodes it is collapsed
     * I don't think so
     *
     * @param unknown_type $list
     */
    public function getRealCollapsedList(&$list) {
        $this->getCollapsedList($list);
        $cleared_list = array();

        // remove all nodes that have no subnodes
        foreach ($list as $key) {
            $item = $this->_getItemByID($key);
            if (sizeof($item->subitems) > 0) {
                $cleared_list[] = $key;
            }
        }
    }

    /**
     * Returns all items (as ID array) which are collapsed.
     *
     * @param array $list
     *         Contains the list with all collapsed items
     */
    public function getCollapsedList(&$list) {
        if ($this->_collapsed == true) {
            $list[] = $this->_id;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->getCollapsedList($list);
        }
    }

    /**
     * Returns all items (as ID array) which are expanded.
     *
     * @param array $list
     *         Contains the list with all expanded items
     */
    public function getExpandedList(&$list) {
        if ($this->_collapsed == false && !in_array($this->_id, $list)) {
            $list[] = $this->_id;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->getExpandedList($list);
        }
    }
}
