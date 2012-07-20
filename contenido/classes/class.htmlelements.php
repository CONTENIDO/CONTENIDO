<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * HTML elements
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.6.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-08-21
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Base class for all CONTENIDO HTML classes
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTML {

    /**
     * Id attribute counter, used to generate unique values for id-attributes
     * @var int
     */
    protected static $_idCounter = 0;

    /**
     * Flag to generate XHTML valid elements
     * @var bool
     */
    protected static $_generateXHTML = null;

    /**
     * Storage of the open SGML tag template
     * @var string
     */
    protected $_skeletonOpen;

    /**
     * Storage of a single SGML tag template
     * @var string
     */
    protected $_skeletonSingle;

    /**
     * Storage of the close SGML tag
     * @var string
     */
    protected $_skeletonClose;

    /**
     * Defines which tag to use
     * @var string
     */
    protected $_tag;

    /**
     * Defines the style definitions
     * @var array
     */
    protected $_styleDefs;

    /**
     * Defines all scripts which are required by the current element
     * @var array
     */
    protected $_requiredScripts;

    /**
     * Defines if the current tag is a contentless tag
     * @var bool
     */
    protected $_contentlessTag;

    /**
     * Defines which JS events contain which scripts
     * @var array
     */
    protected $_eventDefinitions;

    /**
     * Style definitions
     * @var array
     */
    protected $_styleDefinitions;

    /**
     * Attributes
     * @var array
     */
    protected $_attributes;

    /**
     * The content itself
     * @var string
     */
    protected $_content;

    /**
     * Constructor Function, initializes the SGML open/close tags
     * @param   array   $aAttributes   Associative array of table tag attributes
     * @return  void
     */
    public function __construct($aAttributes = null) {
        $this->setAttributes($aAttributes);

        $this->_skeletonOpen = '<%s%s>';
        $this->_skeletonClose = '</%s>';

        if (null === self::$_generateXHTML) {
            self::$_generateXHTML = getEffectiveSetting('generator', 'xhtml', 'false');
            self::$_generateXHTML = (self::$_generateXHTML == 'true');
        }

        if (true === self::$_generateXHTML) {
            $this->_skeletonSingle = '<%s%s />';
        } else {
            $this->_skeletonSingle = '<%s%s>';
        }

        $this->_styleDefs = array();
        $this->_styleDefinitions = array();
        $this->setContentlessTag();

        $this->advanceID();
        $this->_requiredScripts = array();
        $this->_eventDefinitions = array();
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTML() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Contentless tag setter
     * @param  bool  $contentlessTag
     */
    public function setContentlessTag($contentlessTag = true) {
        $this->_contentlessTag = $contentlessTag;
    }

    /**
     * advances to the next ID available in the system.
     *
     * This function is useful if you need to use HTML elements
     * in a loop, but don't want to re-create new objects each time.
     */
    public function advanceID() {
        self::$_idCounter++;
        $this->updateAttributes(array('id' => 'm' . self::$_idCounter));
    }

    /**
     * Returns the current ID
     * @return string current ID
     */
    public function getID() {
        return $this->getAttribute("id");
    }

    /**
     * Sets the HTML tag to $tag
     * @param  string  $tag  The new tag
     */
    public function setTag($tag) {
        $this->_tag = $tag;
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
     * @param  string  $alt  Text to set as the "alt" and "title" attribute
     */
    public function setAlt($alt) {
        $attributes = array("alt" => $alt, "title" => $alt);

        $this->updateAttributes($attributes);
    }

    /**
     * Sets the ID class
     *
     * @param  string  $id  Text to set as the "id"
     */
    public function setID($id) {
        $this->updateAttributes(array("id" => $id));
    }

    /**
     * Sets the CSS class
     *
     * @param  string  $class  Text to set as the "class" attribute
     */
    public function setClass($class) {
        $this->updateAttributes(array("class" => $class));
    }

    /**
     * Sets the CSS style
     *
     * @param  string  $style  Text to set as the "style" attribute
     */
    public function setStyle($style) {
        $this->updateAttributes(array("style" => $style));
    }

    /**
     * Adds an "onXXX" javascript event handler
     *
     * example:
     * $item->setEvent("change","document.forms[0].submit");
     *
     * @param  string  $event  Type of the event, e. g. "change" for "onchange"
     * @param  string  $action  Function or action to call (JavaScript Code)
     */
    public function setEvent($event, $action) {
        if (substr($event, 0, 2) != "on") {
            $this->updateAttributes(array("on" . $event => $action));
        } else {
            $this->updateAttributes(array($event => $action));
        }
    }

    /**
     * Removes an event handler
     *
     * example:
     * $item->unsetEvent("change");
     *
     * @param  string  $event  Type of the event
     */
    public function unsetEvent($event) {
        if (substr($event, 0, 2) != "on") {
            $this->removeAttribute("on" . $event);
        } else {
            $this->removeAttribute($event);
        }
    }

    /**
     * Fills the open SGML tag skeleton
     *
     * fillSkeleton fills the SGML opener tag with the
     * specified attributes. Attributes need to be passed
     * in the stringyfied variant.
     *
     * @param  string  $attributes  Attributes to set
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
     * @deprecated name change, use attachStyleDefinition
     * @param  string  $entity  Entity to define
     * @param  string  $definition  Definition for the given entity
     */
    public function setStyleDefinition($entity, $definition) {
        cDeprecated("Use attachStyleDefinition instead");
        $this->_styleDefs[$entity] = $definition;
    }

    /**
     * Attaches a style definition.
     *
     * This function is not restricted to a single style, e.g.
     * you can set multiple style definitions as-is to the handler.
     *
     * $example->attachStyle("myIdentifier",
     *             "border: 1px solid black; white-space: nowrap");
     * $example->attachStyle("myIdentifier2",
     *                         "padding: 0px");
     *
     * Results in:
     *
     * style="border: 1px solid black; white-space: nowrap; padding: 0px;"
     *
     * @param  string  $sName  Name for a style definition
     * @param  string  $sDefinition  Definition for the given entity
     */
    public function attachStyleDefinition($sName, $sDefinition) {
        $this->_styleDefinitions[$sName] = $sDefinition;
    }

    public function addRequiredScript($script) {
        if (!is_array($this->_requiredScripts)) {
            $this->_requiredScripts = array();
        }

        $this->_requiredScripts[] = $script;

        $this->_requiredScripts = array_unique($this->_requiredScripts);
    }

    /**
     * Sets the content of the object
     *
     * @param  string|object  $content  String with the content or an object to render.
     */
    protected function _setContent($content) {
        $this->setContentlessTag(false);
        // Is it an array?
        if (is_array($content)) {
            unset($this->_content);

            $this->_content = "";

            foreach ($content as $item) {
                if (is_object($item)) {
                    if (method_exists($item, "render")) {
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
            if (is_object($content)) {
                if (method_exists($content, "render")) {
                    $this->_content = $content->render();
                }

                if (count($content->_requiredScripts) > 0) {
                    $this->_requiredScripts = array_merge($this->_requiredScripts, $content->_requiredScripts);
                }

                return;
            } else {
                $this->_content = $content;
            }
        }
    }

    /**
     * Attaches the code for an event
     *
     * Example to attach an onClick handler:
     * setEventDefinition("foo", "onClick", "alert('foo');");
     *
     * @param  string  $sName  Defines the name of the event
     * @param  string  $sEvent  Defines the event (e.g. onClick)
     * @param  string  $sCode  Defines the code
     */
    public function attachEventDefinition($sName, $sEvent, $sCode) {
        $this->_eventDefinitions[strtolower($sEvent)][$sName] = $sCode;
    }

    /**
     * Sets a specific attribute
     *
     * @param  string  $sAttributeName  Name of the attribute
     * @param  string  $sValue  Value of the attribute
     */
    public function setAttribute($sAttributeName, $sValue) {
        $sAttributeName = strtolower($sAttributeName);
        if (is_null($sValue)) {
            $sValue = $sAttributeName;
        }
        $this->_attributes[$sAttributeName] = $sValue;
    }

    /**
     * Sets the HTML attributes
     * @param   array  $aAttributes  Associative array with attributes
     */
    public function setAttributes($aAttributes) {
        $this->_attributes = $this->_parseAttributes($aAttributes);
    }

    /**
     * Returns a valid atrributes array.
     * @param  array  $aAttributes  Associative array with attributes
     * @return  array
     */
    protected function _parseAttributes($aAttributes) {
        if (!is_array($aAttributes)) {
            return array();
        }

        $aReturn = array();
        foreach ($aAttributes as $sKey => $sValue) {
            if (is_int($sKey)) {
                $sKey = $sValue = strtolower($sValue);
            } else {
                $sKey = strtolower($sKey);
            }

            $aReturn[$sKey] = $sValue;
        }

        return $aReturn;
    }

    /**
     * Removes an attribute
     * @param  string  $sAttributeName  Attribute name
     */
    public function removeAttribute($sAttributeName) {
        if (isset($this->_attributes[$sAttributeName])) {
            unset($this->_attributes[$sAttributeName]);
        }
    }

    /**
     * Returns the value of the given attribute.
     * @param  string  $sAttributeName  Attribute name
     * @return  string|null  Returns null if the attribute does not exist
     */
    public function getAttribute($sAttributeName) {
        $sAttributeName = strtolower($sAttributeName);

        if (isset($this->_attributes[$sAttributeName])) {
            return $this->_attributes[$sAttributeName];
        }

        return null;
    }

    /**
     * Updates the passed attributes without changing the other existing attributes
     * @param  array  $aAttributes  Associative array with attributes
     */
    public function updateAttributes($aAttributes) {
        $aAttributes = $this->_parseAttributes($aAttributes);

        foreach ($aAttributes as $sKey => $sValue) {
            $this->_attributes[$sKey] = $sValue;
        }
    }

    /**
     * Returns an HTML formatted attribute string
     * @param  array  $aAttributes  Associative array with attributes
     * @return  string  Attrbiute string in HTML format
     */
    protected function _getAttrString($aAttributes) {
        $sAttrString = '';

        if (!is_array($aAttributes)) {
            return '';
        }

        foreach ($aAttributes as $sKey => $sValue) {
            $sAttrString .= ' ' . $sKey . '="' . htmlspecialchars($sValue, ENT_COMPAT) . '"';
        }

        return $sAttrString;
    }

    /**
     * Returns the assoc array(default) or string of attributes
     * @param  bool  $bReturnAsString  Whether to return the attributes as string
     * @return  array|string  Attributes
     */
    public function getAttributes($bReturnAsString = false) {
        if ($bReturnAsString) {
            return $this->_getAttrString($this->_attributes);
        } else {
            return $this->_attributes;
        }
    }

    /**
     * Generates the markup of the element
     * @return  string   Generated markup
     */
    public function toHTML() {
        // Fill style definition
        $style = $this->getAttribute("style");

        // If the style doesn't end with a semicolon, append one
        if (is_string($style)) {
            $style = trim($style);
            if (substr($style, strlen($style) - 1) != ";") {
                $style .= ";";
            }
        }

        foreach ($this->_styleDefinitions as $sEntry) {
            $style .= $sEntry;
            if (substr($style, strlen($style) - 1) != ";") {
                $style .= ";";
            }
        }

        foreach ($this->_eventDefinitions as $sEventName => $sEntry) {
            $aFullCode = array();
            foreach ($sEntry as $sName => $sCode) {
                $aFullCode[] = $sCode;
            }
            $this->setAttribute($sEventName, $this->getAttribute($sEventName) . implode(" ", $aFullCode));
        }

        // Apply all stored styles
        foreach ($this->_styleDefs as $key => $value) {
            $style .= "$key: $value;";
        }

        if ($style != "") {
            $this->setStyle($style);
        }

        if ($this->_content != "" || $this->_contentlessTag == false) {
            $attributes = $this->getAttributes(true);
            return $this->fillSkeleton($attributes) . $this->_content . $this->fillCloseSkeleton();
        } else {
            // This is a single style tag
            $attributes = $this->getAttributes(true);

            return $this->fillSkeleton($attributes);
        }
    }

    /**
     * Alias for toHtml
     * @return  string  Generated markup
     */
    public function render() {
        return $this->toHtml();
    }

    /**
     * Direct call of object as string will return it's generated markup.
     * @return  string  Generated markup
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
 * HTML Form element class
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLFormElement extends cHTML {

    /**
     * Constructor. This is a generic form element, where
     * specific elements should be inherited from this class.
     *
     * @param  string  $name  Name of the element
     * @param  string  $id  ID of the element
     * @param  string  $disabled  Item disabled flag (non-empty to set disabled)
     * @param  string  $tabindex  Tab index for form elements
     * @param  string  $accesskey  Key to access the field
     * @param  string  $class  CSS class name to set
     */
    public function __construct($name = "", $id = "", $disabled = "", $tabindex = "", $accesskey = "", $class = "text_medium") {
        parent::__construct();

        $this->updateAttributes(array("name" => $name));

        if (is_string($id) && !empty($id)) {
            $this->updateAttributes(array("id" => $id));
        }

        $this->_tag = "input";

        $this->setClass($class);
        $this->setDisabled($disabled);
        $this->setTabindex($tabindex);
        $this->setAccessKey($accesskey);
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLFormElement($name = "", $id = "", $disabled = "", $tabindex = "", $accesskey = "", $class = "text_medium") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $id, $disabled, $tabindex, $accesskey, $class);
    }

    /**
     * Sets the "disabled" attribute of an element. User Agents
     * usually are showing the element as "greyed-out".
     *
     * Example:
     * $obj->setDisabled("disabled");
     * $obj->setDisabled("");
     *
     * The first example sets the disabled flag, the second one
     * removes the disabled flag.
     *
     * @param  string  $disabled  Sets the disabled-flag if non-empty
     */
    public function setDisabled($disabled) {
        if (!empty($disabled)) {
            $this->updateAttributes(array("disabled" => "disabled"));
        } else {
            $this->removeAttribute("disabled");
        }
    }

    /**
     * Sets the tab index for this element. The tab
     * index needs to be numeric, bigger than 0 and smaller than 32767.
     *
     * @param  int  $tabindex Desired tab index
     */
    public function setTabindex($tabindex) {
        if (is_numeric($tabindex) && $tabindex >= 0 && $tabindex <= 32767) {
            $this->updateAttributes(array("tabindex" => $tabindex));
        }
    }

    /**
     * Sets the access key for this element.
     *
     * @param  string  $accesskey  The length of the access key. May be A-Z and 0-9.
     */
    public function setAccessKey($accesskey) {
        if ((strlen($accesskey) == 1) && is_alphanumeric($accesskey)) {
            $this->updateAttributes(array("accesskey" => $accesskey));
        } else {
            $this->removeAttribute("accesskey");
        }
    }

}

/**
 * HTML Hidden Field
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLHiddenField extends cHTMLFormElement {

    /**
     * Constructor. Creates an HTML hidden field.
     *
     * @param  string  $name  Name of the element
     * @param  string  $value  Title of the button
     * @param  string  $id  ID of the element
     */
    public function __construct($name, $value = "", $id = "") {
        parent::__construct($name, $id);
        $this->setContentlessTag();
        $this->updateAttributes(array("type" => "hidden"));
        $this->_tag = "input";

        $this->setValue($value);
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLHiddenField($name, $value = "", $id = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $value, $id);
    }

    /**
     * Sets the value for the field
     *
     * @param  string  $value  Value of the field
     */
    public function setValue($value) {
        $this->updateAttributes(array("value" => $value));
    }

    /**
     * Renders the hidden field
     * @return  string  Generated markup
     */
    public function toHtml() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes);
    }

}

/**
 * HTML Button class
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLButton extends cHTMLFormElement {

    /**
     * Constructor. Creates an HTML button.
     *
     * Creates a submit button by default, can be changed
     * using setMode.
     *
     * @param  string  $name  Name of the element
     * @param  string  $title  Title of the button
     * @param  string  $id  ID of the element
     * @param  string  $disabled  Item disabled flag (non-empty to set disabled)
     * @param  string  $tabindex  Tab index for form elements
     * @param  string  $accesskey  Key to access the field
     * @param  string  $mode  Mode of button
     */
    public function __construct($name, $title = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "", $mode = "submit") {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = "input";
        $this->setContentlessTag();
        $this->setTitle($title);
        $this->setMode($mode);
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLButton($name, $title = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "", $mode = "submit") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $title, $id, $disabled, $tabindex, $accesskey, $mode);
    }

    /**
     * Sets the title (caption) for the button
     *
     * @param  string  $title  The title to set
     */
    public function setTitle($title) {
        $this->updateAttributes(array("value" => $title));
    }

    /**
     * Sets the mode (submit or reset) for the button
     *
     * @param  string  $mode  Either "submit", "reset" or "image".
     * @return  bool  Returns false if failed to set the mode
     */
    public function setMode($mode) {
        switch ($mode) {
            case "submit":
            case "reset":
                $this->updateAttributes(array("type" => $mode));
                break;
            case "image":
                $this->updateAttributes(array("type" => $mode));
                break;
            case "button":
                $this->updateAttributes(array("type" => $mode));
                break;
            default:
                return false;
        }
    }

    /**
     * Set the image src if mode type is "image"
     *
     * @param  string  $mode Image path.
     */
    public function setImageSource($src) {
        $this->updateAttributes(array("src" => $src));
    }

    /**
     * Renders the button
     *
     * @return string Rendered HTML
     */
    public function toHtml() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes);
    }

}

/**
 * HTML Textbox
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTextbox extends cHTMLFormElement {

    /**
     * Constructor. Creates an HTML text box.
     *
     * If no additional parameters are specified, the
     * default width is 20 units.
     *
     * @param  string  $name  Name of the element
     * @param  string  $initvalue  Initial value of the box
     * @param  int  $width  width of the text box
     * @param  int  $maxlength  maximum input length of the box
     * @param  string  $id  ID of the element
     * @param  string  $disabled  Item disabled flag (non-empty to set disabled)
     * @param  string  $tabindex  Tab index for form elements
     * @param  string  $accesskey  Key to access the field
     */
    public function __construct($name, $initvalue = "", $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);

        $this->_tag = "input";
        $this->setContentlessTag();
        $this->setValue($initvalue);

        $this->setWidth($width);
        $this->setMaxLength($maxlength);

        $this->updateAttributes(array("type" => "text"));
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTextbox($name, $initvalue = "", $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $initvalue, $width, $maxlength, $id, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the width of the text box.
     * @param  int  $width  width of the text box
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 50;
        }

        $this->updateAttributes(array("size" => $width));
    }

    /**
     * Sets the maximum input length of the text box.
     * @param  int  $maxlen  maximum input length
     */
    public function setMaxLength($maxlen) {
        $maxlen = intval($maxlen);

        if ($maxlen <= 0) {
            $this->removeAttribute("maxlength");
        } else {
            $this->updateAttributes(array("maxlength" => $maxlen));
        }
    }

    /**
     * Sets the initial value of the text box.
     *
     * @param  string  $value  Initial value
     */
    public function setValue($value) {
        $this->updateAttributes(array("value" => $value));
    }

}

/**
 * HTML Password Box
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLPasswordbox extends cHTMLFormElement {

    /**
     * Constructor. Creates an HTML password box.
     *
     * If no additional parameters are specified, the
     * default width is 20 units.
     *
     * @param  string  $name  Name of the element
     * @param  string  $initvalue  Initial value of the box
     * @param  int  $width  width of the text box
     * @param  int  $maxlength  maximum input length of the box
     * @param  string  $id  ID of the element
     * @param  string  $disabled  Item disabled flag (non-empty to set disabled)
     * @param  string  $tabindex  Tab index for form elements
     * @param  string  $accesskey  Key to access the field
     */
    public function __construct($name, $initvalue = "", $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = "input";
        $this->setValue($initvalue);

        $this->setWidth($width);
        $this->setMaxLength($maxlength);

        $this->updateAttributes(array("type" => "password"));
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLPasswordbox($name, $initvalue = "", $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $initvalue, $width, $maxlength, $id, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the width of the text box.
     * @param  int  $width  width of the text box
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 20;
        }

        $this->updateAttributes(array("size" => $width));
    }

    /**
     * Sets the maximum input length of the text box.
     * @param  int  $maxlen  maximum input length
     */
    public function setMaxLength($maxlen) {
        $maxlen = intval($maxlen);

        if ($maxlen <= 0) {
            $this->removeAttribute("maxlength");
        } else {
            $this->updateAttributes(array("maxlength" => $maxlen));
        }
    }

    /**
     * Sets the initial value of the text box.
     * @param  string  $value  Initial value
     */
    public function setValue($value) {
        $this->updateAttributes(array("value" => $value));
    }

}

class cHTMLTextarea extends cHTMLFormElement {

    protected $_value;

    /**
     * Constructor. Creates an HTML text area.
     *
     * If no additional parameters are specified, the
     * default width is 60 chars, and the height is 5 chars.
     *
     * @param  string  $name  Name of the element
     * @param  string  $initvalue  Initial value of the textarea
     * @param  int  $width  width of the textarea
     * @param  int  $height  height of the textarea
     * @param  string  $id  ID of the element
     * @param  string  $disabled  Item disabled flag (non-empty to set disabled)
     * @param  string  $tabindex  Tab index for form elements
     * @param  string  $accesskey  Key to access the field
     */
    public function __construct($name, $initvalue = "", $width = "", $height = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = "textarea";
        $this->setValue($initvalue);
        $this->setContentlessTag(false);
        $this->setWidth($width);
        $this->setHeight($height);
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTextarea($name, $initvalue = "", $width = "", $height = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $initvalue, $width, $height, $id, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the width of the text box.
     * @param  int  $width  width of the text box
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 50;
        }

        $this->updateAttributes(array("cols" => $width));
    }

    /**
     * Sets the maximum input length of the text box.
     * @param  int  $maxlen  maximum input length
     */
    public function setHeight($height) {
        $height = intval($height);

        if ($height <= 0) {
            $height = 5;
        }

        $this->updateAttributes(array("rows" => $height));
    }

    /**
     * Sets the initial value of the text box.
     * @param  string  $value  Initial value
     */
    public function setValue($value) {
        $this->_value = $value;
    }

    /**
     * Renders the textbox
     * @return string Rendered HTML
     */
    public function toHtml() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes) . $this->_value . $this->fillCloseSkeleton();
    }

}

/**
 * HTML Label for form elements
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLLabel extends cHTML {

    /**
     * The text to display on the label
     * @var string
     */
    public $text;

    /**
     * Constructor. Creates an HTML label which can be linked
     * to any form element (specified by their ID).
     *
     * A label can be used to link to elements. This is very useful
     * since if a user clicks a label, the linked form element receives
     * the focus (if supported by the user agent).
     *
     * @param  string  $text  Name of the element
     * @param  string  $for  ID of the form element to link to.
     *
     * @return none
     */
    public function __construct($text, $for) {
        parent::__construct();
        $this->_tag = "label";
        $this->setContentlessTag(false);
        $this->updateAttributes(array("for" => $for));
        $this->text = $text;
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLLabel($text, $for) {
        cDeprecated("Use __construct() instead");
        $this->__construct($text, $for);
    }

    /**
     * Renders the label
     *
     * @param none
     * @return string Rendered HTML
     */
    public function toHtml() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes) . $this->text . $this->fillCloseSkeleton();
    }

}

/**
 * HTML Select Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLSelectElement extends cHTMLFormElement {

    /**
     * All cHTMLOptionElements
     * @var array
     */
    protected $_options;

    /**
     * Constructor. Creates an HTML select field (aka "DropDown").
     *
     * @param  string  $name  Name of the element
     * @param  int  $width  Width of the select element (note: not used)
     * @param  string  $id  ID of the element
     * @param  bool  $disabled  Item disabled flag (non-empty to set disabled)
     * @param  string  $tabindex  Tab index for form elements
     * @param  string  $accesskey  Key to access the field
     */
    public function __construct($name, $width = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = "select";
        $this->setContentlessTag(false);
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLSelectElement($name, $width = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $width, $id, $disabled, $tabindex, $accesskey);
    }

    /**
     * Automatically creates and fills cHTMLOptionElements
     *
     * Array format:
     * $stuff = array(
     *     array("value","title"),
     *     array("value","title")
     * );
     *
     * or regular key => value arrays.
     *
     * @param  array  $stuff  Array with all items
     */
    public function autoFill($stuff) {
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
    }

    /**
     * Adds an cHTMLOptionElement to the number of choices.
     * @param  string  $index  Index of the element
     * @param  object  $element  Filled cHTMLOptionElement to add
     */
    public function addOptionElement($index, $element) {
        $this->_options[$index] = $element;
    }

    public function setMultiselect() {
        $this->updateAttributes(array("multiple" => "multiple"));
    }

    public function setSize($size) {
        $this->updateAttributes(array("size" => $size));
    }

    /**
     * Sets a specific cHTMLOptionElement to the selected state.
     * @param  string  $lvalue  Specifies the "value" of the cHTMLOptionElement to set
     */
    public function setDefault($lvalue) {
        if (is_array($this->_options) && is_array($lvalue)) {
            foreach ($this->_options as $key => $value) {
                if (in_array($value->getAttribute("value"), $lvalue)) {
                    $value->setSelected(true);
                    $this->_options[$key] = $value;
                } else {
                    $value->setSelected(false);
                    $this->_options[$key] = $value;
                }
            }
        } else {
            foreach ($this->_options as $key => $value) {
                if (strcmp($value->getAttribute("value"), $lvalue) == 0) {
                    $value->setSelected(true);
                    $this->_options[$key] = $value;
                } else {
                    $value->setSelected(false);
                    $this->_options[$key] = $value;
                }
            }
        }
    }

    /**
     * Search for the selected elements
     * @return  string|bool  Selected "lvalue" or false
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
     * @param  array  $aElements  Array with "values" of the cHTMLOptionElement to set
     */
    public function setSelected($aElements) {
        if (is_array($this->_options) && is_array($aElements)) {
            foreach ($this->_options as $sKey => $oOption) {
                if (in_array($oOption->getAttribute("value"), $aElements)) {
                    $oOption->setSelected(true);
                    $this->_options[$sKey] = $oOption;
                } else {
                    $oOption->setSelected(false);
                    $this->_options[$sKey] = $oOption;
                }
            }
        }
    }

    /**
     * Renders the select box
     * @return string Rendered HTML
     */
    public function toHtml() {
        $attributes = $this->getAttributes(true);

        $options = "";

        if (is_array($this->_options)) {
            foreach ($this->_options as $key => $value) {
                $options .= $value->toHtml();
            }
        }

        return ($this->fillSkeleton($attributes) . $options . $this->fillCloseSkeleton());
    }

}

/**
 * HTML Select Option Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLOptionElement extends cHTMLFormElement {

    /**
     * Title to display
     * @var string
     */
    protected $_title;

    /**
     * Constructor. Creates an HTML option element.
     *
     * @param  string  $title  Displayed title of the element
     * @param  string  $value  Value of the option
     * @param  bool  $selected  If true, element is selected
     * @param  bool  $disabled  If true, element is disabled
     */
    public function __construct($title, $value, $selected = false, $disabled = false) {
        cHTML::__construct();
        $this->_tag = "option";
        $this->_title = $title;

        $this->updateAttributes(array("value" => $value));
        $this->setContentlessTag(false);

        $this->setSelected($selected);
        $this->setDisabled($disabled);
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLOptionElement($title, $value, $selected = false, $disabled = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($title, $value, $selected, $disabled);
    }

    /**
     * Sets the selected flag
     * @param  bool  $selected  If true, adds the "selected" attribute
     */
    public function setSelected($selected) {
        if ($selected == true) {
            $this->updateAttributes(array("selected" => "selected"));
        } else {
            $this->removeAttribute("selected");
        }
    }

    /**
     * Sets the selected flag
     * @param  bool  $selected  If true, adds the "selected" attribute
     */
    public function isSelected() {
        if ($this->getAttribute("selected") == "selected") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sets the disabled flag
     * @param  bool  $disabled  If true, adds the "disabled" attribute
     */
    public function setDisabled($disabled) {
        if ($disabled == true) {
            $this->updateAttributes(array("disabled" => "disabled"));
        } else {
            $this->removeAttribute("disabled");
        }
    }

    /**
     * Renders the option element. Note:
     * the cHTMLSelectElement renders the options by itself.
     * @return string Rendered HTML
     */
    public function toHtml() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes) . $this->_title . $this->fillCloseSkeleton();
    }

}

/**
 * HTML Radio Button
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLRadiobutton extends cHTMLFormElement {

    /**
     * Values for the check box
     * @var string
     */
    protected $_value;

    /**
     * Constructor. Creates an HTML radio button element.
     *
     * @param  string  $name  Name of the element
     * @param  string  $value  Value of the radio button
     * @param  string  $id  ID of the element
     * @param  bool  $checked  Is element checked?
     * @param  string  $disabled  Item disabled flag (non-empty to set disabled)
     * @param  string  $tabindex  Tab index for form elements
     * @param  string  $accesskey  Key to access the field
     */
    public function __construct($name, $value, $id = "", $checked = false, $disabled = false, $tabindex = null, $accesskey = "") {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = "input";
        $this->_value = $value;
        $this->setContentlessTag();

        $this->setChecked($checked);
        $this->updateAttributes(array("type" => "radio"));
        $this->updateAttributes(array("value" => $value));
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLRadiobutton($name, $value, $id = "", $checked = false, $disabled = false, $tabindex = null, $accesskey = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $value, $id, $checked, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the checked flag.
     * @param  bool  $checked  If true, the "checked" attribute will be assigned.
     */
    public function setChecked($checked) {
        if ($checked == true) {
            $this->updateAttributes(array("checked" => "checked"));
        } else {
            $this->removeAttribute("checked");
        }
    }

    /**
     * Sets a custom label text
     * @param  string  $text  Text to display
     */
    public function setLabelText($text) {
        $this->_labelText = $text;
    }

    /**
     * Renders the option element. Note:
     *
     * If this element has an ID, the value (which equals the text displayed)
     * will be rendered as seperate HTML label, if not, it will be displayed
     * as regular text. Displaying the value can be turned off via the parameter.
     *
     * @param  bool  $renderlabel  If true, renders a label
     * @return string Rendered HTML
     */
    public function toHtml($renderLabel = true) {
        $attributes = $this->getAttributes(true);

        if ($renderLabel == false) {
            return $this->fillSkeleton($attributes);
        }

        $id = $this->getAttribute("id");

        $renderedLabel = "";

        if ($id != "") {
            $label = new cHTMLLabel($this->_value, $this->getAttribute("id"));

            if ($this->_labelText != "") {
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
 * HTML Checkbox
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLCheckbox extends cHTMLFormElement {

    protected $_value;

    /**
     * Constructor. Creates an HTML checkbox element.
     *
     * @param  string  $name  Name of the element
     * @param  string  $value  Value of the radio button
     * @param  string  $id  ID of the element
     * @param  bool  $checked  Is element checked?
     * @param  string  $disabled  Item disabled flag (non-empty to set disabled)
     * @param  string  $tabindex  Tab index for form elements
     * @param  string  $accesskey  Key to access the field
     */
    public function __construct($name, $value, $id = "", $checked = false, $disabled = false, $tabindex = null, $accesskey = "") {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = "input";
        $this->_value = $value;
        $this->setContentlessTag();

        $this->setChecked($checked);
        $this->updateAttributes(array("type" => "checkbox"));
        $this->updateAttributes(array("value" => $value));
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLRadiobutton($name, $value, $id = "", $checked = false, $disabled = false, $tabindex = null, $accesskey = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $value, $id, $checked, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the checked flag.
     * @param  bool  $checked  If true, the "checked" attribute will be assigned.
     */
    public function setChecked($checked) {
        if ($checked == true) {
            $this->updateAttributes(array("checked" => "checked"));
        } else {
            $this->removeAttribute("checked");
        }
    }

    /**
     * Sets a custom label text
     * @param  string  $text  Text to display
     */
    public function setLabelText($text) {
        $this->_labelText = $text;
    }

    /**
     * Renders the checkbox element. Note:
     *
     * If this element has an ID, the value (which equals the text displayed)
     * will be rendered as seperate HTML label, if not, it will be displayed
     * as regular text. Displaying the value can be turned off via the parameter.
     *
     * @param  bool  $renderlabel  If true, renders a label
     * @return string Rendered HTML
     */
    public function toHtml($renderlabel = true) {
        $id = $this->getAttribute("id");

        $renderedLabel = "";

        if ($renderlabel == true) {
            if ($id != "") {
                $label = new cHTMLLabel($this->_value, $this->getAttribute("id"));

                $label->setClass($this->getAttribute("class"));

                if ($this->_labelText != "") {
                    $label->text = $this->_labelText;
                }

                $renderedLabel = $label->toHtml();
            } else {

                $renderedLabel = $this->_value;

                if ($this->_labelText != "") {
                    $label = new cHTMLLabel($this->_value, $this->getAttribute("id"));
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
 * HTML File upload box
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLUpload extends cHTMLFormElement {

    /**
     * Constructor. Creates an HTML upload box.
     *
     * If no additional parameters are specified, the
     * default width is 20 units.
     *
     * @param  string  $name  Name of the element
     * @param  string  $initvalue  Initial value of the box
     * @param  int  $width  width of the text box
     * @param  int  $maxlength  maximum input length of the box
     * @param  string  $id  ID of the element
     * @param  string  $disabled  Item disabled flag (non-empty to set disabled)
     * @param  string  $tabindex  Tab index for form elements
     * @param  string  $accesskey  Key to access the field
     */
    public function __construct($name, $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = "input";
        $this->setContentlessTag();

        $this->setWidth($width);
        $this->setMaxLength($maxlength);

        $this->updateAttributes(array("type" => "file"));
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLUpload($name, $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $width, $maxlength, $id, $disabled, $tabindex, $accesskey);
    }

    /**
     * Sets the width of the text box.
     * @param  int  $width  width of the text box
     */
    public function setWidth($width) {
        $width = intval($width);

        if ($width <= 0) {
            $width = 20;
        }

        $this->updateAttributes(array("size" => $width));
    }

    /**
     * Sets the maximum input length of the text box.
     * @param  int  $maxlen  maximum input length
     */
    public function setMaxLength($maxlen) {
        $maxlen = intval($maxlen);

        if ($maxlen <= 0) {
            $this->removeAttribute("maxlength");
        } else {
            $this->updateAttributes(array("maxlength" => $maxlen));
        }
    }

    /**
     * Renders the textbox
     * @return string Rendered HTML
     */
    public function toHtml() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes);
    }

}

/**
 * DIV Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLDiv extends cHTML {

    /**
     * Constructor. Creates an HTML Div element.
     *
     * @param  mixed  $content  String or object with the contents
     */
    public function __construct($content = "") {
        parent::__construct();
        $this->setContent($content);
        $this->setContentlessTag(false);
        $this->_tag = "div";
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLDiv($content = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($content);
    }

    /**
     * Sets the div's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

}

/**
 * SPAN Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLSpan extends cHTML {

    /**
     * Constructor. Creates an HTML Span element.
     *
     * @param  mixed  $content  String or object with the contents
     */
    public function __construct($content = "") {
        parent::__construct();
        $this->setContent($content);
        $this->setContentlessTag(false);
        $this->_tag = "span";
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLSpan($content = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($content);
    }

    /**
     * Sets the div's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

    /**
     * Renders the SPAN element
     * @return string Rendered HTML
     */
    public function toHTML() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes) . $this->_content . $this->fillCloseSkeleton();
    }

}

/**
 * P Element
 *
 * @author  Simon Sprankel <simon.sprankel@4fb.de>
 */
class cHTMLParagraph extends cHTML
{
    /**
     * Constructor. Creates an HTML p element.
     *
     * @param  mixed  $content  String or object with the contents
     */
    public function __construct($content = "") {
        parent::__construct();
        $this->setContent($content);
        $this->setContentlessTag(false);
        $this->_tag = "p";
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLParagraph($content = "")
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($content);
    }

    /**
     * Sets the p's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

    /**
     * Renders the SPAN element
     * @return string Rendered HTML
     */
    public function toHTML() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes) . $this->_content . $this->fillCloseSkeleton();
    }

}

/**
 * Image Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLImage extends cHTML {

    /**
     * Image source
     * @var string
     */
    protected $_src;

    /**
     * Image width
     * @var int
     */
    protected $_width;

    /**
     * Image height
     * @var int
     */
    protected $_height;

    /**
     * Constructor. Creates an HTML IMG element.
     * @param  mixed  $content  String or object with the contents
     */
    public function __construct($src = null) {
        parent::__construct();

        $this->_tag = "img";
        $this->setContentlessTag();

        $this->setBorder(0);
        $this->setSrc($src);
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLImage($src = null) {
        cDeprecated("Use __construct() instead");
        $this->__construct($src);
    }

    /**
     * Sets the image's source file
     * @param  string  $src  source location
     */
    public function setSrc($src) {
        if ($src === null) {
            $this->_src = "images/spacer.gif";
        } else {
            $this->_src = $src;
        }
    }

    /**
     * Sets the image's width
     * @param  int  $width  Image width
     */
    public function setWidth($width) {
        $this->_width = $width;
    }

    /**
     * Sets the image's height
     * @param  int  $height  Image height
     */
    public function setHeight($height) {
        $this->_height = $height;
    }

    /**
     * Sets the border size
     * @param  int  $border  Border size
     */
    public function setBorder($border) {
        $this->_border = $border;
    }

    public function setAlignment($alignment) {
        $this->updateAttributes(array("align" => $alignment));
    }

    /**
     * Apply dimensions from the source image
     */
    public function applyDimensions() {
        global $cfg;

        // Try to open the image
        list($width, $height) = @getimagesize($cfg['path']['contenido'] . $this->_src);

        if (!empty($width) && !empty($height)) {
            $this->_width = $width;
            $this->_height = $height;
        }
    }

    /**
     * Renders the IMG element
     * @return string Rendered HTML
     */
    public function toHTML() {
        $this->updateAttributes(array("src" => $this->_src));

        if (!empty($this->_width)) {
            $this->updateAttributes(array("width" => $this->_width));
        }

        if (!empty($this->_height)) {
            $this->updateAttributes(array("height" => $this->_height));
        }

        $this->updateAttributes(array("border" => $this->_border));

        return parent::toHTML();
    }

}

/**
 * Table Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTable extends cHTML {

    public function __construct() {
        parent::__construct();

        $this->_tag = "table";
        $this->setContentlessTag(false);
        $this->setPadding(0);
        $this->setSpacing(0);
        $this->setBorder(0);
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTable() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Sets the table's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

    /**
     * Sets the spacing between cells
     * @param  string  $cellspacing  Spacing
     */
    public function setCellSpacing($cellspacing) {
        $this->updateAttributes(array("cellspacing" => $cellspacing));
    }

    public function setPadding($cellpadding) {
        $this->setCellPadding($cellpadding);
    }

    public function setSpacing($cellspacing) {
        $this->setCellSpacing($cellspacing);
    }

    /**
     * Sets the padding between cells
     * @param  string  $cellpadding  Padding
     */
    public function setCellPadding($cellpadding) {
        $this->updateAttributes(array("cellpadding" => $cellpadding));
    }

    /**
     * Sets the table's border
     * @param  string  $border Border size
     */
    public function setBorder($border) {
        $this->updateAttributes(array("border" => $border));
    }

    /**
     * setWidth: Sets the table width
     *
     * @param $width Width
     *
     */
    public function setWidth($width) {
        $this->updateAttributes(array("width" => $width));
    }

}

/**
 * Table Body Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableBody extends cHTML {

    public function __construct() {
        parent::__construct();
        $this->setContentlessTag(false);
        $this->_tag = "tbody";
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableBody() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Sets the table body's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

}

/**
 * Table Row Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableRow extends cHTML {

    public function __construct() {
        parent::__construct();
        $this->setContentlessTag(false);
        $this->_tag = "tr";
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableRow() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Sets the table row's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

}

/**
 * Table Data Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableData extends cHTML {

    public function __construct() {
        parent::__construct();
        $this->setContentlessTag(false);
        $this->_tag = "td";
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableData() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Sets the table width
     * @param  string  $width Width
     */
    public function setWidth($width) {
        $this->updateAttributes(array("width" => $width));
    }

    public function setHeight($height) {
        $this->updateAttributes(array("height" => $height));
    }

    public function setAlignment($alignment) {
        $this->updateAttributes(array("align" => $alignment));
    }

    public function setVerticalAlignment($alignment) {
        $this->updateAttributes(array("valign" => $alignment));
    }

    public function setBackgroundColor($color) {
        $this->updateAttributes(array("bgcolor" => $color));
    }

    public function setColspan($colspan) {
        $this->updateAttributes(array("colspan" => $colspan));
    }

    /**
     * Sets the table data's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

    /**
     * Renders the table data element
     * @return string Rendered HTML
     */
    public function toHTML() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes) . $this->_content . $this->fillCloseSkeleton();
    }

}

/**
 * Table Head Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableHead extends cHTML {

    public function __construct() {
        parent::__construct();
        $this->setContentlessTag(false);
        $this->_tag = "th";
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableHead() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Sets the table head's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

    /**
     * Renders the table head element
     * @return string Rendered HTML
     */
    public function toHTML() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes) . $this->_content . $this->fillCloseSkeleton();
    }

}

/**
 * Table Head Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableHeader extends cHTML {

    public function __construct() {
        parent::__construct();
        $this->setContentlessTag(false);
        $this->_tag = "thead";
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLTableHeader() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Sets the table head's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

    /**
     * Renders the table head element
     * @return string Rendered HTML
     */
    public function toHTML() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes) . $this->_content . $this->fillCloseSkeleton();
    }

}

/**
 * IFrame element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLIFrame extends cHTML {

    public function __construct() {
        parent::__construct();
        $this->setContentlessTag(false);
        $this->_tag = "iframe";
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLIFrame() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Sets this frame's source
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setSrc($src) {
        $this->updateAttributes(array("src" => $src));
    }

    /**
     * Sets this frame's width
     * @param  string  $width Width of the item
     */
    public function setWidth($width) {
        $this->updateAttributes(array("width" => $width));
    }

    /**
     * Sets this frame's height
     * @param  string  $height Height of the item
     */
    public function setHeight($height) {
        $this->updateAttributes(array("height" => $height));
    }

    /**
     * Sets wether this iframe should have a border or not
     * @param  string  $border If 1 or true, this frame will have a border
     */
    public function setBorder($border) {
        $this->updateAttributes(array("frameborder" => intval($border)));
    }

    /**
     * Renders the table head element
     * @return string Rendered HTML
     */
    public function toHTML() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes) . $this->_content . $this->fillCloseSkeleton();
    }

}

class cHTMLAlignmentTable extends cHTMLTable {

    public function __construct() {
        parent::__construct();

        $this->_data = func_get_args();
        $this->setContentlessTag(false);
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLAlignmentTable() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    public function render() {
        $tr = new cHTMLTableRow();
        $td = new cHTMLTableData();

        $out = "";

        foreach ($this->_data as $data) {
            $td->setContent($data);
            $out .= $td->render();
        }

        $tr->setContent($out);

        $this->setContent($tr);

        return $this->toHTML();
    }

}

class cHTMLForm extends cHTML {

    protected $_name;
    protected $_action;
    protected $_method;

    public function __construct($name = "", $action = "main.php", $method = "post") {
        parent::__construct();
        $this->setContentlessTag(false);
        $this->_tag = "form";
        $this->_name = $name;
        $this->_action = $action;
        $this->_method = $method;
    }

    /**
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLForm() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    public function add($content) {
        $this->_content .= $content;
    }

    public function setVar($var, $value) {
        $this->_vars[$var] = $value;
    }

    /**
     * Sets the form's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

    /**
     * Renders the form element
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
        if ($this->getAttribute("name") == "") {
            $this->setAttribute("name", $this->_name);
        }
        if ($this->getAttribute("method") == "") {
            $this->setAttribute("method", $this->_method);
        }
        if ($this->getAttribute("action") == "") {
            $this->setAttribute("action", $this->_action);
        }

        $attributes = $this->getAttributes(true);

        return $this->fillSkeleton($attributes) . $out . $this->_content . $this->fillCloseSkeleton();
    }

}

/**
 * Table Head Element
 *
 * @author  Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLScript extends cHTML {

    public function __construct() {
        parent::__construct();
        $this->setContentlessTag(false);
        $this->_tag = "script";
    }

    /**
     * Sets the table head's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

    /**
     * Renders the table head element
     * @return string Rendered HTML
     */
    public function toHTML() {
        $attributes = $this->getAttributes(true);
        return $this->fillSkeleton($attributes) . $this->_content . $this->fillCloseSkeleton();
    }

}

class cHTMLList extends cHTML {

    public function __construct($type = 'ul', $id = '', $class = '', $elements = array()) {
        parent::__construct();
        $this->setContentlessTag(false);
        $this->_tag = $type;
        $this->setID($id);
        $this->setClass($class);
        $this->setContent($elements);
    }

    /**
     * Sets the list's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

}

class cHTMLListItem extends cHTML {

    public function __construct($id = '', $class = '') {
        parent::__construct();
        $this->setContentlessTag(false);
        $this->_tag = 'li';
        $this->setID($id);
        $this->setClass($class);
    }

    /**
     * Sets the list item's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

}