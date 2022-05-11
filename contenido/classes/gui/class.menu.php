<?php

/**
 * This file contains the menu GUI class.
 *
 * @package          Core
 * @subpackage       GUI
 *
 * @author           Mischa Holz
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Menu GUI class
 *
 * @package    Core
 * @subpackage GUI
 */
class cGuiMenu {

    /**
     * The id of the generic menu
     * @var string
     */
    public $menuId;

    /**
     *
     * @var array
     */
    public $link;

    /**
     *
     * @var array
     */
    public $title = [];

    /**
     *
     * @var array
     */
    public $id = [];

    /**
     *
     * @var array
     */
    public $tooltips = [];

    /**
     * Menu item left image source
     * @var array
     */
    public $image = [];

    /**
     * Menu item left image width
     * @var array
     */
    public $imagewidth = [];

    /**
     * Menu item left image alternate text
     * @var array
     */
    public $imageAlt = [];

    /**
     *
     * @var unknown_type
     */
    public $alt;

    /**
     *
     * @var array
     */
    public $actions = [];

    /**
     *
     * @todo what is this property supposed to be?
     * @var unknown_type
     */
    public $caption;

    /**
     *
     * @todo what is this property supposed to be?
     * @var unknown_type
     */
    public $type;

    /**
     *
     * @todo what is this property supposed to be?
     * @var unknown_type
     */
    public $show;

    /**
     * The marked item.
     *
     * @var mixed
     */
    protected $_marked = false;

    /**
     * Constructor to create an instance of this class.
     * @param string $menuId
     */
    public function __construct($menuId = 'generic_menu_list') {
        $this->setRowmark(true);
        $this->setMenuId($menuId);
    }

    /**
     *
     * @param mixed $item
     * @param string $title
     */
    public function setTitle($item, $title) {
        $this->title[$item] = $title;
    }

    /**
     *
     * @param mixed $item
     * @param int|string $id
     */
    public function setId($item, $id) {
        $this->id[$item] = $id;
    }

    /**
     *
     * @param mixed $item
     * @param string $tooltip
     */
    public function setTooltip($item, $tooltip) {
        $this->tooltips[$item] = $tooltip;
    }

    /**
     *
     * @param bool $rowmark [optional]
     */
    public function setRowmark($rowmark = true) {
        $this->rowmark = $rowmark;
    }

    /**
     *
     * @param string $menuId
     */
    public function setMenuId($menuId = 'generic_menu_list') {
        $this->menuId = $menuId;
    }

    /**
     *
     * @param mixed $item
     * @param string $image
     * @param int $maxWidth [optional]
     * @param string $alt [optional]
     */
    public function setImage($item, $image, $maxWidth = 0, $alt = '') {
        $this->image[$item] = $image;
        $this->imagewidth[$item] = $maxWidth;
        $this->imageAlt[$item] = $alt;
        $this->show[$item] = '';
    }

    /**
     *
     * @param mixed $item
     * @param cHTMLContentElement $link
     */
    public function setLink($item, $link) {
        $this->link[$item] = $link;
    }

    /**
     *
     * @param mixed $item
     * @param mixed $key
     * @param string $action
     */
    public function setActions($item, $key, $action) {
        $this->actions[$item][$key] = $action;
    }

    /**
     *
     * @param mixed $item
     */
    public function setMarked($item) {
        $this->_marked = $item;
    }

    /**
     *
     * @param bool $print [optional]
     *
     * @return string
     * @throws cInvalidArgumentException
     */
    public function render($print = true) {
        $cfg = cRegistry::getConfig();
        $tpl = new cTemplate();

        $tpl->reset();
        $tpl->set('s', 'MENU_ID', $this->menuId);

        if (is_array($this->link)) {
            foreach ($this->link as $key => $value) {
                $img = '&nbsp;';

                if ($value != NULL) {
                    if (isset($this->image[$key])) {
                        $image = new cHTMLImage($this->image[$key], 'vAlignMiddle');
                        $image->setAlt($this->imageAlt[$key]);
                        if ($this->imagewidth[$key] != 0) {
                            $image->setWidth($this->imagewidth[$key]);
                        }
                        $value->setContent($image);
                        $img = $value->render();
                    }

                    $value->setContent($this->title[$key]);
                    $link = $value->render();
                } else {
                    $link = $this->title[$key];

                    if (isset($this->image[$key])) {
                        $image = new cHTMLImage($this->image[$key], 'vAlignMiddle');
                        $image->setAlt($this->imageAlt[$key]);
                        if ($this->imagewidth[$key] != 0) {
                            $image->setWidth($this->imagewidth[$key]);
                        }
                        $img = $image->render();
                    }
                }

                $tpl->set('d', 'NAME', $link);
                $tpl->set('d', 'ICON', $img);

                $extra = [];
                if (isset($this->id[$key])) {
                    $extra[] = 'data-id="' . $this->id[$key] . '"';
                }

                if ($this->_marked === $key) {
                    $extra[] = 'id="marked"';
                }
                if (!empty($this->tooltips[$key])) {
                    $extra[] = 'class="tooltip-north row_mark" original-title="' . $this->tooltips[$key] . '"';
                } else {
                    $extra[] = 'class="row_mark"';
                }
                $tpl->set('d', 'EXTRA', implode(' ', $extra));

                $actions = '';
                if (is_array($this->actions[$key])) {
                    foreach ($this->actions[$key] as $key => $singleAction) {
                        $actions .= '&nbsp;' . $singleAction . '&nbsp;';
                    }
                }
                if ($actions) {
                    $actions = str_replace('&nbsp;&nbsp;', '&nbsp;', $actions);
                }

                $tpl->set('d', 'ACTIONS', $actions);
                $tpl->next();
            }
        }
        $rendered = $tpl->generate(cRegistry::getBackendPath() . $cfg['path']['templates'] . $cfg['templates']['generic_menu'], true);

        if ($this->rowmark == true && is_array($this->link) && count($this->link) > 0) {
            $rendered .= "\n" . $this->_getRowMouseEventHandlerJs();
        }

        if ($print == true) {
            echo $rendered;
        } else {
            return $rendered;
        }
    }

    /**
     * Returns JavaScript code to initialize mouse event handler for the table rows.
     *
     * @return string
     */
    protected function _getRowMouseEventHandlerJs() {
        $js = <<<JS
<script type="text/javascript">
    (function(Con, $) {
        $(function() {
            Con.RowMark.initialize('#{$this->menuId} .row_mark', 'row', '#marked');
        });
    })(Con, Con.$);
</script>
JS;
        return $js;
    }
}
