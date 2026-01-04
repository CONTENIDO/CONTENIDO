<?php

/**
 * This file contains the complex list class for the plugin content allocation.
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
 * Complex list class for content allocation
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 */
class pApiContentAllocationComplexList extends pApiTree
{

    /**
     * @var bool
     */
    protected $_idSetter = true;

    /**
     * @var array
     */
    protected $_load = [];

    /**
     * pApiContentAllocationComplexList constructor
     *
     * @throws cDbException|cException
     */
    public function __construct(string $uuid)
    {
        parent::__construct($uuid);
    }

    /**
     * Builds a render tree
     */
    protected function _buildRenderTree(array $tree): string
    {
        $oldIdSetter = $this->_idSetter;
        $this->_idSetter = false;

        $result = '';

        $even = true;

        $levelElms = count($tree);
        $cnt = 1;
        foreach ($tree as $item_tmp) {
            if (in_array($item_tmp['idpica_alloc'], $this->_load)) {
                $checked = ' checked="checked"';
            } else {
                $checked = '';
            }

            $li_closeElm = '';
            if ($cnt == $levelElms) {
                $li_closeElm = 'class="lastElem"';
            }
            $cnt++;

            $even = !$even;
            $bgColor = ($even) ? 'bright' : 'dark';

            // for wrapping purposes
            $item_tmp['name'] = str_replace('-', '- ', $item_tmp['name']);

            $checkbox = '<input type="checkbox" name="allocation[]" onClick="addToList(this);" ' . $checked . '" id="e' . $item_tmp['idpica_alloc'] . '" value="' . $item_tmp['idpica_alloc'] . '">';
            $item = "\n<li baseClass=\"" . $bgColor . "\" " . $li_closeElm . ">" . $checkbox . " " . $item_tmp['name'];

            $result .= $item;

            if (count($item_tmp['children'])) {
                $children = $this->_buildRenderTree($item_tmp['children']);
                $result .= "\n<ul>" . $children . "</li>";
            } else {
                $result .= "\n</li>";
            }
        }

        if ($oldIdSetter === true) {
            return "\n<ul id=\"finder\">" . $result . "\n</ul>";
        } else {
            return $result . "\n</ul>";
        }
    }

    /**
     * Set method for load
     *
     * @param array $load
     */
    public function setChecked(array $load)
    {
        $this->_load = $load;
    }

    /**
     * Render tree
     *
     * @throws cDbException
     */
    public function renderTree(bool $return = true): ?string
    {
        $tree = $this->fetchTree();
        if (!$tree) {
            return null;
        }

        $tree = $this->_buildRenderTree($tree);

        return $return ? $tree : null;
    }

}
