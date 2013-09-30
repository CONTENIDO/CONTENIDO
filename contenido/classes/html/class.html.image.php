<?php
/**
 * This file contains the cHTMLImage class.
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
 * cHTMLImage class represents an image.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLImage extends cHTML {

    /**
     * Constructor.
     * Creates an HTML IMG element.
     *
     * @param mixed $content String or object with the contents
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($src = NULL, $class = '') {
        parent::__construct();

        $this->_tag = 'img';
        $this->_contentlessTag = true;

        $this->setSrc($src);
        $this->setClass($class);
    }

    /**
     * Sets the image's source file
     *
     * @param string $src source location
     * @return cHTMLImage $this
     */
    public function setSrc($src) {
        if ($src === NULL) {
            $src = 'images/spacer.gif';
        }

        return $this->updateAttribute('src', $src);
    }

    /**
     * Sets the image's width
     *
     * @param int $width Image width
     * @return cHTMLImage $this
     */
    public function setWidth($width) {
        return $this->updateAttribute('width', $width);
    }

    /**
     * Sets the image's height
     *
     * @param int $height Image height
     * @return cHTMLImage $this
     */
    public function setHeight($height) {
        return $this->updateAttribute('height', $height);
    }

    /**
     * Sets the border size
     *
     * @param int $border Border size
     * @return cHTMLImage $this
     */
    public function setBorder($border) {
        return $this->updateAttribute('border', $border);
    }

    /**
     * Apply dimensions from the source image
     */
    public function applyDimensions() {
        // Try to open the image
        list($width, $height) = @getimagesize(cRegistry::getBackendPath() . $this->getAttribute('src'));

        if (!empty($width) && !empty($height)) {
            $this->setWidth($width);
            $this->setHeight($height);
        }
    }

}
