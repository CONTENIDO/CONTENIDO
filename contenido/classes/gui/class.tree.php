<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Visual representation of a cTree
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0
 * @author     mischa.holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2012-07-12
 *   $Id: class.tree.php 2629 2012-07-12 12:14:35Z mischa.holz $
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

define("TREEVIEW_GRIDLINE_SOLID", "solid");
define("TREEVIEW_GRIDLINE_DASHED", "dashed");
define("TREEVIEW_GRIDLINE_DOTTED", "dotted");
define("TREEVIEW_GRIDLINE_NONE", "none");

define("TREEVIEW_BACKGROUND_NONE", "none");
define("TREEVIEW_BACKGROUND_SHADED", "shaded");

define("TREEVIEW_MOUSEOVER_NONE", "none");
define("TREEVIEW_MOUSEOVER_MARK", "mark");

/**
 * class cGuiTree
 * cGuiTree is a visual representation of a cTree. It supports folding,
 * optional gridline marks and item icons.
 */
class cGuiTree extends cTree {

    /**
     *
     * @access private
     */
    private $_globalActions;

    /**
     *
     * @access private
     */
    private $_setItemActions;

    /**
     *
     * @access private
     */
    private $_unsetItemActions;

    /**
     *
     * @access private
     */
    private $_setAttributeActions;

    /**
     *
     * @access private
     */
    private $_unsetAttributeActions;

    /**
     *
     * @access private
     */
    private $_baseLink;

    public function __construct($uuid, $treename = false) {
        global $cfg, $auth;

        cTree::cTree();

        $this->_uuid = $uuid;
        //$this->setGridlineMode(TREEVIEW_GRIDLINE_DOTTED);

        if ($treename != false) {
            $this->setTreeName($treename);
        }

        $this->_user = new cApiUser($auth->auth["uid"]);
    }

    public function processParameters() {
        if (($items = $this->_user->getUserProperty("expandstate", $this->_uuid)) !== false) {
            $list = unserialize($items);

            foreach ($list as $litem) {
                $this->setCollapsed($litem);
            }
        }

        if (!empty($this->_name)) {
            $treename = $this->_name . "_";
        }

        if (array_key_exists($treename . "collapse", $_GET)) {
            $this->setCollapsed($_GET[$treename . "collapse"]);
        }

        if (array_key_exists($treename . "expand", $_GET)) {
            $this->setExpanded($_GET[$treename . "expand"]);
        }

        $xlist = array(); // Define variable before using it by reference...
        $this->getCollapsedList($xlist);
        $slist = serialize($xlist);

        $this->_user->setUserProperty("expandstate", $this->_uuid, $slist);
    }

    /**
     * applies an action to all items in the tree.
     *
     * @deprecated [2012-07-12] Thisfunction doesn't do anything
     * @param cApiClickableAction action action object
     * @return void
     * @access public
     */
    public function applyGlobalAction($action) {
        cDeprecated("This function doesn't do anything.");
    }

    /**
     * removes the action from all treeitems.
     *
     * @deprecated [2012-07-12] Thisfunction doesn't do anything
     * @param cApiClickableAction action Removes the action from the global context.
     * @return void
     * @access public
     */
    public function removeGlobalAction($action) {
        cDeprecated("This function doesn't do anything.");
    }

    /**
     * flushes all actions
     *
     * @deprecated [2012-07-12] Thisfunction doesn't do anything
     * @return void
     * @access public
     */
    public function flushGlobalActions() {
        cDeprecated("This function doesn't do anything.");
    }

    /**
     * sets an action to a specific item.
     *
     * @deprecated [2012-07-12] Thisfunction doesn't do anything
     * @param mixed item cTreeItem-Object or an id of a TreeItem-Object
     * @param cApiClickableAction action
     * @return void
     * @access public
     */
    public function applyItemAction($item, $action) {
        cDeprecated("This function doesn't do anything.");
    }

    /**
     * unsets an action from a specific item. Note that you can unset global actions
     * using this method!
     *
     * @deprecated [2012-07-12] Thisfunction doesn't do anything
     * @param mixed item cTreeItem-Object or an id of a TreeItem-Object
     * @param cApiClickableAction action Action to unset
     * @return void
     * @access public
     */
    public function removeItemAction($item, $action) {
        cDeprecated("This function doesn't do anything.");
    }

    /**
     * flushes all actions for a specific item
     *
     * @deprecated [2012-07-12] Thisfunction doesn't do anything
     * @param mixed item cTreeItem-Object or an id of a TreeItem-Object
     * @return void
     * @access public
     */
    public function flushItemActions($item) {
        cDeprecated("This function doesn't do anything.");
    }

    /**
     * Applies an action to all items with a certain attribute set.
     *
     * @deprecated [2012-07-12] Thisfunction doesn't do anything
     * @param array attributes Values which need to match. The array key is the attribute name. Multiple array
     *        entries are connected with "AND".
     * @param cApiClickableAction action Action to apply
     * @return void
     * @access public
     */
    public function applyActionByItemAttribute($attributes, $action) {
        cDeprecated("This function doesn't do anything.");
    }

    /**
     * Removes an action from all items with a certain attribute set.
     *
     * @deprecated [2012-07-12] Thisfunction doesn't do anything
     * @param array attributes Values which need to match. The array key is the attribute name. Multiple array
     *        entries are connected with "AND".
     * @param cApiClickableAction action Action to remove
     * @return void
     * @access public
     */
    public function removeActionByItemAttribute($attributes, $action) {
        cDeprecated("This function doesn't do anything.");
    }

    /**
     * Removes all actions for items with specific attributes
     *
     * @deprecated [2012-07-12] Thisfunction doesn't do anything
     * @param array attributes Values which need to match. The array key is the attribute name. Multiple array
     *        entries are connected with "AND".
     * @return void
     * @access public
     */
    public function flushActionByItemAttribute($attributes) {
        cDeprecated("This function doesn't do anything.");
    }

    /**
     * @param int mode Sets the gridline mode to one of the following values:
     * TREEVIEW_GRIDLINE_SOLID
     * TREEVIEW_GRIDLINE_DASHED
     * TREEVIEW_GRIDLINE_DOTTED
     * TREEVIEW_GRIDLINE_NONE
     *
     * @return void
     * @access public
     */
    public function setGridlineMode($mode) {
        $this->_gridlineMode = $mode;
    }

    public function setBackgroundMode($mode) {
        $this->_backgroundMode = $mode;
    }

    public function setMouseoverMode($mode) {
        $this->_mouseoverMode = $mode;
    }

    public function setBackgroundColors($colors) {
        $this->_backgroundColors = $colors;
    }

    /**
     * @return string
     * @access public
     */
    public function render($with_root = true) {
        /** @var cTreeItem[] $objects */
        $objects = $this->flatTraverse(0);

        if ($with_root == false) {
            unset($objects[0]);
        }

        $img = new cHTMLImage;
        $r_table = new cHTMLTable;
        $r_row = new cHTMLTableRow;
        $r_leftcell = new cHTMLTableData;
        $r_rightcell = new cHTMLTableData;
        $r_actioncell = new cHTMLTableData;

        $img_spacer = new cHTMLImage;
        $img_spacer->updateAttributes(array('width' => '16', 'height' => '20'));
        $img_spacer->setAlt("");
        $img_spacer->setSrc("images/spacer.gif");
        $img_spacer->advanceID();

        $r_table->setCellPadding(0);
        $r_table->setCellSpacing(0);
        $r_table->setWidth("100%");
        $r_rightcell->appendStyleDefinition("padding-left", "3px");
        $r_rightcell->setVerticalAlignment("middle");
        $r_leftcell->setVerticalAlignment("middle");
        $r_leftcell->updateAttributes(array("nowrap" => "nowrap"));
        $r_rightcell->updateAttributes(array("nowrap" => "nowrap"));
        $r_actioncell->updateAttributes(array("nowrap" => "nowrap"));
        $r_leftcell->setWidth("1%");
        $r_rightcell->setWidth("100%");
        $r_actioncell->setAlignment("right");
        $r_actioncell->setWidth("1%");

        if (!is_object($this->_baseLink)) {
            $this->_baseLink = new cHTMLLink();
        }

        $lastitem = array();
        foreach ($objects as $key => $object) {
            $img->setAlt("");
            $r_table->advanceID();
            $r_rightcell->advanceID();
            $r_leftcell->advanceID();
            $r_row->advanceID();
            $r_actioncell->advanceID();

            for ($level = 1; $level < $object->_level + 1; $level++) {
                if ($object->_level == $level) {
                    if ($object->_next === false) {
                        if (count($object->_subitems) > 0) {
                            $link = $this->_setExpandCollapseLink($this->_baseLink, $object);
                            $link->advanceID();
                            $img->setSrc($this->_getExpandCollapseIcon($object));
                            $img->advanceID();
                            $link->setContent($img);
                            $out .= $link->render();
                        } else {
                            if ($level == 1 && $with_root == false) {
                                $out .= $img_spacer->render();
                            } else {
                                $img->setSrc($this->_buildImagePath("grid_linedownrightend.gif"));
                                $img->advanceID();
                                $out .= $img->render();
                            }
                        }
                        $lastitem[$level] = true;
                    } else {
                        if (count($object->_subitems) > 0) {
                            $link = $this->_setExpandCollapseLink($this->_baseLink, $object);
                            $link->advanceID();
                            $img->setSrc($this->_getExpandCollapseIcon($object));
                            $img->advanceID();
                            $link->setContent($img);
                            $out .= $link->render();
                        } else {
                            if ($level == 1 && $with_root == false) {
                                $out .= $img_spacer->render();
                            } else {
                                $img->setSrc($this->_buildImagePath("grid_linedownright.gif"));
                                $out .= $img->render();
                            }
                        }

                        $lastitem[$level] = false;
                    }
                } else {
                    if ($lastitem[$level] == true) {
                        $out .= $img_spacer->render();
                    } else {
                        if ($level == 1 && $with_root == false) {
                            $out .= $img_spacer->render();
                        } else {
                            $img->setSrc($this->_buildImagePath("/grid_linedown.gif"));
                            $img->advanceID();
                            $out .= $img->render();
                        }
                    }
                }
            }

            /* Fetch Render icon from the meta object */
            if (is_object($object->payload)) {
                /* Fetch payload object */
                $meta = $object->payload->getMetaObject();

                if (is_object($meta)) {
                    $icon = $meta->getIcon();
                    $actions = $meta->getActions();

                    $r_actioncell->setContent($actions);

                    $img->setAlt($meta->getDescription());
                    $img->advanceID();

                    /* Check if we've got an edit link */
                    if (count($meta->_editAction) > 0) {
                        $meta->defineActions();

                        $edit = $meta->getAction($meta->_editAction);

                        $edit->setIcon($icon);

                        $renderedIcon = $edit->render();

                        $edit->_link->setContent($object->getName());
                        $edit->_link->advanceID();
                        $renderedName = $edit->_link->render();
                    } else {
                        $img->setSrc($icon);
                        $renderedIcon = $img->render();
                        $renderedName = $object->getName();
                    }
                }
            } else {
                if (isset($object->_attributes["icon"])) {
                    $img->setSrc($object->_attributes["icon"]);
                    $renderedIcon = $img->render();
                    $renderedName = $object->getName();
                } else {
                    /* Fetch tree icon */
                    if ($object->getId() == 0) {
                        $icon = $object->getTreeIcon();
                        $img->setSrc($icon);
                        $renderedIcon = $img->render();
                        $renderedName = $object->getName();
                    } else {
                        $icon = $object->getTreeIcon();
                        $img->setSrc($icon);
                        $renderedIcon = $img->render();
                        $renderedName = $object->getName();
                    }
                }
            }

            $img->setSrc($icon);
            $r_leftcell->setContent($out . $renderedIcon);
            $r_rightcell->setContent($renderedName);

            $r_row->setContent(array($r_leftcell, $r_rightcell, $r_actioncell));

            $r_table->setContent($r_row);

            $result .= $r_table->render();

            unset($out);
        }

        return ('<table cellspacing="0" cellpadding="0" width="100%" border="0"><tr><td>' . $result . '</td></tr></table>');
    }

    public function _getExpandCollapseIcon($object) {
        if ($object->getCollapsed() == true) {
            return ($this->_buildImagePath("grid_expand.gif"));
        } else {
            return ($this->_buildImagePath("grid_collapse.gif"));
        }
    }

    /**
     * Sets collapsed state
     * @param   cHTMLLink  $link
     * @param   cTreeItem  $object
     * @return  cHTMLLink
     */
    public function _setExpandCollapseLink($link, $object) {
        if (!empty($this->_name)) {
            $treename = $this->_name . "_";
        }

        $link->unsetCustom($treename . "expand");
        $link->unsetCustom($treename . "collapse");

        if ($object->getCollapsed() == true) {
            $link->setCustom($treename . "expand", $object->getId());
        } else {
            $link->setCustom($treename . "collapse", $object->getId);
        }

        return ($link);
    }

    public function _buildImagePath($image) {
        return ("./images/" . $this->_gridlineMode . "/" . $image);
    }

    public function setBaseLink($link) {
        $this->_baseLink = $link;
    }

}

/**
 * Old classname for downwards compatibility
 * @deprecated [2012-07-12] This class was renamed to cGuiTree
 */
class cWidgetTreeView extends cGuiTree {

    public function __construct($uuid, $treename = false) {
        cDeprecated("This class was renamed to cGuiTree");

        parent::__construct($uuid, $treename);
    }

}

?>