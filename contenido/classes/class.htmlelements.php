<?php
/**
 * This file contains the cHTML classes.
 *
 * @package Core
 * @subpackage Frontend
 * @version SVN Revision $Rev:$
 *
 * @author Timo A. Hummel, Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Base class for all CONTENIDO HTML classes
 *
 * @package Core
 * @subpackage Frontend
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
     * @return void
     */
    public function __construct(array $attributes = null) {
        if (!is_null($attributes)) {
            $this->setAttributes($attributes);
        }

        if (self::$_generateXHTML === null) {
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

        $this->advanceID();
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTML() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Contentless tag setter
     *
     * @param bool $contentlessTag
     * @return cHTML $this
     * @deprecated [2012-07-20] use $this->_contentLess directly or just use
     *             setContent() - this function sets the contentLess attribute
     *             to false
     */
    public function setContentlessTag($contentlessTag = true) {
        cDeprecated('Use $this->_contentLess directly or just use setContent() - this function sets the contentLess attribute to false.');
        $this->_contentlessTag = $contentlessTag;

        return $this;
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
     * To set the text for all browsers for mouse over, set "alt"
     * and "title". IE behaves incorrectly and shows "alt" on
     * mouse over. Mozilla browsers only show "title" as mouse over.
     *
     * @param string $alt Text to set as the "alt" and "title" attribute
     * @return cHTML $this
     */
    public function setAlt($alt) {
        return $this->updateAttributes(array(
            'alt' => $alt,
            'title' => $alt
        ));
    }

    /**
     * Sets the ID class
     *
     * @param string $id Text to set as the "id"
     * @return cHTML $this
     */
    public function setID($id) {
        return $this->updateAttribute('id', $id);
    }

    /**
     * Sets the CSS class
     *
     * @param string $class Text to set as the "class" attribute
     * @return cHTML $this
     */
    public function setClass($class) {
        return $this->updateAttribute('class', $class);
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
     * $item->setEvent('change','document.forms[0].submit');
     *
     * @param string $event Type of the event, e. g. "change" for "onchange"
     * @param string $action Function or action to call (JavaScript Code)
     * @return cHTML $this
     */
    public function setEvent($event, $action) {
        if (substr($event, 0, 2) !== 'on') {
            return $this->updateAttribute('on' . $event, htmlspecialchars($action));
        } else {
            return $this->updateAttribute($event, htmlspecialchars($action));
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
     *
     * @param string $entity Entity to define
     * @param string $definition Definition for the given entity
     * @return cHTML $this
     * @deprecated [2012-07-23] name change, use appendStyleDefinition
     */
    public function setStyleDefinition($entity, $definition) {
        cDeprecated('Use appendStyleDefinition instead');
        return $this->appendStyleDefinition($entity, $definition);
    }

    /**
     * Attaches a style definition.
     *
     * This function is not restricted to a single style, e.g.
     * you can set multiple style definitions as-is to the handler.
     *
     * $example->attachStyle('myIdentifier',
     * 'border: 1px solid black; white-space: nowrap');
     * $example->attachStyle('myIdentifier2',
     * 'padding: 0px');
     *
     * Results in:
     *
     * style="border: 1px solid black; white-space: nowrap; padding: 0px;"
     *
     * @param string $name Name for a style definition
     * @param string $definition Definition for the given entity
     * @return cHTML $this
     * @deprecated [2012-07-23] Set the definition via appendStyleDefinition and
     *             do not use a name any more
     */
    public function attachStyleDefinition($name, $definition) {
        cDeprecated('Set the definition via appendStyleDefinition and do not use a name any more');
        // tokenize styles and add them via appendStyleDefinition
        $styles = explode(';', $definition);
        foreach ($styles as $style) {
            $propVal = explode(':', $style);
            if (count($propVal) === 2) {
                $this->appendStyleDefinition(trim($propVal[0]), trim($propVal[1]));
            }
        }

        return $this;
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
     * @return string null value or null if the attribute does not
     *         exist
     */
    public function getAttribute($attributeName) {
        $attributeName = strtolower($attributeName);

        if (isset($this->_attributes[$attributeName])) {
            return $this->_attributes[$attributeName];
        }

        return null;
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

/**
 * cHTMLFormElement class represents a form element.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLFormElement extends cHTML {

    /**
     * Constructor.
     * This is a generic form element, where
     * specific elements should be inherited from this class.
     *
     * @param string $name Name of the element
     * @param string $id ID of the element
     * @param string $disabled Item disabled flag (non-empty to set disabled)
     * @param string $tabindex Tab index for form elements
     * @param string $accesskey Key to access the field
     * @param string $class CSS class name to set
     * @return void
     */
    public function __construct($name = '', $id = '', $disabled = '', $tabindex = '', $accesskey = '', $class = 'text_medium', $class = '') {
        parent::__construct();

        $this->updateAttribute('name', $name);

        if (is_string($id) && !empty($id)) {
            $this->updateAttribute('id', $id);
        }

        $this->_tag = 'input';

        $this->setClass($class);
        $this->setDisabled($disabled);
        $this->setTabindex($tabindex);
        $this->setAccessKey($accesskey);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLFormElement($name = '', $id = '', $disabled = '', $tabindex = '', $accesskey = '', $class = 'text_medium') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $id, $disabled, $tabindex, $accesskey, $class);
    }

    /**
     * Sets the "disabled" attribute of an element.
     * User Agents
     * usually are showing the element as "greyed-out".
     *
     * Example:
     * $obj->setDisabled('disabled');
     * $obj->setDisabled('');
     *
     * The first example sets the disabled flag, the second one
     * removes the disabled flag.
     *
     * @param string $disabled Sets the disabled-flag if non-empty
     * @return cHTMLFormElement $this
     */
    public function setDisabled($disabled) {
        if (empty($disabled)) {
            $this->removeAttribute('disabled');
        } else {
            $this->updateAttribute('disabled', 'disabled');
        }

        return $this;
    }

    /**
     * Sets the tab index for this element.
     * The tab
     * index needs to be numeric, bigger than 0 and smaller than 32767.
     *
     * @param int $tabindex Desired tab index
     * @return cHTMLFormElement $this
     */
    public function setTabindex($tabindex) {
        if (is_numeric($tabindex) && $tabindex >= 0 && $tabindex <= 32767) {
            $this->updateAttribute('tabindex', $tabindex);
        }

        return $this;
    }

    /**
     * Sets the access key for this element.
     *
     * @param string $accesskey The length of the access key. May be A-Z and
     *        0-9.
     * @return cHTMLFormElement $this
     */
    public function setAccessKey($accesskey) {
        if ((strlen($accesskey) == 1) && isAlphanumeric($accesskey)) {
            $this->updateAttribute('accesskey', $accesskey);
        } else {
            $this->removeAttribute('accesskey');
        }

        return $this;
    }

}

/**
 * cHTMLHiddenField class represents a hidden form field.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLHiddenField extends cHTMLFormElement {

    /**
     * Constructor.
     * Creates an HTML hidden field.
     *
     * @param string $name Name of the element
     * @param string $value Title of the button
     * @param string $id ID of the element
     * @return void
     */
    public function __construct($name, $value = '', $id = '') {
        parent::__construct($name, $id);
        $this->_contentlessTag = true;
        $this->updateAttribute('type', 'hidden');
        $this->_tag = 'input';

        $this->setValue($value);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLHiddenField($name, $value = '', $id = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $value, $id);
    }

    /**
     * Sets the value for the field
     *
     * @param string $value Value of the field
     * @return cHTMLHiddenField $this
     */
    public function setValue($value) {
        $this->updateAttribute('value', $value);

        return $this;
    }

}

/**
 * cHTMLButton class represents a button.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLButton extends cHTMLFormElement {

    /**
     * Constructor.
     * Creates an HTML button.
     *
     * Creates a submit button by default, can be changed
     * using setMode.
     *
     * @param string $name Name of the element
     * @param string $title Title of the button
     * @param string $id ID of the element
     * @param string $disabled Item disabled flag (non-empty to set disabled)
     * @param string $tabindex Tab index for form elements
     * @param string $accesskey Key to access the field
     * @param string $mode Mode of button
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name, $title = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '', $mode = 'submit', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'input';
        $this->_contentlessTag = true;
        $this->setTitle($title);
        $this->setMode($mode);
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLButton($name, $title = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '', $mode = 'submit') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $title, $id, $disabled, $tabindex, $accesskey, $mode);
    }

    /**
     * Sets the title (caption) for the button
     *
     * @param string $title The title to set
     * @return cHTMLButton $this
     */
    public function setTitle($title) {
        $this->updateAttribute('value', $title);

        return $this;
    }

    /**
     * Sets the mode (submit or reset) for the button
     *
     * @param string $mode Either 'submit', 'reset' or 'image'.
     * @return cHTMLButton $this
     */
    public function setMode($mode) {
        $modes = array(
            'submit',
            'reset',
            'image',
            'button'
        );
        if (in_array($mode, $modes)) {
            $this->updateAttribute('type', $mode);
        }

        return $this;
    }

    /**
     * Set the image src if mode type is "image"
     *
     * @param string $mode Image path.
     * @return cHTMLButton $this
     */
    public function setImageSource($src) {
        return $this->updateAttribute('src', $src);
    }

}

/**
 * cHTMLTextbox class represents a textbox.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTextbox extends cHTMLFormElement {

    /**
     * Constructor.
     * Creates an HTML text box.
     *
     * If no additional parameters are specified, the
     * default width is 20 units.
     *
     * @param string $name Name of the element
     * @param string $initvalue Initial value of the box
     * @param int $width width of the text box
     * @param int $maxlength maximum input length of the box
     * @param string $id ID of the element
     * @param string $disabled Item disabled flag (non-empty to set disabled)
     * @param string $tabindex Tab index for form elements
     * @param string $accesskey Key to access the field
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name, $initvalue = '', $width = '', $maxlength = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);

        $this->_tag = 'input';
        $this->_contentlessTag = true;
        $this->setValue($initvalue);

        $this->setWidth($width);
        $this->setMaxLength($maxlength);

        $this->updateAttribute('type', 'text');
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTextbox($name, $initvalue = '', $width = '', $maxlength = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $initvalue, $width, $maxlength, $id, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the width of the text box.
     *
     * @param int $width width of the text box
     * @return cHTMLTextbox $this
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 50;
        }

        return $this->updateAttribute('size', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $maxlen maximum input length
     * @return cHTMLTextbox $this
     */
    public function setMaxLength($maxlen) {
        $maxlen = intval($maxlen);

        if ($maxlen <= 0) {
            return $this->removeAttribute('maxlength');
        } else {
            return $this->updateAttribute('maxlength', $maxlen);
        }
    }

    /**
     * Sets the initial value of the text box.
     *
     * @param string $value Initial value
     * @return cHTMLTextbox $this
     */
    public function setValue($value) {
        return $this->updateAttribute('value', $value);
    }

}

/**
 * cHTMLPasswordbox class represents a password form field.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLPasswordbox extends cHTMLFormElement {

    /**
     * Constructor.
     * Creates an HTML password box.
     *
     * If no additional parameters are specified, the
     * default width is 20 units.
     *
     * @param string $name Name of the element
     * @param string $initvalue Initial value of the box
     * @param int $width width of the text box
     * @param int $maxlength maximum input length of the box
     * @param string $id ID of the element
     * @param string $disabled Item disabled flag (non-empty to set disabled)
     * @param string $tabindex Tab index for form elements
     * @param string $accesskey Key to access the field
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name, $initvalue = '', $width = '', $maxlength = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'input';
        $this->setValue($initvalue);

        $this->setWidth($width);
        $this->setMaxLength($maxlength);

        $this->updateAttribute('type', 'password');
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLPasswordbox($name, $initvalue = '', $width = '', $maxlength = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $initvalue, $width, $maxlength, $id, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the width of the text box.
     *
     * @param int $width width of the text box
     * @return cHTMLPasswordbox $this
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 20;
        }

        return $this->updateAttribute('size', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $maxlen maximum input length
     * @return cHTMLPasswordbox $this
     */
    public function setMaxLength($maxlen) {
        $maxlen = intval($maxlen);

        if ($maxlen <= 0) {
            return $this->removeAttribute('maxlength');
        } else {
            return $this->updateAttribute('maxlength', $maxlen);
        }
    }

    /**
     * Sets the initial value of the text box.
     *
     * @param string $value Initial value
     * @return cHTMLPasswordbox $this
     */
    public function setValue($value) {
        return $this->updateAttribute('value', $value);
    }

}

/**
 * cHTMLTextarea class represents a textarea.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTextarea extends cHTMLFormElement {

    protected $_value;

    /**
     * Constructor.
     * Creates an HTML text area.
     *
     * If no additional parameters are specified, the
     * default width is 60 chars, and the height is 5 chars.
     *
     * @param string $name Name of the element
     * @param string $initvalue Initial value of the textarea
     * @param int $width width of the textarea
     * @param int $height height of the textarea
     * @param string $id ID of the element
     * @param string $disabled Item disabled flag (non-empty to set disabled)
     * @param string $tabindex Tab index for form elements
     * @param string $accesskey Key to access the field
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name, $initvalue = '', $width = '', $height = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'textarea';
        $this->setValue($initvalue);
        $this->_contentlessTag = false;
        $this->setWidth($width);
        $this->setHeight($height);
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTextarea($name, $initvalue = '', $width = '', $height = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $initvalue, $width, $height, $id, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the width of the text box.
     *
     * @param int $width width of the text box
     * @return cHTMLTextarea $this
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 50;
        }

        return $this->updateAttribute('cols', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $maxlen maximum input length
     * @return cHTMLTextarea $this
     */
    public function setHeight($height) {
        $height = intval($height);

        if ($height <= 0) {
            $height = 5;
        }

        return $this->updateAttribute('rows', $height);
    }

    /**
     * Sets the initial value of the text box.
     *
     * @param string $value Initial value
     * @return cHTMLTextarea $this
     */
    public function setValue($value) {
        $this->_value = $value;

        return $this;
    }

    /**
     * Renders the textarea
     *
     * @return string Rendered HTML
     */
    public function toHtml() {
        $this->_setContent($this->_value);

        return parent::toHTML();
    }

}

/**
 * cHTMLLabel class represents a form label.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLLabel extends cHTML {

    /**
     * The text to display on the label
     *
     * @var string
     */
    public $text;

    /**
     * Constructor.
     * Creates an HTML label which can be linked
     * to any form element (specified by their ID).
     *
     * A label can be used to link to elements. This is very useful
     * since if a user clicks a label, the linked form element receives
     * the focus (if supported by the user agent).
     *
     * @param string $text Name of the element
     * @param string $for ID of the form element to link to.
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($text, $for, $class = '') {
        parent::__construct();
        $this->_tag = 'label';
        $this->_contentlessTag = false;
        $this->updateAttribute('for', $for);
        $this->text = $text;
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLLabel($text, $for) {
        cDeprecated('Use __construct() instead');
        $this->__construct($text, $for);
    }

    /**
     * Renders the label
     *
     * @return string Rendered HTML
     */
    public function toHtml() {
        $this->_setContent($this->text);

        return parent::toHTML();
    }

}

/**
 * cHTMLSelectElement class represents a select element.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLSelectElement extends cHTMLFormElement {

    /**
     * All cHTMLOptionElements
     *
     * @var array
     */
    protected $_options;

    /**
     * Constructor.
     * Creates an HTML select field (aka "DropDown").
     *
     * @param string $name Name of the element
     * @param int $width Width of the select element (note: not used)
     * @param string $id ID of the element
     * @param bool $disabled Item disabled flag (non-empty to set disabled)
     * @param string $tabindex Tab index for form elements
     * @param string $accesskey Key to access the field
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name, $width = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'select';
        $this->_contentlessTag = false;
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLSelectElement($name, $width = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $width, $id, $disabled, $tabindex, $accesskey);
    }

    /**
     * Automatically creates and fills cHTMLOptionElements
     *
     * Array format:
     * $stuff = array(
     * array('value', 'title'),
     * array('value', 'title')
     * );
     *
     * or regular key => value arrays:
     * $stuff = array(
     * 'value' => 'title',
     * 'value' => 'title'
     * );
     *
     * @param array $stuff Array with all items
     * @return cHTMLSelectElement $this
     */
    public function autoFill(array $stuff) {
        if (is_array($stuff)) {
            foreach ($stuff as $key => $row) {
                if (is_array($row)) {
                    $option = new cHTMLOptionElement($row[1], $row[0]);
                    $this->addOptionElement($row[0], $option);
                } else {
                    $option = new cHTMLOptionElement($row, $key);
                    $this->addOptionElement($key, $option);
                }
            }
        }

        return $this;
    }

    /**
     * Adds an cHTMLOptionElement to the number of choices at the specified
     * position.
     *
     * @param string $index Index of the element
     * @param cHTMLOptionElement $element Filled cHTMLOptionElement to add
     * @return cHTMLSelectElement $this
     */
    public function addOptionElement($index, cHTMLOptionElement $element) {
        $this->_options[$index] = $element;

        return $this;
    }

    /**
     * Appends a cHTMLOptionElement to the number of choices.
     *
     * @param cHTMLOptionElement $element Filled cHTMLOptionElement to add
     * @return cHTMLSelectElement $this
     */
    public function appendOptionElement(cHTMLOptionElement $element) {
        $this->_options[] = $element;

        return $this;
    }

    /**
     * Defines that this select element is a multiselect element.
     *
     * @return cHTMLSelectElement $this
     */
    public function setMultiselect() {
        return $this->updateAttribute('multiple', 'multiple');
    }

    /**
     * Defines the size of this select element.
     *
     * @return cHTMLSelectElement $this
     */
    public function setSize($size) {
        return $this->updateAttribute('size', $size);
    }

    /**
     * Sets a specific cHTMLOptionElement to the selected state.
     *
     * @param string $lvalue Specifies the "value" of the cHTMLOptionElement to
     *        set
     * @return cHTMLSelectElement $this
     */
    public function setDefault($lvalue) {
        if (is_array($this->_options) && is_array($lvalue)) {
            foreach ($this->_options as $key => $value) {
                if (in_array($value->getAttribute('value'), $lvalue)) {
                    $value->setSelected(true);
                    $this->_options[$key] = $value;
                } else {
                    $value->setSelected(false);
                    $this->_options[$key] = $value;
                }
            }
        } else {
            foreach ($this->_options as $key => $value) {
                if (strcmp($value->getAttribute('value'), $lvalue) == 0) {
                    $value->setSelected(true);
                    $this->_options[$key] = $value;
                } else {
                    $value->setSelected(false);
                    $this->_options[$key] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Search for the selected elements
     *
     * @return string bool "lvalue" or false
     */
    public function getDefault() {
        if (is_array($this->_options)) {
            foreach ($this->_options as $key => $value) {
                if ($value->isSelected()) {
                    return $key;
                }
            }
        }

        return false;
    }

    /**
     * Sets specified elements as selected (and all others as unselected)
     *
     * @param array $elements Array with "values" of the cHTMLOptionElement to
     *        set
     * @return cHTMLSelectElement $this
     */
    public function setSelected(array $elements) {
        if (is_array($this->_options)) {
            foreach ($this->_options as $key => $option) {
                if (in_array($option->getAttribute('value'), $elements)) {
                    $option->setSelected(true);
                    $this->_options[$key] = $option;
                } else {
                    $option->setSelected(false);
                    $this->_options[$key] = $option;
                }
            }
        }

        return $this;
    }

    /**
     * Renders the select box
     *
     * @return string Rendered HTML
     */
    public function toHtml() {
        $this->_setContent($this->_options);

        return parent::toHTML();
    }

}

/**
 * cHTMLOptionElement class represents a select option element.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLOptionElement extends cHTMLFormElement {

    /**
     * Title to display
     *
     * @var string
     */
    protected $_title;

    /**
     * Constructor.
     * Creates an HTML option element.
     *
     * @param string $title Displayed title of the element
     * @param string $value Value of the option
     * @param bool $selected If true, element is selected
     * @param bool $disabled If true, element is disabled
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($title, $value, $selected = false, $disabled = false, $class = '') {
        cHTML::__construct();
        $this->_tag = 'option';
        $this->_title = $title;

        $this->updateAttribute('value', $value);
        $this->_contentlessTag = false;

        $this->setSelected($selected);
        $this->setDisabled($disabled);
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLOptionElement($title, $value, $selected = false, $disabled = false) {
        cDeprecated('Use __construct() instead');
        $this->__construct($title, $value, $selected, $disabled);
    }

    /**
     * Sets the selected flag
     *
     * @param bool $selected If true, adds the "selected" attribute
     * @return cHTMLOptionElement $this
     */
    public function setSelected($selected) {
        if ($selected == true) {
            return $this->updateAttribute('selected', 'selected');
        } else {
            return $this->removeAttribute('selected');
        }
    }

    /**
     * Checks whether this option element is selected.
     *
     * @return bool whether this option element is selected
     */
    public function isSelected() {
        return $this->getAttribute('selected') === 'selected';
    }

    /**
     * Sets the disabled flag
     *
     * @param bool $disabled If true, adds the "disabled" attribute
     * @return cHTMLOptionElement $this
     */
    public function setDisabled($disabled) {
        if ($disabled == true) {
            return $this->updateAttribute('disabled', 'disabled');
        } else {
            return $this->removeAttribute('disabled');
        }
    }

    /**
     * Renders the option element.
     * Note:
     * the cHTMLSelectElement renders the options by itself.
     *
     * @return string Rendered HTML
     */
    public function toHtml() {
        $this->_setContent($this->_title);

        return parent::toHTML();
    }

}

/**
 * cHTMLRadiobutton class represents a radio button.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLRadiobutton extends cHTMLFormElement {

    /**
     * Values for the check box
     *
     * @var string
     */
    protected $_value;

    /**
     * The text for the corresponding label
     *
     * @var string
     */
    protected $_labelText;

    /**
     * Constructor.
     * Creates an HTML radio button element.
     *
     * @param string $name Name of the element
     * @param string $value Value of the radio button
     * @param string $id ID of the element
     * @param bool $checked Is element checked?
     * @param string $disabled Item disabled flag (non-empty to set disabled)
     * @param string $tabindex Tab index for form elements
     * @param string $accesskey Key to access the field
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name, $value, $id = '', $checked = false, $disabled = false, $tabindex = null, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'input';
        $this->_value = $value;
        $this->_contentlessTag = true;

        $this->setChecked($checked);
        $this->updateAttribute('type', 'radio');
        $this->updateAttribute('value', $value);
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLRadiobutton($name, $value, $id = '', $checked = false, $disabled = false, $tabindex = null, $accesskey = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $value, $id, $checked, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the checked flag.
     *
     * @param bool $checked If true, the "checked" attribute will be assigned.
     * @return cHTMLRadiobutton $this
     */
    public function setChecked($checked) {
        if ($checked == true) {
            return $this->updateAttribute('checked', 'checked');
        } else {
            return $this->removeAttribute('checked');
        }
    }

    /**
     * Sets a custom label text
     *
     * @param string $text Text to display
     * @return cHTMLRadiobutton $this
     */
    public function setLabelText($text) {
        $this->_labelText = $text;

        return $this;
    }

    /**
     * Renders the option element.
     * Note:
     *
     * If this element has an ID, the value (which equals the text displayed)
     * will be rendered as seperate HTML label, if not, it will be displayed
     * as regular text. Displaying the value can be turned off via the
     * parameter.
     *
     * @param bool $renderlabel If true, renders a label
     * @return string Rendered HTML
     */
    public function toHtml($renderLabel = true) {
        $attributes = $this->getAttributes(true);

        if ($renderLabel == false) {
            return $this->fillSkeleton($attributes);
        }

        $id = $this->getAttribute('id');

        $renderedLabel = '';

        if ($id != '') {
            $label = new cHTMLLabel($this->_value, $this->getAttribute('id'));

            if ($this->_labelText != '') {
                $label->text = $this->_labelText;
            }

            $renderedLabel = $label->toHtml();
        } else {
            $renderedLabel = $this->_value;
        }

        return $this->fillSkeleton($attributes) . $renderedLabel;
    }

}

/**
 * cHTMLCheckbox class represents a checkbox.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLCheckbox extends cHTMLFormElement {

    /**
     * Values for the check box
     *
     * @var string
     */
    protected $_value;

    /**
     * The text for the corresponding label
     *
     * @var string
     */
    protected $_labelText;

    /**
     * Constructor.
     * Creates an HTML checkbox element.
     *
     * @param string $name Name of the element
     * @param string $value Value of the radio button
     * @param string $id ID of the element
     * @param bool $checked Is element checked?
     * @param string $disabled Item disabled flag (non-empty to set disabled)
     * @param string $tabindex Tab index for form elements
     * @param string $accesskey Key to access the field
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name, $value, $id = '', $checked = false, $disabled = false, $tabindex = null, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'input';
        $this->_value = $value;
        $this->_contentlessTag = true;

        $this->setChecked($checked);
        $this->updateAttribute('type', 'checkbox');
        $this->updateAttribute('value', $value);
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLRadiobutton($name, $value, $id = '', $checked = false, $disabled = false, $tabindex = null, $accesskey = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $value, $id, $checked, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the checked flag.
     *
     * @param bool $checked If true, the "checked" attribute will be assigned.
     * @return cHTMLCheckbox $this
     */
    public function setChecked($checked) {
        if ($checked == true) {
            return $this->updateAttribute('checked', 'checked');
        } else {
            return $this->removeAttribute('checked');
        }
    }

    /**
     * Sets a custom label text
     *
     * @param string $text Text to display
     * @return cHTMLCheckbox $this
     */
    public function setLabelText($text) {
        $this->_labelText = $text;

        return $this;
    }

    /**
     * Renders the checkbox element.
     * Note:
     *
     * If this element has an ID, the value (which equals the text displayed)
     * will be rendered as seperate HTML label, if not, it will be displayed
     * as regular text. Displaying the value can be turned off via the
     * parameter.
     *
     * @param bool $renderlabel If true, renders a label
     * @return string Rendered HTML
     */
    public function toHtml($renderlabel = true) {
        $id = $this->getAttribute('id');

        $renderedLabel = '';

        if ($renderlabel == true) {
            if ($id != '') {
                $label = new cHTMLLabel($this->_value, $this->getAttribute('id'));

                $label->setClass($this->getAttribute('class'));

                if ($this->_labelText != '') {
                    $label->text = $this->_labelText;
                }

                $renderedLabel = $label->toHtml();
            } else {

                $renderedLabel = $this->_value;

                if ($this->_labelText != '') {
                    $label = new cHTMLLabel($this->_value, $this->getAttribute('id'));
                    $label->text = $this->_labelText;
                    $renderedLabel = $label->toHtml();
                }
            }

            $result = new cHTMLDiv(parent::toHTML() . $renderedLabel);
            $result->setClass('checkboxWrapper');
            return $result->render();
        } else {
            return parent::toHTML();
        }
    }

}

/**
 * cHTMLUpload class represents a file upload element.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLUpload extends cHTMLFormElement {

    /**
     * Constructor.
     * Creates an HTML upload box.
     *
     * If no additional parameters are specified, the
     * default width is 20 units.
     *
     * @param string $name Name of the element
     * @param string $initvalue Initial value of the box
     * @param int $width width of the text box
     * @param int $maxlength maximum input length of the box
     * @param string $id ID of the element
     * @param string $disabled Item disabled flag (non-empty to set disabled)
     * @param string $tabindex Tab index for form elements
     * @param string $accesskey Key to access the field
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name, $width = '', $maxlength = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'input';
        $this->_contentlessTag = true;

        $this->setWidth($width);
        $this->setMaxLength($maxlength);

        $this->updateAttribute('type', 'file');
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLUpload($name, $width = '', $maxlength = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $width, $maxlength, $id, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the width of the text box.
     *
     * @param int $width width of the text box
     * @return cHTMLUpload $this
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 20;
        }

        return $this->updateAttribute('size', $width);
    }

    /**
     * Sets the maximum input length of the text box.
     *
     * @param int $maxlen maximum input length
     * @return cHTMLUpload $this
     */
    public function setMaxLength($maxlen) {
        $maxlen = intval($maxlen);

        if ($maxlen <= 0) {
            return $this->removeAttribute('maxlength');
        } else {
            return $this->updateAttribute('maxlength', $maxlen);
        }
    }

}

/**
 * cHTMLDiv class represents a div element.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLDiv extends cHTML {

    /**
     * Constructor.
     * Creates an HTML Div element.
     *
     * @param mixed $content String or object with the contents
     * @param string $class the class of this element
     * @param string $id the ID of this element
     * @return void
     */
    public function __construct($content = '', $class = '', $id = '') {
        parent::__construct();
        $this->setContent($content);
        $this->_contentlessTag = false;
        $this->_tag = 'div';
        $this->setClass($class);
        $this->setID($id);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLDiv($content = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($content);
    }

    /**
     * Sets the div's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}

/**
 * cHTMLSpan class represents a span element.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLSpan extends cHTML {

    /**
     * Constructor.
     * Creates an HTML Span element.
     *
     * @param mixed $content String or object with the contents
     * @return void
     */
    public function __construct($content = '') {
        parent::__construct();
        $this->setContent($content);
        $this->_contentlessTag = false;
        $this->_tag = 'span';
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLSpan($content = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($content);
    }

    /**
     * Sets the div's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLSpan $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}

/**
 * cHTMLParagraph class represents a paragraph.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLParagraph extends cHTML {

    /**
     * Constructor.
     * Creates an HTML p element.
     *
     * @param mixed $content String or object with the contents
     * @param string $class class of this element
     * @return void
     */
    public function __construct($content = '', $class = '') {
        parent::__construct();
        $this->setContent($content);
        $this->setClass($class);
        $this->_contentlessTag = false;
        $this->_tag = 'p';
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLParagraph($content = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($content);
    }

    /**
     * Sets the p's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLParagraph $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}

/**
 * cHTMLImage class represents an image.
 *
 * @package Core
 * @subpackage Frontend
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
    public function __construct($src = null, $class = '') {
        parent::__construct();

        $this->_tag = 'img';
        $this->_contentlessTag = true;

        $this->setSrc($src);
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLImage($src = null) {
        cDeprecated('Use __construct() instead');
        $this->__construct($src);
    }

    /**
     * Sets the image's source file
     *
     * @param string $src source location
     * @return cHTMLImage $this
     */
    public function setSrc($src) {
        if ($src === null) {
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
     * Sets the alignment
     *
     * @param string $alignment the alignment of the image
     * @return cHTMLImage $this
     * @deprecated [2012-07-23] use CSS for alignment
     */
    public function setAlignment($alignment) {
        cDeprecated('Use CSS for alignment!');
        $this->updateAttribute('align', $alignment);

        return $this;
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

/**
 * cHTMLTable class represents a table.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTable extends cHTML {

    /**
     * Creates an HTML table element.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();

        $this->_tag = 'table';
        $this->_contentlessTag = false;
        $this->setPadding(0);
        $this->setSpacing(0);
        $this->setBorder(NULL);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTable() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Sets the table's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLTable $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

    /**
     * Sets the spacing between cells
     *
     * @param string $cellspacing Spacing
     * @return cHTMLTable $this
     */
    public function setCellSpacing($cellspacing) {
        return $this->updateAttribute('cellspacing', $cellspacing);
    }

    /**
     * Alias for setCellSpacing
     *
     * @param string $cellspacing
     * @return cHTMLTable $this
     */
    public function setSpacing($cellspacing) {
        return $this->setCellSpacing($cellspacing);
    }

    /**
     * Sets the padding between cells
     *
     * @param string $cellpadding Padding
     * @return cHTMLTable $this
     */
    public function setCellPadding($cellpadding) {
        return $this->updateAttribute('cellpadding', $cellpadding);
    }

    /**
     * Alias for setCellPadding
     *
     * @param string $cellpadding
     * @return cHTMLTable $this
     */
    public function setPadding($cellpadding) {
        return $this->setCellPadding($cellpadding);
    }

    /**
     * Sets the table's border
     *
     * @param string $border Border size
     * @return cHTMLTable $this
     */
    public function setBorder($border) {
        return $this->updateAttribute('border', $border);
    }

    /**
     * setWidth: Sets the table width
     *
     * @param $width Width
     * @return cHTMLTable $this
     */
    public function setWidth($width) {
        return $this->updateAttribute('width', $width);
    }

}

/**
 * cHTMLTableBody class represents a table body.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTableBody extends cHTML {

    /**
     * Creates an HTML tbody element.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->_contentlessTag = false;
        $this->_tag = 'tbody';
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableBody() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Sets the table body's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLTableBody $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}

/**
 * cHTMLTableRow class represents a table row.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTableRow extends cHTML {

    /**
     * Creates an HTML tr element.
     *
     * @return void
     */
    public function __construct($content = null) {
        parent::__construct();
        $this->_contentlessTag = false;
        $this->_tag = 'tr';
        if (!empty($content)) {
            $this->setContent($content);
        }
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableRow() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Sets the table row's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLTableRow $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}

/**
 * cHTMLTableData class represents a table date.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTableData extends cHTML {

    /**
     * Creates an HTML td element.
     *
     * @return void
     */
    public function __construct($content = null) {
        parent::__construct();
        $this->_contentlessTag = false;
        $this->_tag = 'td';
        if (!empty($content)) {
            $this->setContent($content);
        }
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableData() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Sets the table width
     *
     * @param string $width Width
     * @return cHTMLTableData $this
     */
    public function setWidth($width) {
        return $this->updateAttribute('width', $width);
    }

    /**
     * Sets the table height
     *
     * @param string $height Height
     * @return cHTMLTableData $this
     */
    public function setHeight($height) {
        return $this->updateAttribute('height', $height);
    }

    /**
     * Sets the table alignment
     *
     * @param string $alignment Alignment
     * @return cHTMLTableData $this
     */
    public function setAlignment($alignment) {
        return $this->updateAttribute('align', $alignment);
    }

    /**
     * Sets the table vertical alignment
     *
     * @param string $alignment Vertical Alignment
     * @return cHTMLTableData $this
     */
    public function setVerticalAlignment($alignment) {
        return $this->updateAttribute('valign', $alignment);
    }

    /**
     * Sets the table background color
     *
     * @param string $color background color
     * @return cHTMLTableData $this
     */
    public function setBackgroundColor($color) {
        return $this->updateAttribute('bgcolor', $color);
    }

    /**
     * Sets the table colspan
     *
     * @param string $colspan Colspan
     * @return cHTMLTableData $this
     */
    public function setColspan($colspan) {
        return $this->updateAttribute('colspan', $colspan);
    }

    /**
     * Sets the table data's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLTableData $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}

/**
 * cHTMLTableHead class represents a table head.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTableHead extends cHTML {

    /**
     * Creates an HTML th element.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->_contentlessTag = false;
        $this->_tag = 'th';
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableHead() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Sets the table head's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLTableHead $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}

/**
 * cHTMLTableHeader class represents a table header.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLTableHeader extends cHTML {

    /**
     * Creates an HTML thead element.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->_contentlessTag = false;
        $this->_tag = 'thead';
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableHeader() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Sets the table head's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLTableHeader $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

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

/**
 * cHTMLAlignmentTable class represents an alignment table.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLAlignmentTable extends cHTMLTable {

    public function __construct() {
        parent::__construct();

        $this->_data = func_get_args();
        $this->_contentlessTag = false;
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLAlignmentTable() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    public function render() {
        $tr = new cHTMLTableRow();
        $td = new cHTMLTableData();

        $out = '';

        foreach ($this->_data as $data) {
            $td->setContent($data);
            $out .= $td->render();
        }

        $tr->setContent($out);

        $this->setContent($tr);

        return $this->toHTML();
    }

}

/**
 * cHTMLForm class represents a form.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLForm extends cHTML {

    protected $_name;

    protected $_action;

    protected $_method;

    /**
     * Creates an HTML form element.
     *
     * @param string $name the name of the form
     * @param string $action the action which should be performed when this form
     *        is submitted
     * @param string $method the method to use - post or get
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name = '', $action = 'main.php', $method = 'post', $class = '') {
        parent::__construct();
        $this->_contentlessTag = false;
        $this->_tag = 'form';
        $this->_name = $name;
        $this->_action = $action;
        $this->_method = $method;
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLForm() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Adds content to the form
     *
     * @param string $content content to add
     * @return cHTMLForm $this
     * @deprecated 2012-09-12 Use appendContent($content) instead!
     */
    public function add($content) {
        cDeprecated('Use appendContent($content) instead!');

        return $this->appendContent($content);
    }

    /**
     * Sets the given var.
     *
     * @param string $var
     * @param string $value
     * @return cHTMLForm $this
     */
    public function setVar($var, $value) {
        $this->_vars[$var] = $value;

        return $this;
    }

    /**
     * Sets the form's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLForm $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

    /**
     * Renders the form element
     *
     * @return string Rendered HTML
     */
    public function toHTML() {
        $out = '';
        if (is_array($this->_vars)) {
            foreach ($this->_vars as $var => $value) {
                $f = new cHTMLHiddenField($var, $value);
                $out .= $f->render();
            }
        }
        if ($this->getAttribute('name') == '') {
            $this->setAttribute('name', $this->_name);
        }
        if ($this->getAttribute('method') == '') {
            $this->setAttribute('method', $this->_method);
        }
        if ($this->getAttribute('action') == '') {
            $this->setAttribute('action', $this->_action);
        }

        $attributes = $this->getAttributes(true);

        return $this->fillSkeleton($attributes) . $out . $this->_content . $this->fillCloseSkeleton();
    }

}

/**
 * cHTMLScript class represents a script.
 *
 * @todo Should set attribute type="text/javascript" by default or depending on
 *       doctype!
 * @package Core
 * @subpackage Frontend
 */
class cHTMLScript extends cHTML {

    /**
     * Creates an HTML script element.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
        $this->_contentlessTag = false;
        $this->_tag = 'script';
    }

    /**
     * Sets the table head's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLScript $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}

/**
 * cHTMLList class represents a list.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLList extends cHTML {

    /**
     * Creates an HTML list element.
     *
     * @param string $type type of the list - ul or ol
     * @param string $id the ID of the list element
     * @param string $class the class of the list element
     * @param array|string|object $elements the elements of this list
     * @return void
     */
    public function __construct($type = 'ul', $id = '', $class = '', $elements = array()) {
        parent::__construct();
        $this->_contentlessTag = false;
        if ($type !== 'ul' && $type !== 'ol') {
            $type = 'ul';
        }
        $this->_tag = $type;
        $this->setID($id);
        $this->setClass($class);
        $this->setContent($elements);
    }

    /**
     * Sets the list's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLList $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}

/**
 * cHTMLListItem class represents a list item.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLListItem extends cHTML {

    /**
     * Creates an HTML li element.
     *
     * @param string $id the ID of this list item
     * @param string $class the class of this list item
     * @return void
     */
    public function __construct($id = '', $class = '') {
        parent::__construct();
        $this->_contentlessTag = false;
        $this->_tag = 'li';
        $this->setID($id);
        $this->setClass($class);
    }

    /**
     * Sets the list item's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLListItem $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

}

/**
 * cHTMLLink class represents a link.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLLink extends cHTML {
    /* Stores the link location */
    protected $_link;

    /* Stores the content */
    protected $_content;

    /* Stores the anchor */
    protected $_anchor;

    /* Stores the custom entries */
    protected $_custom;

    protected $_image;

    /**
     * Constructor.
     * Creates an HTML link.
     *
     * @param string $href String with the location to link to
     * @return void
     */
    public function __construct($href = '') {
        global $sess;
        parent::__construct();

        $this->setLink($href);
        $this->_contentlessTag = false;
        $this->_tag = 'a';
        $this->_image = '';

        // Check for backend
        if (is_object($sess)) {
            if ($sess->classname == 'cSession') {
                $this->enableAutomaticParameterAppend();
            }
        }
    }

    public function enableAutomaticParameterAppend() {
        return $this->setEvent('click', 'var doit = true; try { var i = get_registered_parameters() } catch (e) { doit = false; }; if (doit == true) { this.href += i; }');
    }

    public function disableAutomaticParameterAppend() {
        return $this->unsetEvent('click');
    }

    /**
     * Sets the link to a specific location
     *
     * @param string $href String with the location to link to
     * @return cHTMLLink $this
     */
    public function setLink($href) {
        $this->_link = $href;
        $this->_type = 'link';

        if (strpos($href, 'javascript:') !== false) {
            $this->disableAutomaticParameterAppend();
        }

        return $this;
    }

    /**
     * Sets the target frame
     *
     * @param string $target Target frame identifier
     * @return cHTMLLink $this
     */
    public function setTargetFrame($target) {
        return $this->updateAttribute('target', $target);
    }

    /**
     * Sets a CONTENIDO link (area, frame, action)
     *
     * @param string $targetarea Target backend area
     * @param string $targetframe Target frame (1-4)
     * @param string $targetaction Target action
     * @return cHTMLLink $this
     */
    public function setCLink($targetarea, $targetframe, $targetaction = '') {
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
     * @return cHTMLLink $this
     */
    public function setMultiLink($righttoparea, $righttopaction, $rightbottomarea, $rightbottomaction) {
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
     * @return cHTMLLink $this
     */
    public function setCustom($key, $value) {
        $this->_custom[$key] = $value;

        return $this;
    }

    public function setImage($src) {
        $this->_image = $src;

        return $this;
    }

    /**
     * Unsets a previous set custom attribute
     *
     * @param string $key Parameter name
     * @return cHTMLLink $this
     */
    public function unsetCustom($key) {
        if (isset($this->_custom[$key])) {
            unset($this->_custom[$key]);
        }

        return $this;
    }

    public function getHref() {
        global $sess;

        if (is_array($this->_custom)) {
            $custom = '';

            foreach ($this->_custom as $key => $value) {
                $custom .= "&$key=$value";
            }
        }

        if ($this->_anchor) {
            $anchor = '#' . $this->_anchor;
        } else {
            $anchor = '';
        }

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
                break;
            case 'clink':
                $this->disableAutomaticParameterAppend();
                return 'main.php?area=' . $this->_targetarea . '&frame=' . $this->_targetframe . '&action=' . $this->_targetaction . $custom . '&contenido=' . $sess->id . $anchor;
                break;
            case 'multilink':
                $this->disableAutomaticParameterAppend();
                $tmp_mstr = 'javascript:conMultiLink(\'%s\',\'%s\',\'%s\',\'%s\');';
                $mstr = sprintf($tmp_mstr, 'right_top', $sess->url('main.php?area=' . $this->_targetarea . '&frame=' . $this->_targetframe . '&action=' . $this->_targetaction . $custom), 'right_bottom', $sess->url('main.php?area=' . $this->_targetarea2 . '&frame=' . $this->_targetframe2 . '&action=' . $this->_targetaction2 . $custom));
                return $mstr;
                break;
        }
    }

    /**
     * Sets an anchor
     * Only works for the link types Link and cLink.
     *
     * @param string $content Anchor name
     * @return cHTMLLink $this
     */
    public function setAnchor($anchor) {
        $this->_anchor = $anchor;

        return $this;
    }

    /**
     * Sets the link's content
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLLink $this
     */
    public function setContent($content) {
        return $this->_setContent($content);
    }

    /**
     * Appends code / objects to the content.
     *
     * @param string|object|array $content String with the content or a cHTML
     *        object to render or an array of strings / objects.
     * @return cHTMLDiv $this
     */
    public function appendContent($content) {
        return $this->_appendContent($content);
    }

    /**
     * Renders the link
     *
     * @return string Rendered HTML
     */
    public function toHTML() {
        $this->updateAttribute('href', $this->getHref());

        if ($this->_image != '') {
            $image = new cHTMLImage($this->_image);
            $this->setContent($image);
        }

        return parent::toHTML();
    }

}

/**
 * Old class name for downwards compatibility
 *
 * @deprecated [2012-07-12] This class was renamed to cHTMLLink
 */
class Link extends cHTMLLink {

    public $link;

    public $title;

    public $targetarea;

    public $targetframe;

    public $targetaction;

    public $targetarea2;

    public $targetframe2;

    public $targetaction2;

    public $caption;

    public $javascripts;

    public $type;

    public $custom;

    public $content;

    public $attributes;

    public $img_width;

    public $img_height;

    public $img_type;

    public $img_attr;

    public function __construct() {
        cDeprecated('This class was renamed to cHTMLLink');

        parent::__construct();
    }

    function setJavascript($js) {
        cDeprecated('This function never did anything.');
    }

}