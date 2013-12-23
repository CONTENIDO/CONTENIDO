<?php
/**
 * This file contains the cHTMLTable class.
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
 * cHTMLTable class represents a table.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLTable extends cHTMLContentElement {

    /**
     * Creates an HTML table element.
     */
    public function __construct() {
        parent::__construct();

        $this->_tag = 'table';
        $this->setPadding(0);
        $this->setSpacing(0);
        $this->setBorder(NULL);
    }

    /**
     * Sets the spacing between cells
     *
     * @param string $cellspacing Spacing
     * @return cHTMLTable $this
     */
    public function setCellSpacing($cellspacing) {
        return $this->updateAttribute('cellspacing', $cellspacing);
    }

    /**
     * Alias for setCellSpacing
     *
     * @param string $cellspacing
     * @return cHTMLTable $this
     */
    public function setSpacing($cellspacing) {
        return $this->setCellSpacing($cellspacing);
    }

    /**
     * Sets the padding between cells
     *
     * @param string $cellpadding Padding
     * @return cHTMLTable $this
     */
    public function setCellPadding($cellpadding) {
        return $this->updateAttribute('cellpadding', $cellpadding);
    }

    /**
     * Alias for setCellPadding
     *
     * @param string $cellpadding
     * @return cHTMLTable $this
     */
    public function setPadding($cellpadding) {
        return $this->setCellPadding($cellpadding);
    }

    /**
     * Sets the table's border
     *
     * @param string $border Border size
     * @return cHTMLTable $this
     */
    public function setBorder($border) {
        return $this->updateAttribute('border', $border);
    }

    /**
     * setWidth: Sets the table width
     *
     * @param string $width Width
     * @return cHTMLTable $this
     */
    public function setWidth($width) {
        return $this->updateAttribute('width', $width);
    }

}
