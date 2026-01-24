<?php

/**
 * This file contains the cHTMLTable class.
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
 * cHTMLTable class represents a table.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLTable extends cHTMLContentElement
{

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML table element.
     */
    public function __construct()
    {
        parent::__construct();

        $this->_tag = 'table';
    }

    /**
     * Sets the spacing between cells
     *
     * @param string $cellspacing Spacing
     */
    public function setCellSpacing($cellspacing): self
    {
        return $this->updateAttribute('cellspacing', $cellspacing);
    }

    /**
     * Alias for setCellSpacing
     *
     * @param string $cellspacing
     */
    public function setSpacing($cellspacing): self
    {
        return $this->setCellSpacing($cellspacing);
    }

    /**
     * Sets the padding between cells
     *
     * @param string $cellpadding Padding
     */
    public function setCellPadding($cellpadding): self
    {
        return $this->updateAttribute('cellpadding', $cellpadding);
    }

    /**
     * Alias for setCellPadding
     *
     * @param string $cellpadding
     */
    public function setPadding($cellpadding): self
    {
        return $this->setCellPadding($cellpadding);
    }

    /**
     * Sets the table's border
     *
     * @param int $border Border size
     */
    public function setBorder($border): self
    {
        return $this->updateAttribute('border', cSecurity::toInteger($border));
    }

    /**
     * setWidth: Sets the table width.
     *
     * @param int|string $width Width in pixels or percentage (e.g. "100%")
     */
    public function setWidth($width): self
    {
        if (is_string($width) && substr($width, -1) === '%') {
            return $this->updateAttribute('width', $width);
        }
        return $this->updateAttribute('width', cSecurity::toInteger($width));
    }

}
