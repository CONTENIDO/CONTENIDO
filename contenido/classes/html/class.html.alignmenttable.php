<?php

/**
 * This file contains the cHTMLAlignmentTable class.
 *
 * @package    Core
 * @subpackage GUI_HTML
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLAlignmentTable class represents an alignment table.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLAlignmentTable extends cHTMLTable
{
    /**
     * @var array
     */
    protected $_data;

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_data = func_get_args();
        $this->_contentlessTag = false;
    }

    /**
     * Generates the markup of the element.
     *
     * @return string
     *         generated markup
     * @see cHTML::render()
     */
    public function render(): string
    {
        $tr = new cHTMLTableRow();
        $td = new cHTMLTableData();

        $out = '';

        foreach ($this->_data as $data) {
            $td->setContent($data);
            $out .= $td->render();
        }

        $tr->setContent($out);

        $this->setContent($tr);

        return $this->toHtml();
    }

}
