<?php

/**
 * This file contains the tree view class for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

plugin_include('repository', 'custom/FrontendNavigation.php');

/**
 * Tree view class for content allocation
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 */
class pApiContentAllocationTreeView extends pApiTree
{

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
    public function __construct($uuid)
    {
        $cfg = cRegistry::getConfig();

        parent::__construct($uuid);
        $this->_tpl = new cTemplate();
        $this->_template = $cfg['pica']['treetemplate'];
    }

    /**
     * Build and render tree
     *
     * @param array $tree
     * @return array $result html code
     * @throws cException
     */
    protected function _buildRenderTree(array $tree): array
    {
        $idart = cRegistry::getArticleId();
        $sess = cRegistry::getBackendSessionId();
        $area = cRegistry::getArea();
        $action = cRegistry::getAction();
        $frame = cRegistry::getFrame();

        $requestIdPicaAlloc = cSecurity::toInteger($_GET['idpica_alloc'] ?? '0');
        $requestGetStep = $_GET['step'] ?? '';
        $requestParentId = cSecurity::toInteger($_GET['parentid'] ?? '0');

        $txtNewCategory = i18n("New category", 'content_allocation');
        $txtRenameCategory = i18n("Rename category", 'content_allocation');
        $txtMoveCategoryUp = i18n("Move category up", 'content_allocation');
        $txtMoveCategoryDown = i18n("Move category down", 'content_allocation');
        $txtSetCategoryOffline = i18n("Set category offline", 'content_allocation');
        $txtSetCategoryOnline = i18n("Set category online", 'content_allocation');
        $txtUnableToDelete = i18n("One or more subcategories exist, unable to delete", 'content_allocation');
        $txtDeleteCategory = i18n("Delete category", 'content_allocation');
        $txtConfirmDeletion = i18n("Are you sure to delete the following category", 'content_allocation');

        $result = [];
        foreach ($tree as $item_tmp) {
            $item = [];
            // update item
            if ($requestGetStep == 'rename' && $item_tmp['idpica_alloc'] == $requestIdPicaAlloc) {
                $item['ITEMNAME'] = piContentAllocationBuildContentAllocationForm(
                    $requestGetStep, 'storeRename', $action, $frame, $sess, $area,
                    'treeItemPost[idpica_alloc]', $item_tmp['idpica_alloc'], $item_tmp['name']
                );
            } else {
                if (count($item_tmp['children']) || $item_tmp['status'] == 'collapsed') {
                    $expandCollapseImg = 'images/close_all.gif';
                    if ($item_tmp['status'] == 'collapsed') {
                        $expandCollapseImg = 'images/open_all.gif';
                    }

                    $expandCollapse = '<a class="con_img_button text_center" href="main.php?contenido=' . $sess . '&idart=' . $idart . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&oldstate=' . 'huhu' . '&step=collapse&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img class="mgt3" src="' . $expandCollapseImg . '" alt=""></a>';
                } else {
                    $expandCollapseImg = 'images/spacer.gif';
                    $expandCollapse = '<img class="con_img_button_off" src="' . $expandCollapseImg . '" alt="">';
                }

                if ($item_tmp['status'] == 'collapsed') {
                    $expandCollapse = '<a class="con_img_button text_center" href="main.php?contenido=' . $sess . '&idart=' . $idart . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=expanded&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img class="mgt3" src="' . $expandCollapseImg . '" alt=""></a>';
                }
                $item['ITEMNAME'] = $expandCollapse . ' ' . $item_tmp['name'];
            }
            $item['ITEMINDENT'] = $item_tmp['level'] * 15 + 3;
            $item['ACTION_CREATE'] = '<a class="con_img_button" href="main.php?contenido=' . $sess . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=add&parentid=' . $item_tmp['idpica_alloc'] . '"><img src="images/folder_new.gif" alt="" title="' . $txtNewCategory . '" alt="' . $txtNewCategory . '"></a>';

            $item['ACTION_RENAME'] = '<a class="con_img_button" href="main.php?contenido=' . $sess . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=rename&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img src="images/but_todo.gif" alt="' . $txtRenameCategory . '" title="' . $txtRenameCategory . '"></a>';
            $item['ACTION_MOVE_UP'] = (count($result) >= 1) ? '<a class="con_img_button" href="main.php?contenido=' . $sess . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=moveup&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img src="images/folder_moveup.gif" alt="' . $txtMoveCategoryUp . '" title="' . $txtMoveCategoryUp . '"></a>' : '<img class="con_img_button" src="images/spacer.gif" alt=""></a>';
            // Move down action is not used at the moment!
            // $item['ACTION_MOVE_DOWN'] = (count($result) >= 1) ? '<img class="con_img_button" src="images/folder_movedown.gif" alt="' . $txtMoveCategoryDown . '" title="' . $txtMoveCategoryDown . '">' : '<img class="con_img_button" src="images/spacer.gif" alt="">';
            $item['ACTION_MOVE_DOWN'] = '';

            if ($item_tmp['online'] == 1) { // set offline
                $item['ACTION_ONOFFLINE'] = '<a class="con_img_button" href="main.php?contenido=' . $sess . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=offline&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img src="images/online.gif" alt="' . $txtSetCategoryOffline . '" title="' . $txtSetCategoryOffline . '"></a>';
            } else {
                $item['ACTION_ONOFFLINE'] = '<a class="con_img_button" href="main.php?contenido=' . $sess . '&action=' . $action . '&frame=' . $frame . '&area=' . $area . '&step=online&idpica_alloc=' . $item_tmp['idpica_alloc'] . '"><img src="images/offline.gif" alt="' . $txtSetCategoryOnline . '" title="' . $txtSetCategoryOnline . '"></a>';
            }

            if (count($item_tmp['children'])) {
                $item['ACTION_DELETE'] = '<img class="con_img_button" src="images/delete_inact.gif" alt="' . $txtUnableToDelete . '" title="' . $txtUnableToDelete . '">';
            } else {
                $item['ACTION_DELETE'] = '<a class="con_img_button" href="javascript:void(0)" onclick="Con.showConfirmation(&quot;' . $txtConfirmDeletion . '&quot;, function() { piContentAllocationDeleteCategory(' . $item_tmp['idpica_alloc'] . '); });return false;"><img src="images/delete.gif" alt="' . $txtDeleteCategory . '" title="' . $txtDeleteCategory . '"></a>';
            }

            $result[] = $item;

            if (count($item_tmp['children'])) {
                $children = $this->_buildRenderTree($item_tmp['children']);
                $result = array_merge($result, $children);
            }

            // add new item -> show form
            if ($requestGetStep == 'add' && $item_tmp['idpica_alloc'] == $requestParentId) {
                $item = [];

                $item['ITEMNAME'] = piContentAllocationBuildContentAllocationForm(
                    $requestGetStep, 'store', $action, $frame, $sess, $area,
                    'treeItemPost[parentid]', $requestParentId, ''
                );
                $item['ITEMINDENT'] = ($item_tmp['level'] + 1) * 15;
                $item['ACTION_CREATE'] = '<img class="con_img_button" src="images/spacer.gif" alt="">';
                $item['ACTION_RENAME'] = '<img class="con_img_button" src="images/spacer.gif" alt="">';
                $item['ACTION_MOVE_UP'] = '<img class="con_img_button" src="images/spacer.gif" alt=">';
                // Move down action is not used at the moment!
                // $item['ACTION_MOVE_DOWN'] = '<img src="images/spacer.gif" alt="" width="15" height="13">';
                $item['ACTION_MOVE_DOWN'] = '';
                $item['ACTION_DELETE'] = '<img class="con_img_button" src="images/spacer.gif" alt="">';
                $item['ACTION_ONOFFLINE'] = '<img class="con_img_button" src="images/spacer.gif" alt="">';

                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Rendering tree
     *
     * @param bool $return
     *
     * @return string|bool|void
     * @throws cDbException
     * @throws cInvalidArgumentException|cException
     */
    public function renderTree(bool $return = true)
    {
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
