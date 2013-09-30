<?php
/**
 * This file contains the cHTMLTableData class.
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
 * cHTMLTableData class represents a table date.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLTableData extends cHTMLContentElement {

    /**
     * Creates an HTML td element.
     *
     * @return void
     */
    public function __construct($content = NULL) {
        parent::__construct($content);
        $this->_tag = 'td';
    }

    /**
     * Sets the table width
     *
     * @param string $width Width
     * @return cHTMLTableData $this
     */
    public function setWidth($width) {
        return $this->updateAttribute('width', $width);
    }

    /**
     * Sets the table height
     *
     * @param string $height Height
     * @return cHTMLTableData $this
     */
    public function setHeight($height) {
        return $this->updateAttribute('height', $height);
    }

    /**
     * Sets the table alignment
     *
     * @param string $alignment Alignment
     * @return cHTMLTableData $this
     */
    public function setAlignment($alignment) {
        return $this->updateAttribute('align', $alignment);
    }

    /**
     * Sets the table vertical alignment
     *
     * @param string $alignment Vertical Alignment
     * @return cHTMLTableData $this
     */
    public function setVerticalAlignment($alignment) {
        return $this->updateAttribute('valign', $alignment);
    }

    /**
     * Sets the table background color
     *
     * @param string $color background color
     * @return cHTMLTableData $this
     */
    public function setBackgroundColor($color) {
        return $this->updateAttribute('bgcolor', $color);
    }

    /**
     * Sets the table colspan
     *
     * @param string $colspan Colspan
     * @return cHTMLTableData $this
     */
    public function setColspan($colspan) {
        return $this->updateAttribute('colspan', $colspan);
    }

}
