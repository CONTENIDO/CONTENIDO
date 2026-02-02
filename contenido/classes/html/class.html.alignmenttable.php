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
     * @param mixed ...$arguments Data to be displayed in the alignment table
     */
    public function __construct(...$arguments)
    {
        parent::__construct();

        $this->_data = $arguments;
        $this->_contentlessTag = false;
    }

    /**
     * @inheritDoc
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
