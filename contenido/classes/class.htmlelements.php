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
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *   modified 2010-12-28, Murat Purc, replaced cHTMLDIV/cHTMLSPAN against cHTMLDiv/cHTMLSpan
 *   modofied 2011-08-22, Timo Trautmann, removed hard coded css class text_medium with optional parameter   
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

// Global ID counter
$cHTMLIDCount = 0;

// Global generate xhtml setting
$cHTMLGenerateXHTML = null;

/**
 * Base class for all CONTENIDO HTML classes
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTML
{
	/**
	 * Storage of the open SGML tag template
	 * @var string 
	 * @access protected
	 */
	protected $_skeleton_open;

	/**
	 * Storage of a single SGML tag template
	 * @var string 
	 * @access protected
	 */
	protected $_skeleton_single;

	/**
	 * Storage of the close SGML tag
	 * @var string 
	 * @access protected
	 */
	protected $_skeleton_close;

	/**
	 * Defines which tag to use
	 * @var string 
	 * @access protected
	 */
	protected $_tag;

	/**
	 * Defines the style definitions
	 * @var string 
	 * @access protected
	 */
	protected $_styledefs;

	/**
	 * Defines all scripts which are required by the current element
	 * @var array
	 * @access protected
	 */
	protected $_requiredScripts;

	/** 
	 * Defines if the current tag is a contentless tag
	 * @var boolean
	 * @access protected
	 */
	protected $_contentlessTag;

	/**
	 * Defines which JS events contain which scripts
	 */
	protected $_aEventDefinitions;

	/**
	 * Style definitions 
	 */
	protected $_aStyleDefinitions;
	
	/**
	 * Attributes
	 */
	protected $_aAttributes;
	
	/**
	 * The content itself
	 */
	protected $_content;
	
	/**
	 * Constructor Function
	 * Initializes the SGML open/close tags
     * @param	array	$aAttributes	Associative array of table tag attributes
	 * @return 	void
	 */
	public function __construct($aAttributes = null) {
		global $cfg;
		
		$this->setAttributes($aAttributes);

		$this->_skeleton_open = '<%s%s>';
		$this->_skeleton_close = '</%s>';

        if (null === $cHTMLGenerateXHTML) {
            $cHTMLGenerateXHTML = getEffectiveSetting('generator', 'xhtml', 'false');
        }

		if ($cHTMLGenerateXHTML == 'true') {
			$this->_skeleton_single = '<%s%s />';
		} else {
			$this->_skeleton_single = '<%s%s>';
		}

		$this->_styledefs = array();
		$this->_aStyleDefinitions = array();
		$this->setContentlessTag();

		$this->advanceID();
		$this->_requiredScripts = array();
		$this->_aEventDefinitions = array();
	}
	
	/**
	* @deprecated [2012-01-19] use __construct instead
	*/
	public function cHTML() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

	public function setContentlessTag($contentlessTag = true)
	{
		$this->_contentlessTag = $contentlessTag;
	}

	/**
	 * advances to the next ID available in the system.
	 * 
	 * This function is useful if you need to use HTML elements
	 * in a loop, but don't want to re-create new objects each time.
	 *
	 * @param $class string Text to set as the "id"
	 */
	public function advanceID()
	{
		global $cHTMLIDCount;

		$cHTMLIDCount ++;
		$this->updateAttributes(array ("id" => "m".$cHTMLIDCount));
	}

	/**
	 * getID: returns the current ID
	 *
	 * @param none
	 * @return string current ID
	 */
	public function getID()
	{
		return $this->getAttribute("id");
	}
	
	/**
	 * sets the HTML tag to $tag
	 *
	 * @param: $tag string the new tag
	 */
	public function setTag($tag) {
		$this->_tag = $tag;
	}

	/**
	 * setAlt: sets the alt and title attributes
	 *
	 * Sets the "alt" and "title" tags. Usually, "alt" is used 
	 * for accessibility and "title" for mouse overs.
	 * 
	 * To set the text for all browsers for mouse over, set "alt"
	 * and "title". IE behaves incorrectly and shows "alt" on 
	 * mouse over. Mozilla browsers only show "title" as mouse over.
	 *
	 * @param $alt string Text to set as the "alt" attribute
	 */
	public function setAlt($alt)
	{
		$attributes = array ("alt" => $alt, "title" => $alt);

		$this->updateAttributes($attributes);
	}

	/**
	 * sets the ID class
	 *
	 * @param $class string Text to set as the "id"
	 */
	public function setID($id)
	{
		$this->updateAttributes(array ("id" => $id));
	}

	/**
	 * sets the CSS class
	 *
	 * @param $class string Text to set as the "alt" attribute
	 */
	public function setClass($class)
	{
		$this->updateAttributes(array ("class" => $class));
	}

	/**
	 * sets the CSS style
	 *
	 * @param $class string Text to set as the "alt" attribute
	 */
	public function setStyle($style)
	{
		$this->updateAttributes(array ("style" => $style));
	}

	/**
	 * adds an "onXXX" javascript event handler
	 *
	 * example:
	 * $item->setEvent("change","document.forms[0].submit");
	 *
	 * @param $event string Type of the event
	 * @param $action string Function or action to call (JavaScript Code)
	 */
	public function setEvent($event, $action)
	{
		if (substr($event, 0, 2) != "on")
		{
			$this->updateAttributes(array ("on".$event => $action));
		} else
		{
			$this->updateAttributes(array ($event => $action));
		}
	}

	/**
	 * removes an event handler
	 *
	 * example:
	 * $item->unsetEvent("change");
	 *
	 * @param $event string Type of the event
	 */
	public function unsetEvent($event)
	{
		if (substr($event, 0, 2) != "on")
		{
			$this->removeAttribute("on".$event);
		} else
		{
			$this->removeAttribute($event);
		}

	}

	/**
	 * fillSkeleton: Fills the open SGML tag skeleton
	 * 
	 * fillSkeleton fills the SGML opener tag with the
	 * specified attributes. Attributes need to be passed
	 * in the stringyfied variant.
	 *
	 * @param $attributes string Attributes to set
	 * @return string filled SGML opener skeleton
	 */
	public function fillSkeleton($attributes)
	{
		if ($this->_contentlessTag == true)
		{
			return sprintf($this->_skeleton_single, $this->_tag, $attributes);
		} else
		{
			return sprintf($this->_skeleton_open, $this->_tag, $attributes);
		}

	}

	/**
	 * fillCloseSkeleton: Fills the close skeleton
	 *
	 * @param none
	 * @return string filled SGML closer skeleton
	 */
	public function fillCloseSkeleton()
	{
		return sprintf($this->_skeleton_close, $this->_tag);
	}

	/**
	 * addStyleDefinition
	 *
	 * @deprecated name change, use attachStyleDefinition
	 * @param $entity string Entity to define
	 * @param $definition string Definition for the given entity 
	 * @return string filled SGML closing skeleton
	 */
	public function setStyleDefinition($entity, $definition)
	{
        cDeprecated("Use attachStyleDefinition instead");
		$this->_styledefs[$entity] = $definition;
	}
	
	/**
	 * attachStyleDefinition: Attaches a style definition.
	 * 
	 * This function is not restricted to a single style, e.g.
	 * you can set multiple style definitions as-is to the handler.
	 * 
	 * $example->attachStyle("myIdentifier",
	 * 			"border: 1px solid black; white-space: nowrap");
	 * $example->attachStyle("myIdentifier2",
	 * 						"padding: 0px");
	 * 
	 * Results in:
	 * 
	 * style="border: 1px solid black; white-space: nowrap; padding: 0px;"
	 *
	 * @param $sName   		string Name for a style definition
	 * @param $sDefinition 	string Definition for the given entity 
	 * @return string filled SGML closing skeleton
	 */
	public function attachStyleDefinition($sName, $sDefinition)
	{
		$this->_aStyleDefinitions[$sName] = $sDefinition;
	}	

	public function addRequiredScript($script)
	{
		if (!is_array($this->_requiredScripts))
		{
			$this->_requiredScripts = array ();
		}

		$this->_requiredScripts[] = $script;

		$this->_requiredScripts = array_unique($this->_requiredScripts);
	}

	/**
	 * _setContent: Sets the content of the object
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	protected function _setContent($content)
	{
		$this->setContentlessTag(false);
		/* Is it an array? */
		if (is_array($content))
		{
			unset ($this->_content);
			
			$this->_content = "";
			
			foreach ($content as $item)
			{
				if (is_object($item))
				{
					if (method_exists($item, "render"))
					{
						$this->_content .= $item->render();
					}

					if (count($item->_requiredScripts) > 0)
					{
						$this->_requiredScripts = array_merge($this->_requiredScripts, $item->_requiredScripts);
					}
				} else
				{
					$this->_content .= $item;
				}
			}
		} else
		{
			if (is_object($content))
			{
				if (method_exists($content, "render"))
				{
					$this->_content = $content->render();
				}

				if (count($content->_requiredScripts) > 0)
				{
					$this->_requiredScripts = array_merge($this->_requiredScripts, $content->_requiredScripts);
				}

				return;
			} else
			{
				$this->_content = $content;
			}
		}

	}

	/**
	 * attachEventDefinition: Attaches the code for an event
	 * 
	 * Example to attach an onClick handler:
	 * setEventDefinition("foo", "onClick", "alert('foo');");
	 * 
	 * @param $sName string defines the name of the event
	 * @param $sEvent string defines the event (e.g. onClick)
	 * @param $sCode string defines the code
	 *
	 * @return 	void
	 */
	public function attachEventDefinition($sName, $sEvent, $sCode)
	{
		$this->_aEventDefinitions[strtolower($sEvent)][$sName] = $sCode;

	}

	/**
	 * setAttribte: Sets a specific attribute
	 * 
	 * @param $sAttributeName string Name of the attribute
	 * @param $sValue string Value of the attribute
	 *
	 * @return	void
	 */
	public function setAttribute($sAttributeName, $sValue) {
        $sAttributeName = strtolower($sAttributeName);
        if (is_null($sValue)) {
            $sValue = $sAttributeName;
        }
        $this->_aAttributes[$sAttributeName] = $sValue;
    }

    /**
     * Sets the HTML attributes
     * @param  	array   $aAttributes	Associative array with attributes
     * @return	void
     */
    public function setAttributes($aAttributes) {
        $this->_aAttributes = $this->_parseAttributes($aAttributes);
    }
	
	/**
     * Returns a valid atrributes array.
	 *
     * @param    array   $aAttributes	Associative array with attributes
	 *
     * @return   array
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
	 *
     * @param	string	$sAttributeName	Attribute name
	 *
     * @return	void
     */
    public function removeAttribute($sAttributeName) {
        $attr = strtolower($sAttributeName);
		
        if (isset($this->_aAttributes[$sAttributeName])) {
            unset($this->_aAttributes[$sAttributeName]);
        }
    }
	
	/**
     * Returns the value of the given attribute.
     *
     * @param	string	$sAttributeName	Attribute name
	 *
     * @return	string|null	Returns null if the attribute does not exist
     */
    public function getAttribute($sAttributeName) {
        $sAttributeName = strtolower($sAttributeName);
		
        if (isset($this->_aAttributes[$sAttributeName])) {
            return $this->_aAttributes[$sAttributeName];
        }
		
        return null;
    }

    /**
     * Updates the passed attributes without changing the other existing attributes
	 *
     * @param	array	$aAttributes	Associative array with attributes
     * 
	 * @return	void
     */
    public function updateAttributes($aAttributes) {
		$aAttributes = $this->_parseAttributes($aAttributes);
	
		foreach ($aAttributes as $sKey => $sValue) {
			$this->aAttributes[$sKey] = $sValue;
		}
    }
	
	/**
     * Returns an HTML formatted attribute string
	 *
     * @param	array	$aAttributes	Associative array with attributes
     * 
     * @return	string	Attrbiute string in HTML format
     */
    protected function _getAttrString($aAttributes) {
        $sAttrString = '';

        if (!is_array($aAttributes)) {
			return '';
		}

		foreach ($aAttributes as $sKey => $sValue) {
            $sAttrString .= ' ' . $sKey . '="' . $sValue . '"';
        }
		
        return $sAttrString;
    }

    /**
     * Returns the assoc array (default) or string of attributes
     *
     * @param	bool	Whether to return the attributes as string
	 *
     * @return	array|string	attributes
     */
    public function getAttributes($bReturnAsString = false) {
        if ($bReturnAsString) {
            return $this->_getAttrString($this->_aAttributes);
        } else {
            return $this->_aAttributes;
        }
    }

	/**
	 * Renders the output
	 * @return	string	rendered output
	 */
	public function toHTML()
	{
		/* Fill style definition */
		$style = $this->getAttribute("style");

		/* If the style doesn't end with a semicolon, append one */
		if (is_string($style))
		{
			$style = trim($style);

			if (substr($style, strlen($style) - 1) != ";")
			{
				$style .= ";";
			}
		}
		
		foreach ($this->_aStyleDefinitions as $sEntry)
		{
			$style .= $sEntry;
			
			if (substr($style, strlen($style) - 1) != ";")
			{
				$style .= ";";
			}			
		}

		
		foreach ($this->_aEventDefinitions as $sEventName => $sEntry)
		{
			$aFullCode = array();
			
			foreach ($sEntry as $sName => $sCode)
			{
				$aFullCode[] = $sCode;
			}

			$this->setAttribute($sEventName, $this->getAttribute($sEventName).implode(" ", $aFullCode));
		}

		/* Apply all stored styles */
		foreach ($this->_styledefs as $key => $value)
		{
			$style .= "$key: $value;";
		}

		if ($style != "")
		{
			$this->setStyle($style);
		}

		if ($this->_content != "" || $this->_contentlessTag == false)
		{
			$attributes = $this->getAttributes(true);

			return $this->fillSkeleton($attributes).$this->_content.$this->fillCloseSkeleton();
		} else
		{
			/* This is a single style tag */
			$attributes = $this->getAttributes(true);

			return $this->fillSkeleton($attributes);
		}
	}

	/**
	 * render(): Alias for toHtml
	 * @return string Rendered HTML
	 */
	public function render()
	{
		return $this->toHtml();
	}
	
	/**
	 * Direct call of object as string will return its rendered HTML code.
	 * @return string Rendered HTML
	 */
	public function __toString() {
		return $this->render();
	}
	
	/**
     * Displays the HTML to the screen
	 * @return	void
     */
    public function display() {
        echo $this->render();
    }
}

/**
 * HTML Form element class
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLFormElement extends cHTML
{

	/**
	 * Constructor. This is a generic form element, where
	 * specific elements should be inherited from this class.
	 *
	 * @param $name string Name of the element 
	 * @param $id string ID of the element
	 * @param $disabled string Item disabled flag (non-empty to set disabled)
	 * @param $tabindex string Tab index for form elements
	 * @param $accesskey string Key to access the field
	 *
	 * @return none
	 */
	public function __construct($name = "", $id = "", $disabled = "", $tabindex = "", $accesskey = "", $class = "text_medium")
	{
		parent::__construct();

		$this->updateAttributes(array ("name" => $name));

		if (is_string($id) && !empty ($id))
		{
			$this->updateAttributes(array ("id" => $id));
		}

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
	 * @param $disabled string Sets the disabled-flag if non-empty
	 * @return none
	 */
	public function setDisabled($disabled)
	{
		if (!empty ($disabled))
		{
			$this->updateAttributes(array ("disabled" => "disabled"));
		} else
		{
			$this->removeAttribute("disabled");
		}
	}

	/**
	 * sets the tab index for this element. The tab
	 * index needs to be numeric, bigger than 0 and smaller than 32767.
		 *
	 * @param $tabindex int desired tab index
	 * @return none
	 */
	public function setTabindex($tabindex)
	{
		if (is_numeric($tabindex) && $tabindex >= 0 && $tabindex <= 32767)
		{
			$this->updateAttributes(array ("tabindex" => $tabindex));
		}
	}

	/**
	 * sets the access key for this element.
		 *
	 * @param $accesskey string The length of the access key. May be A-Z and 0-9.
	 * @return none
	 */
	public function setAccessKey($accesskey)
	{
		if ((strlen($accesskey) == 1) && is_alphanumeric($accesskey))
		{
			$this->updateAttributes(array ("accesskey" => $accesskey));
		} else
		{
			$this->removeAttribute("accesskey");
		}
	}
}

/**
 * HTML Hidden Field
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLHiddenField extends cHTMLFormElement
{

	/**
	 * Constructor. Creates an HTML hidden field.
	 *
	 * @param $name string Name of the element
	 * @param $value string Title of the button
	 * @param $id string ID of the element
	 *
	 * @return none
	 */
	function __construct($name, $value = "", $id = "")
	{
		parent::__construct($name, $id);
		$this->setContentlessTag();
		$this->updateAttributes(array ("type" => "hidden"));
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
	 * @param $value string Value of the field
	 * @return none
	 */
	public function setValue($value)
	{
		$this->updateAttributes(array ("value" => $value));
	}

	/**
	 * Renders the hidden field
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHtml()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes);
	}

}
/**
 * HTML Button class
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLButton extends cHTMLFormElement
{

	/**
	 * Constructor. Creates an HTML button.
	 *
	 * Creates a submit button by default, can be changed
	 * using setMode.
	 *
	 * @param $name string Name of the element
	 * @param $title string Title of the button
	 * @param $id string ID of the element
	 * @param $disabled string Item disabled flag (non-empty to set disabled)
	 * @param $tabindex string Tab index for form elements
	 * @param $accesskey string Key to access the field
	 *
	 * @return none
	 */
	function __construct($name, $title = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "", $mode = "submit")
	{
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
	 * @param $title string The title to set
	 * @return none
	 */
	public function setTitle($title)
	{
		$this->updateAttributes(array ("value" => $title));
	}

	/**
	 * Sets the mode (submit or reset) for the button
		 *
	 * @param $mode string Either "submit", "reset" or "image".
	 * @return boolean Returns false if failed to set the mode
	 */
	public function setMode($mode)
	{

		switch ($mode)
		{
			case "submit" :
			case "reset" :
				$this->updateAttributes(array ("type" => $mode));
				break;
			case "image" :
				$this->updateAttributes(array ("type" => $mode));
				break;
			case "button" :
				$this->updateAttributes(array ("type" => $mode));
				break;
			default :
				return false;
		}
	}

	/**
	 * Set the image src if mode type is "image"
		 *
	 * @param $mode string image path.
	 * @return void
	 */
	public function setImageSource($src)
	{
		$this->updateAttributes(array ("src" => $src));
	}

	/**
	 * Renders the button
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHtml()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes);
	}

}

/**
 * HTML Textbox
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTextbox extends cHTMLFormElement
{

	/**
	 * Constructor. Creates an HTML text box.
	 *
	 * If no additional parameters are specified, the
	 * default width is 20 units.
	 *
	 * @param $name string Name of the element
	 * @param $initvalue string Initial value of the box
	 * @param $width int width of the text box
	 * @param $maxlength int maximum input length of the box
	 * @param $id string ID of the element
	 * @param $disabled string Item disabled flag (non-empty to set disabled)
	 * @param $tabindex string Tab index for form elements
	 * @param $accesskey string Key to access the field
	 *
	 * @return none
	 */
	public function __construct($name, $initvalue = "", $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "")
	{
		parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        
		$this->_tag = "input";
		$this->setContentlessTag();
		$this->setValue($initvalue);

		$this->setWidth($width);
		$this->setMaxLength($maxlength);

		$this->updateAttributes(array ("type" => "text"));
	}
	
	
	/**
	* @deprecated [2012-01-19] use __construct instead
	*/
	public function cHTMLTextbox($name, $initvalue = "", $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $initvalue, $width, $maxlength, $id, $disabled, $tabindex, $accesskey);
    }
    
    
	/**
	 * sets the width of the text box.
	 *
	 * @param $width int width of the text box
	 *
	 * @return none
	 */
	public function setWidth($width)
	{
		$width = intval($width);

		if ($width <= 0)
		{
			$width = 50;
		}

		$this->updateAttributes(array ("size" => $width));
	}

	/**
	 * sets the maximum input length of the text box.
	 *
	 * @param $maxlen int maximum input length
	 *
	 * @return none
	 */
	public function setMaxLength($maxlen)
	{
		$maxlen = intval($maxlen);

		if ($maxlen <= 0)
		{
			$this->removeAttribute("maxlength");
		} else
		{
			$this->updateAttributes(array ("maxlength" => $maxlen));
		}
	}

	/**
	 * sets the initial value of the text box.
	 *
	 * @param $value string Initial value
	 *
	 * @return none
	 */
	public function setValue($value)
	{
		$this->updateAttributes(array ("value" => $value));
	}
}

/**
 * HTML Password Box
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLPasswordbox extends cHTMLFormElement
{

	/**
	 * Constructor. Creates an HTML password box.
	 *
	 * If no additional parameters are specified, the
	 * default width is 20 units.
	 *
	 * @param $name string Name of the element
	 * @param $initvalue string Initial value of the box
	 * @param $width int width of the text box
	 * @param $maxlength int maximum input length of the box
	 * @param $id string ID of the element
	 * @param $disabled string Item disabled flag (non-empty to set disabled)
	 * @param $tabindex string Tab index for form elements
	 * @param $accesskey string Key to access the field
	 *
	 * @return none
	 */
	public function __construct($name, $initvalue = "", $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "")
	{
		parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "input";
		$this->setValue($initvalue);

		$this->setWidth($width);
		$this->setMaxLength($maxlength);

		$this->updateAttributes(array ("type" => "password"));
	}
	
	/**
	* @deprecated [2012-01-19] use __construct instead
	*/
	public function cHTMLPasswordbox($name, $initvalue = "", $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $initvalue, $width, $maxlength, $id, $disabled, $tabindex, $accesskey);
    }

	/**
	 * sets the width of the text box.
	 *
	 * @param $width int width of the text box
	 *
	 * @return none
	 */
	public function setWidth($width)
	{
		$width = intval($width);

		if ($width <= 0)
		{
			$width = 20;
		}

		$this->updateAttributes(array ("size" => $width));

	}

	/**
	 * sets the maximum input length of the text box.
	 *
	 * @param $maxlen int maximum input length
	 *
	 * @return none
	 */
	public function setMaxLength($maxlen)
	{
		$maxlen = intval($maxlen);

		if ($maxlen <= 0)
		{
			$this->removeAttribute("maxlength");
		} else
		{
			$this->updateAttributes(array ("maxlength" => $maxlen));
		}

	}

	/**
	 * sets the initial value of the text box.
	 *
	 * @param $value string Initial value
	 *
	 * @return none
	 */
	public function setValue($value)
	{
		$this->updateAttributes(array ("value" => $value));
	}
}

class cHTMLTextarea extends cHTMLFormElement
{
	protected $_value;

	/**
	 * Constructor. Creates an HTML text area.
	 *
	 * If no additional parameters are specified, the
	 * default width is 60 chars, and the height is 5 chars.
	 *
	 * @param $name string Name of the element
	 * @param $initvalue string Initial value of the textarea
	 * @param $width int width of the textarea
	 * @param $height int height of the textarea
	 * @param $id string ID of the element
	 * @param $disabled string Item disabled flag (non-empty to set disabled)
	 * @param $tabindex string Tab index for form elements
	 * @param $accesskey string Key to access the field
	 *
	 * @return none
	 */
	public function __construct($name, $initvalue = "", $width = "", $height = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "")
	{
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
	 * sets the width of the text box.
	 *
	 * @param $width int width of the text box
	 *
	 * @return none
	 */
	public function setWidth($width)
	{
		$width = intval($width);

		if ($width <= 0)
		{
			$width = 50;
		}

		$this->updateAttributes(array ("cols" => $width));

	}

	/**
	 * sets the maximum input length of the text box.
	 *
	 * @param $maxlen int maximum input length
	 *
	 * @return none
	 */
	public function setHeight($height)
	{
		$height = intval($height);

		if ($height <= 0)
		{
			$height = 5;
		}

		$this->updateAttributes(array ("rows" => $height));

	}

	/**
	 * sets the initial value of the text box.
	 *
	 * @param $value string Initial value
	 *
	 * @return none
	 */
	public function setValue($value)
	{
		$this->_value = $value;
	}

	/**
	 * Renders the textbox
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHtml()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->_value.$this->fillCloseSkeleton();
	}

}
/**
 * HTML Label for form elements
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLLabel extends cHTML
{

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
	 * @param $text string Name of the element
	 * @param $for string ID of the form element to link to.
	 *
	 * @return none
	 */
	public function __construct($text, $for)
	{
		parent::__construct();
		$this->_tag = "label";
		$this->setContentlessTag(false);
		$this->updateAttributes(array ("for" => $for));
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
	public function toHtml()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->text.$this->fillCloseSkeleton();
	}

}

/**
 * HTML Select Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLSelectElement extends cHTMLFormElement
{
	/**
	 * All cHTMLOptionElements
	 * @var array
	 */
	protected $_options;

	/**
	 * Constructor. Creates an HTML select field (aka "DropDown").
	 *
	 * @param $name string Name of the element
	 * @param $width int width of the select element
	 * @param $id string ID of the element
	 * @param $disabled string Item disabled flag (non-empty to set disabled)
	 * @param $tabindex string Tab index for form elements
	 * @param $accesskey string Key to access the field
	 *
	 * @return none
	 */
	public function __construct($name, $width = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "")
	{
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
	 *					array("value","title"),
	 *					array("value","title"));
	 * 
	 * or regular key => value arrays.
	 *
	 * @param $stuff array Array with all items
	 *
	 * @return none
	 */
	public function autoFill($stuff)
	{
		if (is_array($stuff))
		{
			foreach ($stuff as $key => $row)
			{
				if (is_array($row))
				{
					$option = new cHTMLOptionElement($row[1], $row[0]);
					$this->addOptionElement($row[0], $option);
				} else
				{
					$option = new cHTMLOptionElement($row, $key);
					$this->addOptionElement($key, $option);
				}
			}
		}
	}

	/**
	 * Adds an cHTMLOptionElement to the number of choices.
	 *
	 * @param $index string Index of the element
	 * @param $element object Filled cHTMLOptionElement to add
	 *
	 * @return none
	 */
	public function addOptionElement($index, $element)
	{
		$this->_options[$index] = $element;
	}

	public function setMultiselect()
	{
		$this->updateAttributes(array ("multiple" => "multiple"));
	}

	public function setSize($size)
	{
		$this->updateAttributes(array ("size" => $size));
	}

	/**
	 * Sets a specific cHTMLOptionElement to the selected
	 * state. 
	 *
	 * @param $lvalue string Specifies the "value" of the cHTMLOptionElement to set
	 *
	 * @return none
	 */
	public function setDefault($lvalue)
	{
		$bSet = false;

		if (is_array($this->_options))
		{
			foreach ($this->_options as $key => $value)
			{
				if (strcmp($value->getAttribute("value"), $lvalue) == 0)
				{
					$value->setSelected(true);
					$this->_options[$key] = $value;
					$bSet = true;
				} else
				{
					$value->setSelected(false);
					$this->_options[$key] = $value;
				}
			}
		}

		if ($bSet == false)
		{
			if (is_array($this->_options))
			{
				foreach ($this->_options as $key => $value)
				{
					$value->setSelected(true);
					$this->_options[$key] = $value;
					return;
				}
			}
		}
	}

	/**
	 * Search for the selected elements
	 *
	 * @param none
	 *
	 * @return Selected "lvalue"
	 */
	public function getDefault()
	{
		if (is_array($this->_options))
		{
			foreach ($this->_options as $key => $value)
			{
				if ($value->isSelected())
				{
					return $key;
				}
			}
		}
		return false;
	}
	
	/**
	 * Sets specified elements as selected (and all others as unselected)
	 *
	 * @param array		$aElements Array with "values" of the cHTMLOptionElement to set
	 *
	 * @return none
	 */
	public function setSelected($aElements)
	{
		if (is_array($this->_options) && is_array($aElements))
		{
			foreach ($this->_options as $sKey => $oOption)
			{
				if (in_array($oOption->getAttribute("value"), $aElements))
				{
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
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHtml()
	{

		$attributes = $this->getAttributes(true);

		$options = "";

		if (is_array($this->_options))
		{
			foreach ($this->_options as $key => $value)
			{
				$options .= $value->toHtml();
			}
		}

		return ($this->fillSkeleton($attributes).$options.$this->fillCloseSkeleton());
	}

}

/**
 * HTML Select Option Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLOptionElement extends cHTMLFormElement
{
	/**
	 * Title to display
	 * @var string 
	 * @access protected
	 */
	protected $_title;

	/**
	 * Constructor. Creates an HTML option element.
	 *
	 * @param $title string Displayed title of the element
	 * @param $value string Value of the option
	 * @param $selected boolean If true, element is selected
	 * @param $disabled boolean If true, element is disabled
	 *
	 * @return none
	 */
	public function __construct($title, $value, $selected = false, $disabled = false)
	{
		cHTML :: __construct();
		$this->_tag = "option";
		$this->_title = $title;

		$this->updateAttributes(array ("value" => $value));
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
	 * sets the selected flag
	 *
	 * @param $selected boolean If true, adds the "selected" attribute 
	 *
	 * @return none
	 */
	public function setSelected($selected)
	{
		if ($selected == true)
		{
			$this->updateAttributes(array ("selected" => "selected"));
		} else
		{
			$this->removeAttribute("selected");
		}
	}

	/**
	 * sets the selected flag
	 *
	 * @param $selected boolean If true, adds the "selected" attribute 
	 *
	 * @return none
	 */
	public function isSelected()
	{
		if ($this->getAttribute("selected") == "selected")
		{
			return true;
		} else
		{
			return false;
		}
	}

	/**
	 * sets the disabled flag
	 *
	 * @param $disabled boolean If true, adds the "disabled" attribute 
	 *
	 * @return none
	 */

	public function setDisabled($disabled)
	{
		if ($disabled == true)
		{
			$this->updateAttributes(array ("disabled" => "disabled"));
		} else
		{
			$this->removeAttribute("disabled");
		}
	}

	/**
	 * Renders the option element. Note:
	 * the cHTMLSelectElement renders the options by itself.
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHtml()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->_title.$this->fillCloseSkeleton();
	}

}

/**
 * HTML Radio Button
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLRadiobutton extends cHTMLFormElement
{
	/**
	 * Values for the check box
	 * @var string 
	 * @access protected
	 */
	protected $_value;

	/**
	 * Constructor. Creates an HTML radio button element.
	 *
	 * @param $name string Name of the element
	 * @param $value string Value of the radio button
	 * @param $id string ID of the element
	 * @param $checked boolean Is element checked?
	 * @param $disabled string Item disabled flag (non-empty to set disabled)
	 * @param $tabindex string Tab index for form elements
	 * @param $accesskey string Key to access the field
	 *
	 * @return none
	 */
	public function __construct($name, $value, $id = "", $checked = false, $disabled = false, $tabindex = null, $accesskey = "")
	{
		parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "input";
		$this->_value = $value;
		$this->setContentlessTag();

		$this->setChecked($checked);
		$this->updateAttributes(array ("type" => "radio"));
		$this->updateAttributes(array ("value" => $value));
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
	 *
	 * @param $checked boolean If true, the "checked" attribute will be assigned.
	 *
	 * @return none
	 */
	public function setChecked($checked)
	{
		if ($checked == true)
		{
			$this->updateAttributes(array ("checked" => "checked"));
		} else
		{
			$this->removeAttribute("checked");
		}
	}

	/**
	 * Sets a custom label text
	 *
	 * @param $text string Text to display
	 *
	 * @return none
	 */
	public function setLabelText($text)
	{
		$this->_labelText = $text;
	}

	/**
	 * Renders the option element. Note:
	 *
	 * If this element has an ID, the value (which equals the text displayed)
	 * will be rendered as seperate HTML label, if not, it will be displayed
	 * as regular text. Displaying the value can be turned off via the parameter.
		 *
	 * @param $renderlabel boolean If true, renders a label 
	 *
	 * @return string Rendered HTML
	 */
	public function toHtml($renderLabel = true)
	{
		$attributes = $this->getAttributes(true);

		if ($renderLabel == false)
		{
			return $this->fillSkeleton($attributes);
		}

		$id = $this->getAttribute("id");

		$renderedLabel = "";

		if ($id != "")
		{
			$label = new cHTMLLabel($this->_value, $this->getAttribute("id"));

			if ($this->_labelText != "")
			{
				$label->text = $this->_labelText;
			}

			$renderedLabel = $label->toHtml();
		} else
		{
			$renderedLabel = $this->_value;
		}

		return $this->fillSkeleton($attributes).$renderedLabel;
	}

}

/**
 * HTML Checkbox
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLCheckbox extends cHTMLFormElement
{
	protected $_value;

	/**
	 * Constructor. Creates an HTML checkbox element.
	 *
	 * @param $name string Name of the element
	 * @param $value string Value of the radio button
	 * @param $id string ID of the element
	 * @param $checked boolean Is element checked?
	 * @param $disabled string Item disabled flag (non-empty to set disabled)
	 * @param $tabindex string Tab index for form elements
	 * @param $accesskey string Key to access the field
	 *
	 * @return none
	 */
	public function __construct($name, $value, $id = "", $checked = false, $disabled = false, $tabindex = null, $accesskey = "")
	{

		parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "input";
		$this->_value = $value;
		$this->setContentlessTag();

		$this->setChecked($checked);
		$this->updateAttributes(array ("type" => "checkbox"));
		$this->updateAttributes(array ("value" => $value));
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
	 *
	 * @param $checked boolean If true, the "checked" attribute will be assigned.
	 *
	 * @return none
	 */
	public function setChecked($checked)
	{
		if ($checked == true)
		{
			$this->updateAttributes(array ("checked" => "checked"));
		} else
		{
			$this->removeAttribute("checked");
		}
	}

	/**
	 * Sets a custom label text
	 *
	 * @param $text string Text to display
	 *
	 * @return none
	 */
	public function setLabelText($text)
	{
		$this->_labelText = $text;
	}

	/**
	 * Renders the checkbox element. Note:
	 *
	 * If this element has an ID, the value (which equals the text displayed)
	 * will be rendered as seperate HTML label, if not, it will be displayed
	 * as regular text. Displaying the value can be turned off via the parameter.
		 *
	 * @param $renderlabel boolean If true, renders a label 
	 *
	 * @return string Rendered HTML
	 */
	public function toHtml($renderlabel = true)
	{
		$id = $this->getAttribute("id");

		$renderedLabel = "";

		if ($renderlabel == true)
		{
			if ($id != "")
			{
				$label = new cHTMLLabel($this->_value, $this->getAttribute("id"));

				$label->setClass($this->getAttribute("class"));

				if ($this->_labelText != "")
				{
					$label->text = $this->_labelText;
				}

				$renderedLabel = $label->toHtml();
			} else
			{

				$renderedLabel = $this->_value;

				if ($this->_labelText != "")
				{
					$label = new cHTMLLabel($this->_value, $this->getAttribute("id"));
					$label->text = $this->_labelText;
					$renderedLabel = $label->toHtml();
				}

			}

			return '<table border="0" cellspacing="0" cellpadding="0"><tr><td nowrap="nowrap">'.parent::toHTML().'</td><td nowrap="nowrap">'.$renderedLabel.'</td></tr></table>';
		} else
		{
			return parent::toHTML();
		}

	}

}

/**
 * HTML File upload box
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLUpload extends cHTMLFormElement
{

	/**
	 * Constructor. Creates an HTML upload box.
	 *
	 * If no additional parameters are specified, the
	 * default width is 20 units.
	 *
	 * @param $name string Name of the element
	 * @param $initvalue string Initial value of the box
	 * @param $width int width of the text box
	 * @param $maxlength int maximum input length of the box
	 * @param $id string ID of the element
	 * @param $disabled string Item disabled flag (non-empty to set disabled)
	 * @param $tabindex string Tab index for form elements
	 * @param $accesskey string Key to access the field
	 *
	 * @return none
	 */
	function __construct($name, $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "")
	{
		parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "input";
		$this->setContentlessTag();

		$this->setWidth($width);
		$this->setMaxLength($maxlength);

		$this->updateAttributes(array ("type" => "file"));
	}

	/**
	* @deprecated [2012-01-19] use __construct instead
	*/
	public function cHTMLUpload($name, $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($name, $width, $maxlength, $id, $disabled, $tabindex, $accesskey);
    }

	/**
	 * sets the width of the text box.
	 *
	 * @param $width int width of the text box
	 *
	 * @return none
	 */
	public function setWidth($width)
	{
		$width = intval($width);

		if ($width <= 0)
		{
			$width = 20;
		}

		$this->updateAttributes(array ("size" => $width));

	}

	/**
	 * sets the maximum input length of the text box.
	 *
	 * @param $maxlen int maximum input length
	 *
	 * @return none
	 */
	public function setMaxLength($maxlen)
	{
		$maxlen = intval($maxlen);

		if ($maxlen <= 0)
		{
			$this->removeAttribute("maxlength");
		} else
		{
			$this->updateAttributes(array ("maxlength" => $maxlen));
		}

	}

	/**
	 * Renders the textbox
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHtml()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes);
	}

}

/**
 * HTML Link
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLLink extends cHTML
{
	/* Stores the link location */
	protected $_link;

	/* Stores the content */
	protected $_content;

	/* Stores the anchor */
	protected $_anchor;

	/* Stores the custom entries */
	protected $_custom;

	/**
	 * Constructor. Creates an HTML link.
	 *
	 * @param $href String with the location to link to
	 *
	 */
	public function __construct($href = "")
	{
		global $sess;
		parent::__construct();

		$this->setLink($href);
		$this->setContentlessTag(false);
		$this->_tag = "a";

		/* Check for backend */
		if (is_object($sess))
		{
			if ($sess->classname == "Contenido_Session")
			{
				$this->enableAutomaticParameterAppend();
			}
		}
	}
	
	/**
	* @deprecated [2012-01-19] use __construct instead
	*/
	public function cHTMLLink($href = "") {
        cDeprecated("Use __construct() instead");
        $this->__construct($href);
    }
    
	public function enableAutomaticParameterAppend()
	{
		$this->setEvent("click", 'var doit = true; try { var i = get_registered_parameters() } catch (e) { doit = false; }; if (doit == true) { this.href += i; }');
	}

	public function disableAutomaticParameterAppend()
	{
		$this->unsetEvent("click");
	}

	/**
	 * setLink: Sets the link to a specific location
	 *
	 * @param $href String with the location to link to
	 *
	 */
	public function setLink($href)
	{
		$this->_link = $href;
		$this->_type = "link";

		if (strpos($href, "javascript:") !== false)
		{
			$this->disableAutomaticParameterAppend();
		}
	}

	/**
	 * setTargetFrame: Sets the target frame
	 *
	 * @param $target string Target frame identifier
	 *
	 */
	public function setTargetFrame($target)
	{
		$this->updateAttributes(array ("target" => $target));
	}

	/**
	 * setLink: Sets a CONTENIDO link (area, frame, action)
	 *
	 * @param $targetarea 	string	Target backend area
	 * @param $targetframe 	string	Target frame (1-4)
	 * @param $targetaction string	Target action
	 */
	public function setCLink($targetarea, $targetframe, $targetaction = "")
	{
		$this->_targetarea = $targetarea;
		$this->_targetframe = $targetframe;
		$this->_targetaction = $targetaction;
		$this->_type = "clink";
	}

	/**
	 * setMultiLink: Sets a multilink
	 *
	 * @param $righttoparea      string Area   (right top)
	 * @param $righttopaction    string Action (right top)
	 * @param $rightbottomarea   string Area   (right bottom)
	 * @param $rightbottomaction string Action (right bottom)
	 */
	public function setMultiLink($righttoparea, $righttopaction, $rightbottomarea, $rightbottomaction)
	{
		$this->_targetarea = $righttoparea;
		$this->_targetframe = 3;
		$this->_targetaction = $righttopaction;
		$this->_targetarea2 = $rightbottomarea;
		$this->_targetframe2 = 4;
		$this->_targetaction2 = $rightbottomaction;
		$this->_type = "multilink";
	}

	/**
	 * setCustom: Sets a custom attribute to be appended to the link
	 *
	 * @param $key  	string	Parameter name
	 * @param $value	string	Parameter value
	 */
	public function setCustom($key, $value)
	{
		$this->_custom[$key] = $value;
	}

	public function getHref()
	{
		global $sess;

		if (is_array($this->_custom))
		{
			$custom = "";

			foreach ($this->_custom as $key => $value)
			{
				$custom .= "&$key=$value";
			}
		}

		if ($this->_anchor)
		{
			$anchor = "#".$this->_anchor;
		} else
		{
			$anchor = "";
		}

		switch ($this->_type)
		{
			case "link" :
				$custom = "";
				if (is_array($this->_custom))
				{
					foreach ($this->_custom as $key => $value)
					{
						if ($custom == "")
						{
							$custom .= "?$key=$value";
						} else
						{
							$custom .= "&$key=$value";
						}
					}
				}

				return $this->_link.$custom.$anchor;
				break;
			case "clink" :
				$this->disableAutomaticParameterAppend();
				return 'main.php?area='.$this->_targetarea.'&frame='.$this->_targetframe.'&action='.$this->_targetaction.$custom."&contenido=".$sess->id.$anchor;
				break;
			case "multilink" :
				$this->disableAutomaticParameterAppend();
				$tmp_mstr = 'javascript:conMultiLink(\'%s\',\'%s\',\'%s\',\'%s\');';
				$mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=".$this->_targetarea."&frame=".$this->_targetframe."&action=".$this->_targetaction.$custom), 'right_bottom', $sess->url("main.php?area=".$this->_targetarea2."&frame=".$this->_targetframe2."&action=".$this->_targetaction2.$custom));
				return $mstr;
				break;
		}

	}

	/**
	 * setAnchor: Sets an anchor
	 *
	 * Only works for the link types Link and cLink.
	 *
	 * @param $content string Anchor name
	 *
	 */
	public function setAnchor($anchor)
	{
		$this->_anchor = $anchor;
	}

	/**
	 * setContent: Sets the link's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the link
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHTML()
	{
		$this->updateAttributes(array ("href" => $this->getHref()));

		return parent::toHTML();
	}

}

/**
 * DIV Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLDiv extends cHTML
{
	/**
	 * Constructor. Creates an HTML Div element.
	 *
	 * @param $content mixed String or object with the contents
	 */
	public function __construct($content = "")
	{
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
	 * setContent: Sets the div's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}
}

/**
 * SPAN Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLSpan extends cHTML
{
	/**
	 * Constructor. Creates an HTML Span element.
	 *
	 * @param $content mixed String or object with the contents
	 */
	public function __construct($content = "")
	{
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
	 * setContent: Sets the div's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the SPAN element
	 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHTML()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->_content.$this->fillCloseSkeleton();
	}
}

/**
 * Image Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLImage extends cHTML
{
	/**
	 * Image source
	 * @var string 
	 * @access protected
	 */
	protected $_src;

	/**
	 * Image width
	 * @var int 
	 * @access protected
	 */
	protected $_width;

	/**
	 * Image height
	 * @var int
	 * @access protected
	 */
	protected $_height;

	/**
	 * Constructor. Creates an HTML IMG element.
	 *
	 * @param $content mixed String or object with the contents
	 *
	 */
	public function __construct($src = NULL)
	{
		parent::__construct();

		$this->_tag = "img";
		$this->setContentlessTag();

		$this->setBorder(0);
		$this->setSrc($src);
	}

	/**
	* @deprecated [2012-01-19] use __construct instead
	*/
	public function cHTMLImage($src = NULL) {
        cDeprecated("Use __construct() instead");
        $this->__construct($src);
    }
    
	/**
	 * setSrc: Sets the image's source file
	 *
	 * @param $src string source location
	 *
	 */
	public function setSrc($src)
	{
		if ($src === NULL)
		{
			$this->_src = "images/spacer.gif";
		} else
		{
			$this->_src = $src;
		}
	}

	/**
	 * setWidth: Sets the image's width
	 *
	 * @param $width int Image width
	 *
	 */
	public function setWidth($width)
	{
		$this->_width = $width;
	}

	/**
	 * setHeight: Sets the image's height
	 *
	 * @param $height int Image height
	 *
	 */
	public function setHeight($height)
	{
		$this->_height = $height;
	}

	/**
	 * setBorder: Sets the border size
	 *
	 * @param $border int Border size
	 *
	 */
	public function setBorder($border)
	{
		$this->_border = $border;
	}

	public function setAlignment($alignment)
	{
		$this->updateAttributes(array ("align" => $alignment));
	}

	/**
	 * applyDimensions: Apply dimensions from the source image
	 *
	 * @param none
	 *
	 */
	public function applyDimensions()
	{
		global $cfg;

		/* Try to open the image */
		list ($width, $height) = @ getimagesize($cfg['path']['contenido'].$this->_src);

		if (!empty ($width) && !empty ($height))
		{
			$this->_width = $width;
			$this->_height = $height;

		}
	}

	/**
	 * Renders the IMG element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHTML()
	{
		$this->updateAttributes(array ("src" => $this->_src));

		if (!empty ($this->_width))
		{
			$this->updateAttributes(array ("width" => $this->_width));
		}

		if (!empty ($this->_height))
		{
			$this->updateAttributes(array ("height" => $this->_height));
		}

		$this->updateAttributes(array ("border" => $this->_border));

		return parent::toHTML();
	}

}

/**
 * Table Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTable extends cHTML
{
	public function __construct()
	{
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
	 * setContent: Sets the table's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * setCellSpacing: Sets the spacing between cells
	 *
	 * @param $cellspacing Spacing
	 *
	 */
	public function setCellSpacing($cellspacing)
	{
		$this->updateAttributes(array ("cellspacing" => $cellspacing));
	}

	public function setPadding($cellpadding)
	{
		$this->setCellPadding($cellpadding);
	}

	public function setSpacing($cellspacing)
	{
		$this->setCellSpacing($cellspacing);
	}

	/**
	 * setCellPadding: Sets the padding between cells
	 *
	 * @param $cellpadding Padding
	 *
	 */
	public function setCellPadding($cellpadding)
	{
		$this->updateAttributes(array ("cellpadding" => $cellpadding));
	}

	/**
	 * setBorder: Sets the table's border
	 *
	 * @param border Border size
	 *
	 */
	public function setBorder($border)
	{
		$this->updateAttributes(array ("border" => $border));
	}

	/**
	 * setWidth: Sets the table width
	 *
	 * @param $width Width
	 *
	 */
	public function setWidth($width)
	{
		$this->updateAttributes(array ("width" => $width));
	}
}

/**
 * Table Body Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableBody extends cHTML
{
	public function __construct()
	{
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
	 * setContent: Sets the table body's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}
}

/**
 * Table Row Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableRow extends cHTML
{
	public function __construct()
	{
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
	 * setContent: Sets the table row's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}
}

/**
 * Table Data Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableData extends cHTML
{
	public function __construct()
	{
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
	 * setWidth: Sets the table width
	 *
	 * @param $width Width
	 *
	 */
	public function setWidth($width)
	{
		$this->updateAttributes(array ("width" => $width));
	}

	public function setHeight($height)
	{
		$this->updateAttributes(array ("height" => $height));
	}

	public function setAlignment($alignment)
	{
		$this->updateAttributes(array ("align" => $alignment));
	}

	public function setVerticalAlignment($alignment)
	{
		$this->updateAttributes(array ("valign" => $alignment));
	}

	public function setBackgroundColor($color)
	{
		$this->updateAttributes(array ("bgcolor" => $color));
	}

	public function setColspan($colspan)
	{
		$this->updateAttributes(array ("colspan" => $colspan));
	}

	/**
	 * setContent: Sets the table data's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the table data element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHTML()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->_content.$this->fillCloseSkeleton();
	}

}

/**
 * Table Head Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableHead extends cHTML
{
	public function __construct()
	{
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
	 * setContent: Sets the table head's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the table head element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHTML()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->_content.$this->fillCloseSkeleton();
	}
}

/**
 * Table Head Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableHeader extends cHTML
{
	public function __construct()
	{
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
	 * setContent: Sets the table head's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the table head element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHTML()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->_content.$this->fillCloseSkeleton();
	}
}

/**
 * IFrame element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLIFrame extends cHTML
{
	public function __construct()
	{
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
	 * setSrc: Sets this frame's source
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	public function setSrc($src)
	{
		$this->updateAttributes(array ("src" => $src));
	}

	/**
	 * setWidth: Sets this frame's width
	 *
	 * @param $width Width of the item
	 *
	 */
	public function setWidth($width)
	{
		$this->updateAttributes(array ("width" => $width));
	}

	/**
	 * setHeight: Sets this frame's height
	 *
	 * @param $height Height of the item
	 *
	 */
	public function setHeight($height)
	{
		$this->updateAttributes(array ("height" => $height));
	}

	/**
	 * setBorder: Sets wether this iframe should have a border or not
	 *
	 * @param $border If 1 or true, this frame will have a border
	 *
	 */
	public function setBorder($border)
	{
		$this->updateAttributes(array ("frameborder" => intval($border)));
	}

	/**
	 * Renders the table head element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHTML()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->_content.$this->fillCloseSkeleton();
	}
}

class cHTMLAlignmentTable extends cHTMLTable
{
	public function __construct()
	{
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
    
	public function render()
	{
		$tr = new cHTMLTableRow;
		$td = new cHTMLTableData;

		$out = "";

		foreach ($this->_data as $data)
		{
			$td->setContent($data);
			$out .= $td->render();
		}

		$tr->setContent($out);

		$this->setContent($tr);

		return $this->toHTML();
	}
}

class cHTMLForm extends cHTML
{
	public function __construct()
	{
		parent::__construct();
		$this->setContentlessTag(false);
		$this->_tag = "form";
	}

	/**
	* @deprecated [2012-01-19] use __construct instead
	*/
	public function cHTMLForm() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
    
	public function setVar($var, $value)
	{
		$this->_vars[$var] = $value;
	}

	/**
	 * setContent: Sets the form's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the form element
	 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHTML()
	{
		$out = '';
                foreach ($this->_vars as $var => $value)
		{
			$f = new cHTMLHiddenField($var, $value);
			$out .= $f->render();
		}

		$attributes = $this->getAttributes(true);

		return $this->fillSkeleton($attributes).$out.$this->_content.$this->fillCloseSkeleton();
	}
}

/**
 * Table Head Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLScript extends cHTML
{
	public function __construct()
	{
		parent::__construct();
		$this->setContentlessTag(false);
		$this->_tag = "script";
	}

	/**
	 * setContent: Sets the table head's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	public function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the table head element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	public function toHTML()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->_content.$this->fillCloseSkeleton();
	}
}
?>
