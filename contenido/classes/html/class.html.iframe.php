<?php

/**
 * This file contains the cHTMLIFrame class.
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
 * cHTMLIFrame class represents an iframe.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLIFrame extends cHTML
{

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML iframe element.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_contentlessTag = false;
        $this->_tag = 'iframe';
    }

    /**
     * Sets this frame's source
     *
     * @param string $src
     */
    public function setSrc($src): self
    {
        return $this->updateAttribute('src', $src);
    }

    /**
     * Sets this frame's width
     *
     * @param int $width Width of the item
     */
    public function setWidth($width): self
    {
        return $this->updateAttribute('width', cSecurity::toInteger($width));
    }

    /**
     * Sets this frame's height
     *
     * @param int $height Height of the item
     */
    public function setHeight($height): self
    {
        return $this->updateAttribute('height', cSecurity::toInteger($height));
    }

    /**
     * Sets weather this iframe should have a border or not
     *
     * @param int $border If 1 or true, this frame will have a border
     */
    public function setBorder($border): self
    {
        return $this->updateAttribute('frameborder', cSecurity::toInteger($border));
    }

}
