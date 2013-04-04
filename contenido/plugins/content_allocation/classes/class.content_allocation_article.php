<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * ContentAllocation article
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Plugins
 * @subpackage ContentAllocation
 * @version    0.2.1
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @package    CONTENIDO Plugins
 * @subpackage ContentAllocation
 */
class pApiContentAllocationArticle extends pApiTree {

    var $tpl = null;
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