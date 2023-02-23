<?php
/**
 * This file contains the tree item storage class.
 *
 * @package Core
 * @subpackage Backend
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
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
     * @var TreeItem[]|object[]
     */
    protected $_subitems;

    /**
     * Determinate if this tree item is collapsed
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
     * Parent id of this item
     *
     * @var string|int
     */
    protected $_parentId;

    /**
     * Constructor to create an instance of this class.
     *
     * Creates a new, independent tree item.
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
        $this->_subitems = [];
        $this->setCollapsedIcon('images/but_plus.gif');
        $this->setExpandedIcon('images/but_minus.gif');
        $this->setLastnodeIcon('images/but_lastnode.gif');
        $this->_parentId = -1;
    }

    /**
     * Magic getter function for outdated variable names.
     *
     * @param string $name
     *         Name of the variable
     * @return int|string|void
     * @throws cInvalidArgumentException
     */
    public function __get($name) {
        if ($name === 'parent' || $name == '_parent') {
            cDeprecated("The property `' . $name . '` is deprecated since CONTENIDO 4.10.2, use `TreeItem::getParentId()` instead.");
            return $this->_parentId;
        }
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
     * Get method for _custom variable
     *
     * @param string $key
     * @return string mixed
     */
    public function getCustom($key) {
        return $this->_custom[$key];
    }

    /**
     * Setter for the parent id.
     *
     * @param string|int $parentId
     */
    public function setParentId($parentId) {
        $this->_parentId = $parentId;
    }

    /**
     * Getter for the parent id.
     *
     * @return string|int
     */
    public function getParentId() {
        return $this->_parentId;
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
     * @return TreeItem[]|object[]
     */
    public function getSubItems() {
        return $this->_subitems;
    }

    /**
     * Set method for custom array
     *
     * @param string $key
     * @param string|int $content
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
     * Adds a new sub-item to this item.
     *
     * @param TreeItem|object $item
     *         the item to add
     */
    public function addItem(&$item) {
        $this->_subitems[] = $item;
        $item->setParentId($this->_id);
    }

    /**
     * Internal alias for {@see TreeItem::addItemToID()}
     */
    protected function _addItemToID($item, $id) {
        if ($this->_id == $id) {
            $this->_subitems[] = $item;
            $item->setParentId($this->_id);
        } else {
            foreach (array_keys($this->_subitems) as $key) {
                $this->_subitems[$key]->addItemToID($item, $id);
            }
        }
    }

    /**
     * Adds a new sub-item to a specific item with an ID.
     * Traverses all sub-items to find the correct item.
     *
     * @param TreeItem|object $item
     *         the item to add
     * @param string $id
     *         the ID to add the item to
     */
    public function addItemToID($item, $id) {
        $this->_addItemToID($item, $id);
    }

    /**
     * Retrieves a specific item by its ID.
     * Note that this
     * function traverses all sub-items to find the correct item.
     *
     * @param string $id
     *         the ID to find
     * @return TreeItem|object|false
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

        // Return variable as reference
        $result = false;
        return $result;
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
     * Checks if a specific custom attribute is set.
     *
     * @param string $item
     *         the attribute name to find
     * @return bool
     */
    public function isCustomAttributeSet($item) {
        if (array_key_exists($item, $this->_custom)) {
            return true;
        } else {
            foreach (array_keys($this->_subitems) as $key) {
                if ($this->_subitems[$key]->isCustomAttributeSet($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Marks an item as expanded.
     * Traverses all sub-items to find the ID. Note that only the item with $id
     * is expanded, but not its children.
     *
     * @param string|string[] $id
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
        return false;
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
     * Marks an item as collapsed.
     * Traverses all sub-items
     * to find the ID. Note that only the item with $id is
     * collapsed, but not its children.
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
     * @param TreeItem[]|object[] $objects
     *         all found objects
     * @param int $level [optional]
     *         Level to start on
     */
    public function traverse(array &$objects, $level = 0) {
        $objects[] = $this;
        $this->_level = $level;

        if (!$this->_collapsed) {
            foreach (array_keys($this->_subitems) as $key) {
                $this->_subitems[$key]->traverse($objects, $level + 1);
            }
        }
    }

    /**
     * Starts iterating at root node and flattens the tree into an array
     *
     * @param TreeItem|object $item
     * @param TreeItem[]|object[] $flat_tree
     */
    public function getFlatTree($item, array &$flat_tree) {
        foreach ($item->getSubItems() as $curItem) {
            $curItem->custom['vertline'] = [];
            $flat_tree[] = $curItem;
            $this->getFlatTree($curItem, $flat_tree);
        }
    }

    /**
     *
     * @param int|bool $item_id
     * @return bool
     */
    public function hasCollapsedNode($item_id) {
        $parentNodeList = [];
        $this->getTreeParentNodes($parentNodeList, $item_id);
        $collapsedList = [];
        $this->getRealCollapsedList($collapsedList);

        return sizeof(array_intersect($parentNodeList, $collapsedList)) > 0;
    }

    /**
     * Returns a list of the id of all parent nodes of the given node
     *
     * @param TreeItem[]|object[] $parentNodes
     * @param int|bool $id
     */
    public function getTreeParentNodes(array &$parentNodes, $id) {
        $curItem = $this->getItemByID($id);
        $parentId = $curItem->getParentId();

        if ($parentId && $parentId != -1) {
            $parentNodes[] = $parentId;
            $this->getTreeParentNodes($parentNodes, $parentId);
        }
    }

    /**
     * Returns a list of the id of all parent nodes of the given node
     * Not using the nodes of hierarchical tree, but flat tree !!
     *
     * @param TreeItem[]|object[] $parentNodes
     * @param int|bool $stop_id
     */
    protected function _getParentNodes(array &$parentNodes, $stop_id) {
        /** @var TreeItem[]|object[] $flat_tree */
        $flat_tree = [];
        $this->getFlatTree($this, $flat_tree);

        foreach ($flat_tree as $key => $value) {
            if ($value->getId() != $stop_id) {
                $parentNodes[] = $value->getParentId();
            } else {
                break;
            }
        }
    }

    /**
     * getCollapsedList thinks if a node has no sub-nodes it is collapsed
     * I don't think so
     * @TODO The function does not make any changes, nor does it return anything, that should be checked and adjusted.
     *
     * @param TreeItem[]|object[] $list
     */
    public function getRealCollapsedList(array &$list) {
        $this->getCollapsedList($list);
        $cleared_list = [];

        // remove all nodes that have no sub-nodes
        foreach ($list as $key) {
            $item = $this->getItemByID($key);
            if (sizeof($item->getSubItems()) > 0) {
                $cleared_list[] = $key;
            }
        }
    }

    /**
     * Returns all items (as ID array) which are collapsed.
     *
     * @param TreeItem[]|object[] $list
     *         Contains the list with all collapsed items
     */
    public function getCollapsedList(array &$list) {
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
     * @param TreeItem[]|object[] $list
     *         Contains the list with all expanded items
     */
    public function getExpandedList(array &$list) {
        if (!$this->_collapsed && !in_array($this->_id, $list)) {
            $list[] = $this->_id;
        }

        foreach (array_keys($this->_subitems) as $key) {
            $this->_subitems[$key]->getExpandedList($list);
        }
    }
}
