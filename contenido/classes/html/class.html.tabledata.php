<?php

/**
 * This file contains the cHTMLTableData class.
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
 * cHTMLTableData class represents a table date.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLTableData extends cHTMLContentElement
{

    /**
     * Constructor to create an instance of this class.
     *
     * @param string|object|array|null $content [optional] String or object with the contents
     */
    public function __construct($content = NULL)
    {
        parent::__construct($content);
        $this->_tag = 'td';
    }

    /**
     * Sets the table width
     *
     * @param int $width Width
     */
    public function setWidth($width): self
    {
        return $this->updateAttribute('width', cSecurity::toInteger($width));
    }

    /**
     * Sets the table height
     *
     * @param int $height Height
     */
    public function setHeight($height): self
    {
        return $this->updateAttribute('height', cSecurity::toInteger($height));
    }

    /**
     * Sets the table alignment
     *
     * @param string $alignment Alignment
     */
    public function setAlignment($alignment): self
    {
        return $this->updateAttribute('align', $alignment);
    }

    /**
     * Sets the table vertical alignment
     *
     * @param string $alignment Vertical Alignment
     */
    public function setVerticalAlignment($alignment): self
    {
        return $this->updateAttribute('valign', $alignment);
    }

    /**
     * Sets the table background color
     *
     * @param string $color background color
     */
    public function setBackgroundColor($color): self
    {
        return $this->updateAttribute('bgcolor', $color);
    }

    /**
     * Sets the table colspan
     *
     * @param string $colspan Colspan
     */
    public function setColspan($colspan): self
    {
        return $this->updateAttribute('colspan', $colspan);
    }

}
