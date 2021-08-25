<?php

/**
 * This file contains the tree view class for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

plugin_include('repository', 'custom/FrontendNavigation.php');

/**
 * Tree view class for content allocation
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 */
class pApiContentAllocationTreeView extends pApiTree {

    /**
     * @var object cTemplate
     */
    protected $_tpl = null;

    /**
     * @var string
     */
    protected $_template = '';

    /**
     * pApiContentAllocationTreeView constructor
     *
     * @param string $uuid
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($uuid) {
        $cfg = cRegistry::getConfig();

        parent::__construct($uuid);
        $this->_tpl = new cTemplate();
        $this->_template = $cfg['pica']['treetemplate'];
    }

    /**
     * Old constructor
     *
     * @deprecated [2016-02-11]
     *                This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     *
     * @param string $uuid
     *
     * @return pApiContentAllocationTreeView
     * @throws cDbException
     * @throws cException
     */
    public function pApiContentAllocationTreeView($uuid) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        return $this->__construct($uuid);
    }

    /**
     * Build an render tree
     *
     * @param array $tree
     * @return array $result html code
     */
    protected function _buildRenderTree($tree) {
        global $action, $frame, $area;
        $idart = cRegistry::getArticleId();
        $sess = cRegistry::getBackendSessionId();

        $result = array();
        foreach ($tree as $item_tmp) {
            $item = array();
            // update item
            if ($_GET['step'] == 'rename' && $item_tmp['idpica_alloc'] == $_GET['idpica_alloc']) {
                $item = array();
                $item['ITEMNAME'] = '
                    <table cellspacing="0" cellpaddin="0" border="0">
                    <form name="rename" action="main.php" method="POST" onsubmit="return fieldCheck();">
                    <input type="hidden" name="action" value="' . $action . '">
                    <input type="hidden" name="frame" value="' . $frame . '">
                    <input type="hidden" name="contenido" value="' . $sess . '">
                    <input type="hidden" name="area" value="' . $area . '">
                    <input type="hidden" name="step" value="storeRename">
                    <input type="hidden" name="treeItemPost[idpica_alloc]" value="' . $item_tmp['idpica_alloc'] . '">
                    <tr>
                    <td class="text_medium"><input id="itemname" class="text_medium" type="text" name="treeItemPost[name]" value="' . conHtmlentities($item_tmp['name']) . '"></td>
                    <td>&nbsp;
                    <a href="main.php?action=' . $action . '&frame=' . $frame . '&area=' . $area . '&contenido=' . $sess . '"><img src="images/but_cancel.gif"></a>
                    <input type="image" src="images/but_ok.gif">
                    </td></tr>
                    </form>
                    </table>
                    <script type="text/javascript">
                    var controller = document.getElementById("itemname");
                    controller.focus();
                    function fieldCheck() {
                        if (controller.value == "") {
                            alert("' . i18n("Please enter a category name", 'content_allocation') . '");
                            controller.focus();
                            return false;
                        }
                        return true;
                    }
                    </script>';
            } else {
                if ($item_tmp['children'] || $item_tmp['status'] == 'collapsed') {
                    $expandCollapseImg = 'images/close_all.gif';
                    if ($item_tmp['status'] == 'collapsed') {
                        $expandCollapseImg = 'images/open_all.gif';
                    }

                    $expandCollapse = '<a href="main.php?contenido=' . $sess . '&idart=' . $idart . '&action=' . $action . '&frame=' . $frame . '&area=' . $area .  '&oldstate=' . 'huhu' . '&step=collapse&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img src="' . $expandCollapseImg . '" class="vAlignMiddle" alt="" width="7" height="7"></a>';
                } else {
                    $expandCollapseImg = 'images/spacer.gif';
                    $expandCollapse = '<img src="' . $expandCollapseImg . '" alt="" class="vAlignMiddle" width="11" height="11">';
                }

                if ($item_tmp['status'] == 'collapsed') {
                    $expandCollapse = '<a href="main.php?contenido=' . $sess . '&idart=' . $idart . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=expanded&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img src="' . $expandCollapseImg . '" alt="" class="vAlignMiddle" width="7" height="7"></a>';
                }
                $item['ITEMNAME'] = $expandCollapse . ' ' . $item_tmp['name'];
            }
            $item['ITEMINDENT'] = $item_tmp['level'] * 15 + 3;
            $item['ACTION_CREATE'] = '<a href="main.php?contenido=' . $sess . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=add&parentid=' . $item_tmp['idpica_alloc'] . '"><img src="images/folder_new.gif" alt="" title="' . i18n("New category", 'content_allocation') . '" alt="' . i18n("New category", 'content_allocation') . '"></a>';

            $item['ACTION_RENAME'] = '<a href="main.php?contenido=' . $sess . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=rename&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img src="images/but_todo.gif" width="16" height="16" alt="' . i18n("Rename category", 'content_allocation') . '" title="' . i18n("Rename category", 'content_allocation') . '"></a>';
            $item['ACTION_MOVE_UP'] = (count($result) >= 1) ? '<a href="main.php?contenido=' . $sess . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=moveup&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img src="images/folder_moveup.gif" alt="' . i18n("Move category up", 'content_allocation') . '" title="' . i18n("Move category up", 'content_allocation') . '"></a>' : '<img src="images/spacer.gif" width="16" height="16"></a>';
            $item['ACTION_MOVE_DOWN'] = (count($result) >= 1) ? '<img src="images/folder_movedown.gif" alt="' . i18n("Move category down", 'content_allocation') . '" title="' . i18n("Move category down", 'content_allocation') . '">' : '<img src="images/spacer.gif" width="16" height="16">';
            $item['ACTION_MOVE_DOWN'] = '';

            if ($item_tmp['online'] == 1) { // set offline
                $item['ACTION_ONOFFLINE'] = '<a href="main.php?contenido=' . $sess . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=offline&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img src="images/online.gif" alt="' . i18n("Set category offline", 'content_allocation') . '" title="' . i18n("Set category offline", 'content_allocation') . '"></a>';
            } else {
                $item['ACTION_ONOFFLINE'] = '<a href="main.php?contenido=' . $sess . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=online&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img src="images/offline.gif" alt="' . i18n("Set category online", 'content_allocation') . '" title="' . i18n("Set category online", 'content_allocation') . '"></a>';
            }

            if ($item_tmp['children']) {
                $item['ACTION_DELETE'] = '<img src="images/delete_inact.gif" alt="' . i18n("One or more subcategories exist, unable to delete", 'content_allocation') . '" title="' . i18n("One or more subcategories exist, unable to delete", 'content_allocation') . '">';
            } else {
                $name = str_replace("\"", "&amp;quot;", str_replace("'", "\'", $item_tmp['name']));
                $item['ACTION_DELETE'] = '<a href="javascript:void(0)" onclick="Con.showConfirmation(&quot;' . i18n("Are you sure to delete the following category", 'content_allocation') . '&quot;, function() { deleteCategory(' . $item_tmp['idpica_alloc'] . '); });return false;"><img src="images/delete.gif" alt="' . i18n("Delete category") . '" title="' . i18n("Delete category", 'content_allocation') . '"></a>';
            }

            $result[] = $item;

            if ($item_tmp['children']) {
                $children = $this->_buildRenderTree($item_tmp['children']);
                $result = array_merge($result, $children);
            }

            // add new item -> show formular
            if ($_GET['step'] == 'add' && $item_tmp['idpica_alloc'] == $_GET['parentid']) {
                $item = array();

                $item['ITEMNAME'] = '
                    <table cellspacing="0" cellpaddin="0" border="0">
                    <form name="create" action="main.php" method="POST" onsubmit="return fieldCheck();">
                    <input type="hidden" name="action" value="' . $action . '">
                    <input type="hidden" name="frame" value="' . $frame . '">
                    <input type="hidden" name="contenido" value="' . $sess . '">
                    <input type="hidden" name="area" value="' . $area . '">
                    <input type="hidden" name="step" value="store">
                    <input type="hidden" name="treeItemPost[parentid]" value="' . cSecurity::toInteger($_GET['parentid']) . '">
                    <tr>
                    <td class="text_medium"><input id="itemname" class="text_medium" type="text" name="treeItemPost[name]" value=""></td>
                    <td>&nbsp;
                    <a href="main.php?action=' . $action . '&frame=' . $frame . '&area=' . $area . '&contenido=' . $sess . '"><img src="images/but_cancel.gif" alt=""></a>
                    <input type="image" src="images/but_ok.gif">
                    </td></tr>
                    </form>
                    </table>
                    <script type="text/javascript">
                    var controller = document.getElementById("itemname");
                    controller.focus();
                    function fieldCheck() {
                        if (controller.value == "") {
                            alert("' . i18n("Please enter a category name", 'content_allocation') . '");
                            controller.focus();
                            return false;
                        }
                        return true;
                    }
                    </script>';
                $item['ITEMINDENT'] = ($item_tmp['level'] + 1) * 15;
                $item['ACTION_CREATE'] = '<img src="images/spacer.gif" alt="" width="15" height="13">';
                $item['ACTION_RENAME'] = '<img src="images/spacer.gif" alt="" width="23" height="14">';
                $item['ACTION_MOVE_UP'] = '<img src="images/spacer.gif" alt="" width="15" height="13">';
                $item['ACTION_MOVE_DOWN'] = '<img src="images/spacer.gif" alt="" width="15" height="13">';
                $item['ACTION_MOVE_DOWN'] = '';
                $item['ACTION_DELETE'] = '<img src="images/spacer.gif" alt="" width="14" height="13">';
                $item['ACTION_ONOFFLINE'] = '<img src="images/spacer.gif" alt="" width="11" height="12">';

                array_push($result, $item);
            }
        }
        return $result;
    }

    /**
     * Rendering tree
     *
     * @param bool $return
     *
     * @return string|bool
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function renderTree($return = true) {
        $this->_tpl->reset();

        $tree = $this->fetchTree(false, 0, true); // modified 27.10.2005

        if ($tree === false) {
            return false;
        }

        $tree = $this->_buildRenderTree($tree);

        $even = true;
        foreach ($tree as $item) {
            $even = !$even;
            $bgcolor = ($even) ? '#FFFFFF' : '#F1F1F1';
            $this->_tpl->set('d', 'BACKGROUND_COLOR', $bgcolor);
            foreach ($item as $key => $value) {
                $this->_tpl->set('d', $key, $value);
            }
            $this->_tpl->next();
        }

        $this->_tpl->set('s', 'CATEGORY', i18n("Category", 'content_allocation'));
        $this->_tpl->set('s', 'ACTIONS', i18n("Actions", 'content_allocation'));

        if ($return === true) {
            return $this->_tpl->generate($this->_template, true);
        } else {
            $this->_tpl->generate($this->_template);
        }
    }

}
