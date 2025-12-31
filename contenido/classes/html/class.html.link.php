<?php

/**
 * This file contains the cHTMLLink class.
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
 * cHTMLLink class represents a link.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLLink extends cHTMLContentElement
{
    /**
     * Stores the link location
     * @var string
     */
    protected $_link;

    /**
     * Stores the anchor
     * @var string
     */
    protected $_anchor;

    /**
     * Stores the custom entries
     * @var array
     */
    protected $_custom;

    /**
     * @var string
     */
    protected $_image = '';

    /**
     * @var string
     */
    protected $_targetarea;

    /**
     * @var string
     */
    protected $_targetframe;

    /**
     * @var string
     */
    protected $_targetaction;

    /**
     * @var string
     */
    protected $_type;

    /**
     * @var string
     */
    protected $_targetarea2;

    /**
     * @var string
     */
    protected $_targetaction2;

    /**
     * @var string
     */
    protected $_targetframe2;

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML link.
     *
     * @param string $href [optional] String with the location to link to
     * @param mixed $content [optional] String or object with the contents
     * @param string $class [optional] The class of this element
     * @param string $id [optional] The ID of this element
     */
    public function __construct($href = '', $content = '', $class = '', $id = '')
    {
        parent::__construct($content, $class, $id);

        $this->setLink($href);
        $this->_tag = 'a';

        // Check for backend
        $sess = cRegistry::getSession();
        if (is_object($sess) && get_class($sess) === 'cSession') {
            $this->enableAutomaticParameterAppend();
        }
    }

    /**
     * Sets JavaScript onclick attribute event which appends registered parameters to the link.
     */
    public function enableAutomaticParameterAppend(): self
    {
        return $this->setEvent(
            'click',
            'var doit = true; try { var i = get_registered_parameters() } catch (e) { doit = false; }; if (doit == true) { this.href += i; }'
        );
    }

    /**
     * Removes the JavaScript onclick event attribute
     */
    public function disableAutomaticParameterAppend(): self
    {
        return $this->unsetEvent('click');
    }

    /**
     * Sets the link to a specific location
     *
     * @param string $href String with the location to link to
     */
    public function setLink($href): self
    {
        $this->_link = $href;
        $this->_type = 'link';

        if (cString::findFirstPos($href, 'javascript:') !== false) {
            $this->disableAutomaticParameterAppend();
        }

        return $this;
    }

    /**
     * Sets the target frame
     *
     * @param string $target Target frame identifier
     */
    public function setTargetFrame($target): self
    {
        return $this->updateAttribute('target', $target);
    }

    /**
     * Sets a CONTENIDO link (area, frame, action)
     *
     * @param string $targetarea Target backend area
     * @param string $targetframe Target frame (1-4)
     * @param string $targetaction [optional] Target action
     */
    public function setCLink($targetarea, $targetframe, $targetaction = ''): self
    {
        $this->_targetarea = $targetarea;
        $this->_targetframe = $targetframe;
        $this->_targetaction = $targetaction;
        $this->_type = 'clink';

        return $this;
    }

    /**
     * Sets a multilink
     *
     * @param string $righttoparea Area (right top)
     * @param string $righttopaction Action (right top)
     * @param string $rightbottomarea Area (right bottom)
     * @param string $rightbottomaction Action (right bottom)
     */
    public function setMultiLink($righttoparea, $righttopaction, $rightbottomarea, $rightbottomaction): self
    {
        $this->_targetarea = $righttoparea;
        $this->_targetframe = 3;
        $this->_targetaction = $righttopaction;
        $this->_targetarea2 = $rightbottomarea;
        $this->_targetframe2 = 4;
        $this->_targetaction2 = $rightbottomaction;
        $this->_type = 'multilink';

        return $this;
    }

    /**
     * Sets a custom attribute to be appended to the link
     *
     * @param string $key Parameter name
     * @param string $value Parameter value
     */
    public function setCustom($key, $value): self
    {
        $this->_custom[$key] = $value;

        return $this;
    }

    /**
     * @param string $src
     */
    public function setImage($src): self
    {
        $this->_image = $src;

        return $this;
    }

    /**
     * Unsets a previous set custom attribute
     *
     * @param string $key Parameter name
     */
    public function unsetCustom($key): self
    {
        if (isset($this->_custom[$key])) {
            unset($this->_custom[$key]);
        }

        return $this;
    }

    public function getHref(): string
    {
        $sess = cRegistry::getSession();

        $custom = '';
        if (is_array($this->_custom)) {
            foreach ($this->_custom as $key => $value) {
                $custom .= "&$key=$value";
            }
        }

        $anchor = $this->_anchor ? '#' . $this->_anchor : '';

        switch ($this->_type) {
            case 'link':
                $custom = '';
                if (is_array($this->_custom)) {
                    foreach ($this->_custom as $key => $value) {
                        if ($custom == '') {
                            $custom .= "?$key=$value";
                        } else {
                            $custom .= "&$key=$value";
                        }
                    }
                }

                return $this->_link . $custom . $anchor;
            case 'clink':
                $this->disableAutomaticParameterAppend();
                return sprintf(
                    'main.php?area=%s&frame=%d&action=%s&contenido=%s',
                    $this->_targetarea,
                    $this->_targetframe,
                    $this->_targetaction . $custom,
                    $sess->id . $anchor
                );
            case 'multilink':
                $this->disableAutomaticParameterAppend();
                return sprintf(
                    "javascript:Con.multiLink('%s','%s','%s','%s');",
                    'right_top',
                    $sess->url(sprintf(
                        'main.php?area=%s&frame=%d&action=%s',
                        $this->_targetarea,
                        $this->_targetframe,
                        $this->_targetaction . $custom
                    )),
                    'right_bottom',
                    $sess->url(sprintf(
                        'main.php?area=%s&frame=%d&action=%s',
                        $this->_targetarea2,
                        $this->_targetframe2,
                        $this->_targetaction2 . $custom
                    ))
                );
            default:
                return '';
        }
    }

    /**
     * Sets an anchor, works only for the link types Link and cLink.
     *
     * @param string $anchor Anchor name
     */
    public function setAnchor($anchor): self
    {
        $this->_anchor = $anchor;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        $this->updateAttribute('href', $this->getHref());

        if ($this->_image != '') {
            $image = new cHTMLImage($this->_image);
            $this->setContent($image);
        }

        return parent::toHtml();
    }

}
