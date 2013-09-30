<?php
/**
 * This file contains the article class for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 * @version    SVN Revision $Rev:$
 *
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

plugin_include('repository', 'custom/FrontendNavigation.php');

/**
 * Article class for content allocation
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 */
class pApiContentAllocationArticle extends pApiTree {

    var $tpl = NULL;
    var $template = '';

    var $load = array();

    function pApiContentAllocationArticle ($uuid) {
        global $cfg;

        parent::pApiTree($uuid);
        $this->tpl = new cTemplate;
        $this->template = $cfg['pica']['treetemplate_article'];
    }

    function _buildRenderTree ($tree) {
        global $action, $frame, $area, $sess, $idart;

        $result = array();
        foreach ($tree as $item_tmp) {
            $item = array();

            $expandCollapseImg = 'images/spacer.gif';
            $expandCollapse = '<img class="vAlignMiddle" src="'.$expandCollapseImg.'" border="0" width="11" height="11">';

            $item['ITEMNAME'] = $expandCollapse . ' ' . $item_tmp['name'];

            $item['ITEMINDENT'] = $item_tmp['level'] * 15 + 3;

            // set checked!
            $checked = '';
            if (in_array($item_tmp['idpica_alloc'], $this->load)) {
                $checked = ' checked="checked"';
            }
            $item['CHECKBOX'] = '<input type="checkbox" name="allocation[]" value="'.$item_tmp['idpica_alloc'].'" '.$checked.'>';

            array_push($result, $item);

            if ($item_tmp['children']) {
                $children = $this->_buildRenderTree($item_tmp['children']);
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }

    function setChecked($load) {
        $this->load = $load;
    }

    function renderTree ($return = true) {
        $this->tpl->reset();

        $tree = $this->fetchTree();
        if ($tree === false) {
            return false;
        }

        $tree = $this->_buildRenderTree($tree);

        $even = true;
        foreach ($tree as $item) {
            $even = !$even;
            $bgcolor = ($even) ? '#FFFFFF' : '#F1F1F1';
            $this->tpl->set('d', 'BACKGROUND_COLOR', $bgcolor);
            foreach ($item as $key => $value) {
                $this->tpl->set('d', $key, $value);
            }
            $this->tpl->next();
        }

        $this->tpl->set('s', "CATEGORY", i18n("Category", 'content_allocation'));

        if ($return === true) {
            return $this->tpl->generate($this->template, true);
        } else {
            $this->tpl->generate($this->template);
        }
    }
}

?>