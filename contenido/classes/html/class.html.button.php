<?php

/**
 * This file contains the cHTMLButton class.
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
 * cHTMLButton class represents a button.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLButton extends cHTMLFormElement
{

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML button.
     *
     * Creates a submit button by default, can be changed using setMode.
     *
     * @param string $name Name of the element
     * @param string $title [optional] Title of the button
     * @param string $id [optional] ID of the element
     * @param bool $disabled [optional] Item disabled flag (non-empty to set disabled)
     * @param ?int $tabindex [optional] Tab index for form elements
     * @param string $accessKey [optional] Key to access the field
     * @param string $mode [optional] Mode of button
     * @param string $class [optional] The class of this element
     */
    public function __construct(
        $name,
        $title = '',
        $id = '',
        $disabled = false,
        $tabindex = null,
        $accessKey = '',
        $mode = 'submit',
        $class = ''
    )
    {
        parent::__construct($name, $id, $disabled, $tabindex, $accessKey, $class);
        $this->_tag = 'input';
        $this->_contentlessTag = true;
        $this->setTitle($title);
        $this->setMode($mode);
    }

    /**
     * Sets the title (caption) for the button
     *
     * @param string $title The title to set
     */
    public function setTitle($title): self
    {
        return $this->updateAttribute('value', $title);
    }

    /**
     * Sets the mode (submit or reset) for the button
     *
     * @param string $mode Either 'submit', 'reset' or 'image'.
     */
    public function setMode($mode): self
    {
        $modes = [
            'submit',
            'reset',
            'image',
            'button',
        ];
        if (in_array($mode, $modes)) {
            $this->updateAttribute('type', $mode);
        }

        return $this;
    }

    /**
     * Set the image src if mode type is "image"
     *
     * @param string $src Image path.
     */
    public function setImageSource($src): self
    {
        return $this->setMode('image')
            ->updateAttribute('src', $src);
    }

    /**
     * Renders a html input tag of type image.
     *
     * @param string $src The source (path) to the image
     * @param string $alt Alternate text
     * @param array $attributes Attributes to set
     * @return string
     * @since CONTENIDO 4.10.2
     */
    public static function image($src, $alt = '', array $attributes = [])
    {
        $button = new self('');

        $attributes = array_merge([
            'type' => 'image',
            'src' => $src,
            'alt' => $alt,
            'title' => $alt,
        ], $attributes);

        $button->setAttributes(array_merge($button->getAttributes(), $attributes));

        return $button->toHtml();
    }

}
