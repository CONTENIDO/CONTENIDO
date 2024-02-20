<?php

/**
 * This file contains the cHTMLUpload class.
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
 * cHTMLUpload class represents a file upload element.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLUpload extends cHTMLFormElement
{

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML upload box.
     *
     * If no additional parameters are specified, the
     * default width is 20 units.
     *
     * @param string $name
     *         Name of the element
     * @param int $width [optional]
     *         width of the text box
     * @param int $maxlength [optional]
     *         maximum input length of the box
     * @param string $id [optional]
     *         ID of the element
     * @param bool $disabled [optional]
     *         Item disabled flag (non-empty to set disabled)
     * @param int|null $tabindex [optional]
     *         Tab index for form elements
     * @param string $accesskey [optional]
     *         Key to access the field
     * @param string $class [optional]
     *         the class of this element
     */
    public function __construct($name, $width = '', $maxlength = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '', $class = '')
    {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey, $class);
        $this->_tag = 'input';
        $this->_contentlessTag = true;

        $this->setWidth($width);
        $this->setMaxLength($maxlength);

        $this->updateAttribute('type', 'file');
    }

    /**
     * Sets the width of the text box.
     *
     * @param int $width
     *         width of the text box
     * @return cHTMLUpload
     *         $this for chaining
     */
    public function setWidth($width)
    {
        $width = intval($width);

        if ($width <= 0) {
            $width = 20;
        }

        return $this->updateAttribute('size', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $maxlen
     *         maximum input length
     * @return cHTMLUpload
     *         $this for chaining
     */
    public function setMaxLength($maxlen)
    {
        $maxlen = intval($maxlen);

        if ($maxlen <= 0) {
            return $this->removeAttribute('maxlength');
        } else {
            return $this->updateAttribute('maxlength', $maxlen);
        }
    }

}
