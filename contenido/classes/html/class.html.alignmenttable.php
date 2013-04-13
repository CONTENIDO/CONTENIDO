<?php
/**
 * This file contains the cHTMLAlignmentTable class.
 *
 * @package Core
 * @subpackage GUI_HTML
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLAlignmentTable class represents an alignment table.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLAlignmentTable extends cHTMLTable {

    public function __construct() {
        parent::__construct();

        $this->_data = func_get_args();
        $this->_contentlessTag = false;
    }

    public function render() {
        $tr = new cHTMLTableRow();
        $td = new cHTMLTableData();

        $out = '';

        foreach ($this->_data as $data) {
            $td->setContent($data);
            $out .= $td->render();
        }

        $tr->setContent($out);

        $this->setContent($tr);

        return $this->toHTML();
    }

}
