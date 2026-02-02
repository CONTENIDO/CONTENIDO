<?php

/**
 * This file contains the cHTMLImage class.
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
 * cHTMLImage class represents an image.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLImage extends cHTML
{

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML IMG element.
     *
     * @param ?string $src [optional] Image source
     * @param string $class [optional] The class of this element
     */
    public function __construct($src = NULL, $class = '')
    {
        parent::__construct();

        $this->_tag = 'img';
        $this->_contentlessTag = true;

        $this->setSrc($src);
        $this->setClass($class);
    }

    /**
     * Sets the image's source file
     *
     * @param string $src Source location
     */
    public function setSrc($src): self
    {
        if ($src === NULL) {
            $src = 'images/spacer.gif';
        }

        return $this->updateAttribute('src', $src);
    }

    /**
     * Sets the image's width
     *
     * @param int $width Image width
     */
    public function setWidth($width): self
    {
        return $this->updateAttribute('width', cSecurity::toInteger($width));
    }

    /**
     * Sets the image's height
     *
     * @param int $height Image height
     */
    public function setHeight($height): self
    {
        return $this->updateAttribute('height', cSecurity::toInteger($height));
    }

    /**
     * Sets the border size
     *
     * @param int $border Border size
     */
    public function setBorder($border): self
    {
        return $this->updateAttribute('border', cSecurity::toInteger($border));
    }

    /**
     * Apply dimensions from the source image
     */
    public function applyDimensions()
    {
        // Try to open the image
        list($width, $height) = @getimagesize(cRegistry::getBackendPath() . $this->getAttribute('src'));

        if (!empty($width) && !empty($height)) {
            $this->setWidth($width);
            $this->setHeight($height);
        }
    }

    /**
     * Renders an img tag.
     *
     * @param string $src The source (path) to the image
     * @param string $alt Alternate text
     * @param array $attributes Attributes to set
     * @since CONTENIDO 4.10.2
     */
    public static function img(string $src, string $alt = '', array $attributes = []): string
    {
        $img = new self($src);

        $img->setAlt($alt);
        $img->setAttributes(array_merge($img->getAttributes(), $attributes));

        return $img->toHtml();
    }

}
