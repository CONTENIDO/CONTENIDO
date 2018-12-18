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
     *
     * @var array
     */
    public $link;

    /**
     *
     * @var array
     */
    public $title;

    /**
     *
     * @var array
     */
    public $tooltips;

    /**
     *
     * @var array
     */
    public $image;

    /**
     *
     * @var unknown_type
     */
    public $alt;

    /**
     *
     * @var array
     */
    public $actions;

    /**
     *
     * @var array
     */
    public $imagewidth;

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
     */
    public function __construct() {
        $this->rowmark = true;
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
     * @param mixed $item
     * @param string $image
     * @param int $maxwidth [optional]
     */
    public function setImage($item, $image, $maxwidth = 0) {
        $this->image[$item] = $image;
        $this->imagewidth[$item] = $maxwidth;
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
        global $cfg;

        $tpl = new cTemplate;

        $tpl->reset();

        if (is_array($this->link)) {
            foreach ($this->link as $key => $value) {
                if ($value != NULL) {
                    if ($this->imagewidth[$key] != 0) {
                        $value->setContent('<img border="0" alt="" src="' . $this->image[$key] . '" width="' . $this->imagewidth[$key] . '">');
                        $img = $value->render();
                    } else {
                        $value->setContent('<img border="0" alt="" src="' . $this->image[$key] . '">');
                        $img = $value->render();
                    }
                    $value->setContent($this->title[$key]);
                    $link = $value->render();
                } else {
                    $link = $this->title[$key];

                    if ($this->image[$key] != "") {
                        if ($this->imagewidth[$key] != 0) {
                            $img = '<img border="0" alt="" src="' . $this->image[$key] . '" width="' . $this->imagewidth[$key] . '">';
                        } else {
                            $img = '<img border="0" alt="" src="' . $this->image[$key] . '">';
                        }
                    } else {
                        $img = "&nbsp;";
                    }
                }

                $tpl->set('d', 'NAME', $link);

                if ($this->image[$key] == "") {
                    $tpl->set('d', 'ICON', '');
                } else {
                    $tpl->set('d', 'ICON', $img);
                }

                $extra = "";
                if ($this->rowmark == true) {
                    $extra .= 'onmouseover="row.over(this)" onmouseout="row.out(this)" onclick="row.click(this)" ';
                }
                if ($this->_marked === $key) {
                    $extra .= "id='marked' ";
                }
                if ($this->tooltips[$key] != "") {
                    $extra .= "class='tooltip-north' original-title='" . $this->tooltips[$key] . "' ";
                }
                $tpl->set('d', 'EXTRA', $extra);

                $fullactions = "";
                if (is_array($this->actions[$key])) {

                    $fullactions = '<table border="0"><tr>';

                    foreach ($this->actions[$key] as $key => $singleaction) {
                        $fullactions .= '<td nowrap="nowrap">' . $singleaction . '</td>';
                    }

                    $fullactions .= '</tr></table>';
                }

                $tpl->set('d', 'ACTIONS', $fullactions);
                $tpl->next();
            }
        }
        $rendered = $tpl->generate(cRegistry::getBackendPath() . $cfg['path']['templates'] . $cfg['templates']['generic_menu'], true);

        if ($print == true) {
            echo $rendered;
        } else {
            return $rendered;
        }
    }

}
