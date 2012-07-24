<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Manages HTML menus
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO Backend
 * @subpackage GUI
 * @author     mischa.holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2012-07-12
 *   $Id: class.menu.php 2379 2012-06-22 21:00:16Z xmurrix $:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cGuiMenu {

    public $link;
    public $title;
    public $caption;
    public $type;
    public $image;
    public $alt;
    public $actions;
    public $imagewidth;
    public $show;
    protected $_marked;

    public function __construct() {
        $this->rowmark = true;
        $this->_marked = false;
    }

    public function setTitle($item, $title) {
        $this->title[$item] = $title;
    }

    public function setRowmark($rowmark = true) {
        $this->rowmark = $rowmark;
    }

    public function setImage($item, $image, $maxwidth = 0) {
        $this->image[$item] = $image;
        $this->imagewidth[$item] = $maxwidth;
        $this->show[$item] = $show;
    }

    public function setLink($item, $link) {
        $this->link[$item] = $link;
    }

    public function setActions($item, $key, $action) {
        $this->actions[$item][$key] = $action;
    }

    public function setMarked($item) {
        $this->_marked = $item;
    }

    public function render($print = true) {
        global $cfg;

        $tpl = new cTemplate;

        $tpl->reset();

        if (is_array($this->link)) {
            foreach ($this->link as $key => $value) {
                if ($value != NULL) {
                    if ($this->imagewidth[$key] != 0) {
                        $value->setContent('<img border="0" src="' . $this->image[$key] . '" width="' . $this->imagewidth[$key] . '">');
                        $img = $value->render();
                    } else {
                        $value->setContent('<img border="0" src="' . $this->image[$key] . '">');
                        $img = $value->render();
                    }
                    $value->setContent($this->title[$key]);
                    $link = $value->render();
                } else {
                    $link = $this->title[$key];

                    if ($this->image[$key] != "") {
                        if ($this->imagewidth[$key] != 0) {
                            $img = '<img border="0" src="' . $this->image[$key] . '" width="' . $this->imagewidth[$key] . '">';
                        } else {
                            $img = '<img border="0" src="' . $this->image[$key] . '">';
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
                    $extra .= "id='marked'";
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
        $rendered = $tpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_menu'], true);

        if ($print == true) {
            echo $rendered;
        } else {
            return $rendered;
        }
    }

}

/**
 * Old class name for downwards compatibility
 * @deprecated [2012-07-12] This class was renamend to cGuiMenu
 */
class UI_Menu extends cGuiMenu {

    public $extra;
    public $border;
    public $padding;
    public $javascripts;

    public function __construct() {
        cDeprecated("This class was renamend to cGuiMenu");

        parent::__construct();
    }

    public function setBgColor($color) {
        cDeprecated("This functions doesn't do anything. Please use CSS to style your elements");
    }

    public function setPadding($padding) {
        cDeprecated("This functions doesn't do anything. Please use CSS to style your elements");
    }

    public function setBorder($border) {
        cDeprecated("This functions doesn't do anything. Please use CSS to style your elements");
    }

    public function setExtra($extra) {
        cDeprecated("This functions doesn't do anything. Please use CSS to style your elements");
    }

}

?>