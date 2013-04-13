<?php
/**
 * This file contains the list GUI class.
 *
 * @package          Core
 * @subpackage       GUI
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * List GUI class
 *
 * @package    Core
 * @subpackage GUI
 */
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

?>