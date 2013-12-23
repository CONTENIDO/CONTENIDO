<?php
/**
 * This file contains the base class cHTML for all HTML classes.
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
 * Base class for all CONTENIDO HTML classes
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTML {

    /**
     * Id attribute counter, used to generate unique values for id-attributes
     *
     * @var int
     */
    protected static $_idCounter = 0;

    /**
     * Flag to generate XHTML valid elements
     *
     * @var bool
     */
    protected static $_generateXHTML;

    /**
     * Storage of the open SGML tag template
     *
     * @var string
     */
    protected $_skeletonOpen = '<%s%s>';

    /**
     * Storage of a single SGML tag template
     *
     * @var string
     */
    protected $_skeletonSingle;

    /**
     * Storage of the close SGML tag
     *
     * @var string
     */
    protected $_skeletonClose = '</%s>';

    /**
     * Defines which tag to use
     *
     * @var string
     */
    protected $_tag;

    /**
     * Defines the style definitions
     *
     * @var array
     */
    protected $_styleDefs = array();

    /**
     * Defines all scripts which are required by the current element
     *
     * @var array
     */
    protected $_requiredScripts = array();

    /**
     * Defines if the current tag is a contentless tag
     *
     * @var bool
     */
    protected $_contentlessTag = true;

    /**
     * Defines which JS events contain which scripts
     *
     * @var array
     */
    protected $_eventDefinitions = array();

    /**
     * Style definitions
     *
     * @var array
     */
    protected $_styleDefinitions = array();

    /**
     * Attributes
     *
     * @var array
     */
    protected $_attributes;

    /**
     * The content itself
     *
     * @var string
     */
    protected $_content;

    /**
     * Constructor Function.
     *
     * @param array $attributes Associative array of table tag attributes
     */
    public function __construct(array $attributes = NULL) {
        if (!is_null($attributes)) {
            $this->setAttributes($attributes);
        }

        if (self::$_generateXHTML === NULL) {
            if (getEffectiveSetting('generator', 'xhtml', 'false') == 'true') {
                self::$_generateXHTML = true;
            } else {
                self::$_generateXHTML = false;
            }
        }

        if (self::$_generateXHTML === true) {
            $this->_skeletonSingle = '<%s%s />';
        } else {
            $this->_skeletonSingle = '<%s%s>';
        }

        if (isset($attributes['id']) === false) {
            $this->advanceID();
        }
    }

    /**
     * Setter for static $_generateXHTML property
     *
     * @param bool $value
     */
    public static function setGenerateXHTML($value) {
        self::$_generateXHTML = (bool) $value;
    }

    /**
     * Advances to the next ID available in the system.
     *
     * This function is useful if you need to use HTML elements
     * in a loop, but don't want to re-create new objects each time.
     *
     * @return cHTML $this
     */
    public function advanceID() {
        self::$_idCounter++;
        return $this->updateAttribute('id', 'm' . self::$_idCounter);
    }

    /**
     * Returns the current ID
     *
     * @return string current ID
     */
    public function getID() {
        return $this->getAttribute('id');
    }

    /**
     * Sets the HTML tag to $tag
     *
     * @param string $tag The new tag
     * @return cHTML $this
     */
    public function setTag($tag) {
        $this->_tag = $tag;

        return $this;
    }

    /**
     * Sets the alt and title attributes
     *
     * Sets the "alt" and "title" tags. Usually, "alt" is used
     * for accessibility and "title" for mouse overs.
     *
     * The title attribute is not overwritten if there is already a set value for it.
     *
     * To set the text for all browsers for mouse over, set "alt"
     * and "title". IE behaves incorrectly and shows "alt" on
     * mouse over. Mozilla browsers only show "title" as mouse over.
     *
     * @param string $alt Text to set as the "alt" and "title" attribute
     * @param bool $setTitle Whether title attribute should be set, too (optional, default: true)
     * @return cHTML $this
     */
    public function setAlt($alt, $setTitle = true) {
        $attributes = array('alt' => $alt, 'title' => $alt);

        if ($setTitle === false || isset($this->_attributes['title']) === true) {
            unset($attributes['title']);
        }

        return $this->updateAttributes($attributes);
    }

    /**
     * Sets the ID class
     *
     * @param string $id Text to set as the "id"
     * @return cHTML $this
     */
    public function setID($id) {
        if ($id != "") {
            return $this->updateAttribute('id', $id);
        } else {
            return $this;
        }

    }

    /**
     * Sets the CSS class
     *
     * @param string $class Text to set as the "class" attribute
     * @return cHTML $this
     */
    public function setClass($class) {
        if ($class != "") {
            return $this->updateAttribute('class', $class);
        } else {
            return $this;
        }
    }

    /**
     * Sets the CSS style
     *
     * @param string $style Text to set as the "style" attribute
     * @return cHTML $this
     */
    public function setStyle($style) {
        return $this->updateAttribute('style', $style);
    }

    /**
     * Adds an "on???" javascript event handler
     *
     * example:
     * $item->setEvent('change', 'document.forms[0].submit');
     *
     * @param string $event Type of the event, e. g. "change" for "onchange"
     * @param string $action Function or action to call (JavaScript Code)
     * @return cHTML $this
     */
    public function setEvent($event, $action) {
        if (substr($event, 0, 2) !== 'on' && $event != 'disabled') {
            return $this->updateAttribute('on' . $event, conHtmlSpecialChars($action));
        } else {
            return $this->updateAttribute($event, conHtmlSpecialChars($action));
        }
    }

    /**
     * Removes an event handler
     *
     * example:
     * $item->unsetEvent('change');
     *
     * @param string $event Type of the event
     * @return cHTML $this
     */
    public function unsetEvent($event) {
        if (substr($event, 0, 2) !== 'on') {
            return $this->removeAttribute('on' . $event);
        } else {
            return $this->removeAttribute($event);
        }
    }

    /**
     * Fills the open SGML tag skeleton
     *
     * fillSkeleton fills the SGML opener tag with the
     * specified attributes. Attributes need to be passed
     * in the stringyfied variant.
     *
     * @param string $attributes Attributes to set
     * @return string filled SGML opener skeleton
     */
    public function fillSkeleton($attributes) {
        if ($this->_contentlessTag == true) {
            return sprintf($this->_skeletonSingle, $this->_tag, $attributes);
        } else {
            return sprintf($this->_skeletonOpen, $this->_tag, $attributes);
        }
    }

    /**
     * Fills the close skeleton
     *
     * @return string filled SGML closer skeleton
     */
    public function fillCloseSkeleton() {
        return sprintf($this->_skeletonClose, $this->_tag);
    }

    /**
     * Appends the given style definition to the HTML element.
     * Example usage:
     * $element->appendStyleDefinition('margin', '5px');
     *
     * @param string $property the property name, e.g. 'margin'
     * @param string $value the value of the property, e.g. '5px'
     * @return cHTML $this
     */
    public function appendStyleDefinition($property, $value) {
        if (substr($value, -1) === ';') {
            $value = substr($value, 0, strlen($value) - 1);
        }
        $this->_styleDefinitions[$property] = $value;

        return $this;
    }

    /**
     * Appends the given style definitions to the HTML element.
     * Example usage:
     * $element->appendStyleDefinitions(array(
     * 'margin' => '5px',
     * 'padding' => '0'
     * ));
     *
     * @param string $styles the styles to append
     * @return cHTML $this
     */
    public function appendStyleDefinitions(array $styles) {
        foreach ($styles as $property => $value) {
            $this->appendStyleDefinition($property, $value);
        }

        return $this;
    }

    /**
     * Adds a required script to the current element.
     * Anyway, scripts are not included twice.
     *
     * @param string $script the script to include
     * @return cHTML $this
     */
    public function addRequiredScript($script) {
        if (!is_array($this->_requiredScripts)) {
            $this->_requiredScripts = array();
        }

        $this->_requiredScripts[] = $script;
        $this->_requiredScripts = array_unique($this->_requiredScripts);

        return $this;
    }

    /**
     * Sets the content of the object
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTML $this
     */
    protected function _setContent($content) {
        $this->_contentlessTag = false;
        if (is_array($content)) {
            // reset content
            $this->_content = '';
            // content is an array, so iterate over it and append the elements
            foreach ($content as $item) {
                if (is_object($item)) {
                    if (method_exists($item, 'render')) {
                        $this->_content .= $item->render();
                    }
                    if (count($item->_requiredScripts) > 0) {
                        $this->_requiredScripts = array_merge($this->_requiredScripts, $item->_requiredScripts);
                    }
                } else {
                    $this->_content .= $item;
                }
            }
        } else {
            // content is an object or a string, so just set the rendered
            // content
            if (is_object($content)) {
                if (method_exists($content, 'render')) {
                    $this->_content = $content->render();
                }
                if (count($content->_requiredScripts) > 0) {
                    $this->_requiredScripts = array_merge($this->_requiredScripts, $content->_requiredScripts);
                }
            } else {
                $this->_content = $content;
            }
        }

        return $this;
    }

    /**
     * Adds the given content to the already existing content of this object.
     *
     * @param string|object|array $content String with the content or an object
     *        to
     *        render or an array of strings/objects.
     * @return cHTML $this
     */
    protected function _appendContent($content) {
        if (!is_string($this->_content)) {
            $this->_content = '';
        }
        $this->_contentlessTag = false;
        if (is_array($content)) {
            // content is an array, so iterate over it and append the elements
            foreach ($content as $item) {
                if (is_object($item)) {
                    if (method_exists($item, 'render')) {
                        $this->_content .= $item->render();
                    }
                } else {
                    $this->_content .= $item;
                }
            }
        } else {
            // content is an object or a string, so just append the rendered
            // content
            if (is_object($content)) {
                if (method_exists($content, 'render')) {
                    $this->_content .= $content->render();
                }
            } else {
                $this->_content .= $content;
            }
        }

        return $this;
    }

    /**
     * Attaches the code for an event
     *
     * Example to attach an onClick handler:
     * attachEventDefinition('foo', 'onClick', 'alert("foo");');
     *
     * @param string $name Defines the name of the event
     * @param string $event Defines the event (e.g. onClick)
     * @param string $code Defines the code
     * @return cHTML $this
     */
    public function attachEventDefinition($name, $event, $code) {
        $this->_eventDefinitions[strtolower($event)][$name] = $code;

        return $this;
    }

    /**
     * Sets a specific attribute
     *
     * @param string $attributeName Name of the attribute
     * @param string $value Value of the attribute
     * @return cHTML $this
     */
    public function setAttribute($attributeName, $value = NULL) {
        $attributeName = strtolower($attributeName);
        if (is_null($value)) {
            $value = $attributeName;
        }
        $this->_attributes[$attributeName] = $value;

        return $this;
    }

    /**
     * Sets the HTML attributes
     *
     * @param array $attributes Associative array with attributes
     * @return cHTML $this
     */
    public function setAttributes(array $attributes) {
        $this->_attributes = $this->_parseAttributes($attributes);

        return $this;
    }

    /**
     * Returns a valid atrributes array.
     *
     * @param array $attributes Associative array with attributes
     * @return array the parsed attributes
     */
    protected function _parseAttributes(array $attributes) {
        $return = array();
        foreach ($attributes as $key => $value) {
            if (is_int($key)) {
                $key = $value = strtolower($value);
            } else {
                $key = strtolower($key);
            }

            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * Removes an attribute
     *
     * @param string $attributeName Attribute name
     * @return cHTML $this
     */
    public function removeAttribute($attributeName) {
        if (isset($this->_attributes[$attributeName])) {
            unset($this->_attributes[$attributeName]);
        }

        return $this;
    }

    /**
     * Returns the value of the given attribute.
     *
     * @param string $attributeName Attribute name
     * @return string NULL value or NULL if the attribute does not
     *         exist
     */
    public function getAttribute($attributeName) {
        $attributeName = strtolower($attributeName);

        if (isset($this->_attributes[$attributeName])) {
            return $this->_attributes[$attributeName];
        }

        return NULL;
    }

    /**
     * Updates the passed attribute without changing the other existing
     * attributes
     *
     * @param string $name the name of the attribute
     * @param string $value the value of the attribute with the given name
     * @return cHTML $this
     */
    public function updateAttribute($name, $value) {
        return $this->updateAttributes(array(
                    $name => $value
        ));
    }

    /**
     * Updates the passed attributes without changing the other existing
     * attributes
     *
     * @param array $attributes Associative array with attributes
     * @return cHTML $this
     */
    public function updateAttributes(array $attributes) {
        $attributes = $this->_parseAttributes($attributes);

        foreach ($attributes as $key => $value) {
            if (!is_null($value)) {
                $this->_attributes[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Returns an HTML formatted attribute string
     *
     * @param array $attributes Associative array with attributes
     * @return string Attribute string in HTML format
     */
    protected function _getAttrString(array $attributes) {
        $attrString = '';

        if (!is_array($attributes)) {
            return '';
        }

        foreach ($attributes as $key => $value) {
            $attrString .= ' ' . $key . '="' . $value . '"';
        }

        return $attrString;
    }

    /**
     * Returns the assoc array(default) or string of attributes
     *
     * @param bool $returnAsString Whether to return the attributes as string
     * @return array string
     */
    public function getAttributes($returnAsString = false) {
        if ($returnAsString) {
            return $this->_getAttrString($this->_attributes);
        } else {
            return $this->_attributes;
        }
    }

    /**
     * Generates the markup of the element
     *
     * @return string Generated markup
     */
    public function toHTML() {
        // Fill style definition
        $style = $this->getAttribute('style');

        // If the style doesn't end with a semicolon, append one
        if (is_string($style)) {
            $style = trim($style);
            if (substr($style, -1) !== ';') {
                $style .= ';';
            }
        }

        // append the defined styles
        foreach ($this->_styleDefinitions as $property => $value) {
            $style .= "$property: $value;";
        }

        if ($style != '') {
            $this->setStyle($style);
        }

        foreach ($this->_eventDefinitions as $eventName => $entry) {
            $fullCode = array();
            foreach ($entry as $code) {
                $fullCode[] = $code;
            }
            $this->setAttribute($eventName, $this->getAttribute($eventName) . implode(' ', $fullCode));
        }

        $attributes = $this->getAttributes(true);
        if (!empty($this->_content) || $this->_contentlessTag === false) {
            return $this->fillSkeleton($attributes) . $this->_content . $this->fillCloseSkeleton();
        } else {
            // This is a single style tag
            return $this->fillSkeleton($attributes);
        }
    }

    /**
     * Alias for toHtml
     *
     * @return string Generated markup
     */
    public function render() {
        return $this->toHtml();
    }

    /**
     * Direct call of object as string will return it's generated markup.
     *
     * @return string Generated markup
     */
    public function __toString() {
        return $this->render();
    }

    /**
     * Outputs the generated markup
     */
    public function display() {
        echo $this->render();
    }

}
