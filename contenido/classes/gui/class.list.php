<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * A table in the form of a list
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2005-05-11
 *   $Id: class.list.php 2379 2012-06-22 21:00:16Z xmurrix $
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cGuiList {

    protected $cells;

    public function __construct() {
        $this->cells = array();
    }

    public function setCell($item, $cell, $value) {
        $this->cells[$item][$cell] = $value;
    }

    public function render($print = false) {
        global $cfg;

        $backendPath = cRegistry::getBackendPath();

        $tpl = new cTemplate();
        $tpl2 = new cTemplate();

        $colcount = 0;

        if (is_array($this->cells)) {
            foreach ($this->cells as $row => $cells) {
                $thefont = '';
                $unne = '';

                $colcount++;

                $content = "";
                $count = 0;

                foreach ($cells as $key => $value) {
                    $count++;
                    $tpl2->reset();

                    $tpl2->set('s', 'CONTENT', $value);
                    if ($colcount == 1) {
                        $content .= $tpl2->generate($backendPath . $cfg['path']['templates'] . $cfg['templates']['generic_list_head'], true);
                    } else {
                        $content .= $tpl2->generate($backendPath . $cfg['path']['templates'] . $cfg['templates']['generic_list_row'], true);
                    }
                }

                $tpl->set('d', 'ROWS', $content);
                $tpl->next();
            }
        }

        $rendered = $tpl->generate($backendPath . $cfg['path']['templates'] . $cfg['templates']['generic_list'], true);

        if ($print == true) {
            echo $rendered;
        } else {
            return $rendered;
        }
    }

}

/**
 * Old classname for downwards compatibility
 * @deprecated [2012-07-12] This class was renamed to cGuiList
 */
class UI_List extends cGuiList {

    public $extra;
    public $border;
    public $bgcolor;
    public $solid;
    public $width;
    public $padding;
    public $imagewidth;
    public $actions;
    public $link;
    public $title;
    public $caption;
    public $javascripts;
    public $type;
    public $image;
    public $alt;

    public function __construct() {
        cDeprecated("This class was renamed to cGuiList");

        parent::__construct();
    }

    public function setWidth($width) {
        cDeprecated("This function doesn't do anything. Please use CSS to style your elements");
    }

    public function setBgColor($item, $color) {
        cDeprecated("This function doesn't do anything. Please use CSS to style your elements");
    }

    public function setCellExtra($item, $cell, $extra) {
        cDeprecated("This function doesn't do anything. Please use CSS to style your elements");
    }

    public function setPadding($padding) {
        cDeprecated("This function doesn't do anything. Please use CSS to style your elements");
    }

    public function setBorder($border) {
        cDeprecated("This function doesn't do anything. Please use CSS to style your elements");
    }

    public function setExtra($item, $extra) {
        cDeprecated("This function doesn't do anything. Please use CSS to style your elements");
    }

    public function setSolidBorder($solid) {
        cDeprecated("This function doesn't do anything. Please use CSS to style your elements");
    }

    public function setCellAlignment($item, $cell, $alignment) {
        cDeprecated("This function doesn't do anything. Please use CSS to style your elements");
    }

    public function setCellVAlignment($item, $cell, $alignment) {
        cDeprecated("This function doesn't do anything. Please use CSS to style your elements");
    }

}

?>