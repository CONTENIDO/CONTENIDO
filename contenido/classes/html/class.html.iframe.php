<?php
/**
 * This file contains the cHTMLIFrame class.
 *
 * @package Core
 * @subpackage HTML
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * cHTMLIFrame class represents an iframe.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLIFrame extends cHTML {

    /**
     * Creates an HTML iframe element.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->_contentlessTag = false;
        $this->_tag = 'iframe';
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLIFrame() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Sets this frame's source
     *
     * @param string|object $content String with the content or an object to
     *        render.
     * @return cHTMLIFrame $this
     */
    public function setSrc($src) {
        return $this->updateAttribute('src', $src);
    }

    /**
     * Sets this frame's width
     *
     * @param string $width Width of the item
     * @return cHTMLIFrame $this
     */
    public function setWidth($width) {
        return $this->updateAttribute('width', $width);
    }

    /**
     * Sets this frame's height
     *
     * @param string $height Height of the item
     * @return cHTMLIFrame $this
     */
    public function setHeight($height) {
        return $this->updateAttribute('height', $height);
    }

    /**
     * Sets wether this iframe should have a border or not
     *
     * @param string $border If 1 or true, this frame will have a border
     * @return cHTMLIFrame $this
     */
    public function setBorder($border) {
        return $this->updateAttribute('frameborder', intval($border));
    }

}
