<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * HTML elements
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.6.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-08-21
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *
 *   $Id: class.htmlelements.php 469 2008-07-02 09:44:45Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if (!class_exists("HTML_Common"))
{
	cInclude("pear", "HTML/Common.php");
}

/* Global ID counter */
$cHTMLIDCount = 0;

/**
 * Base class for all Contenido HTML classes
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTML extends HTML_Common
{
	/**
	 * Storage of the open SGML tag template
	 * @var string 
	 * @access private
	 */
	var $_skeleton_open;

	/**
	 * Storage of a single SGML tag template
	 * @var string 
	 * @access private
	 */
	var $_skeleton_single;

	/**
	 * Storage of the close SGML tag
	 * @var string 
	 * @access private
	 */
	var $_skeleton_close;

	/**
	 * Defines which tag to use
	 * @var string 
	 * @access private
	 */
	var $_tag;

	/**
	 * Defines the style definitions
	 * @var string 
	 * @access private
	 */
	var $_styledefs;

	/**
	 * Defines all scripts which are required by the current element
	 * @var array
	 * @access private
	 */
	var $_requiredScripts;

	/** 
	 * Defines if the current tag is a contentless tag
	 * @var boolean
	 * @access private
	 */
	var $_contentlessTag;

	/**
	 * Defines which JS events contain which scripts
	 */
	var $_aEventDefinitions;

	/**
	 * Style definitions 
	 */
	var $_aStyleDefinitions;
	
	/**
	 * The content itself
	 */
	var $_content;
	
	/**
	 * Constructor Function
	 * Initializes the SGML open/close tags
	 * @param none
	 */
	function cHTML()
	{
		global $cfg;

		HTML_Common :: HTML_Common();
		$this->_skeleton_open = '<%s%s>';
		$this->_skeleton_close = '</%s>';

		
		/* Cache the XHTML setting for performance reasons */
		if (!is_array($cfg) || !array_key_exists("generate_xhtml", $cfg))
		{
			if (function_exists("getEffectiveSetting"))
			{
				$cfg["generate_xhtml"] = getEffectiveSetting("generator", "xhtml", false);
			} else {
				$cfg["generate_xhtml"] = false;	
			}
		}

		if ($cfg["generate_xhtml"] === "false")
		{
			$cfg["generate_xhtml"] = false;
		}
		
		if ($cfg["generate_xhtml"] == true)
		{
			$this->_skeleton_single = '<%s%s />';
		} else
		{
			$this->_skeleton_single = '<%s%s>';
		}

		$this->_styledefs = array ();
		$this->_aStyleDefinitions = array();
		$this->setContentlessTag();

		$this->advanceID();
		$this->_requiredScripts = array ();
		$this->_aEventDefinitions = array ();
	}

	function setContentlessTag($contentlessTag = true)
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
	function advanceID()
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
	function getID()
	{
		return $this->getAttribute("id");
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
	function setAlt($alt)
	{
		$attributes = array ("alt" => $alt, "title" => $alt);

		$this->updateAttributes($attributes);
	}

	/**
	 * sets the ID class
	 *
	 * @param $class string Text to set as the "id"
	 */
	function setID($id)
	{
		$this->updateAttributes(array ("id" => $id));
	}

	/**
	 * sets the CSS class
	 *
	 * @param $class string Text to set as the "alt" attribute
	 */
	function setClass($class)
	{
		$this->updateAttributes(array ("class" => $class));
	}

	/**
	 * sets the CSS style
	 *
	 * @param $class string Text to set as the "alt" attribute
	 */
	function setStyle($style)
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
	function setEvent($event, $action)
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
	function unsetEvent($event)
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
	function fillSkeleton($attributes)
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
	function fillCloseSkeleton()
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
	function setStyleDefinition($entity, $definition)
	{
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
	function attachStyleDefinition($sName, $sDefinition)
	{
		$this->_aStyleDefinitions[$sName] = $sDefinition;
	}	

	function addRequiredScript($script)
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
	function _setContent($content)
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
	 */
	function attachEventDefinition($sName, $sEvent, $sCode)
	{
		$this->_aEventDefinitions[strtolower($sEvent)][$sName] = $sCode;

	}

	/**
	 * setAttribte: Sets a specific attribute
	 * 
	 * @param $sAttributeName string Name of the attribute
	 * @param $sValue string Value of the attribute
	 */
	function setAttribute($sAttributeName, $sValue)
	{
		$this->updateAttributes(array ($sAttributeName => $sValue));
	}

	/**
	 * Renders the output
	 * If the tag 
	 */
	function toHTML()
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
	 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function render()
	{
		return $this->toHtml();
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
	function cHTMLFormElement($name = "", $id = "", $disabled = "", $tabindex = "", $accesskey = "")
	{
		cHTML :: cHTML();

		$this->updateAttributes(array ("name" => $name));

		if (is_string($id) && !empty ($id))
		{
			$this->updateAttributes(array ("id" => $id));
		}

		$this->setClass("text_medium"); // TODO: Remove this...
		$this->setDisabled($disabled);
		$this->setTabindex($tabindex);
		$this->setAccessKey($accesskey);
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
	function setDisabled($disabled)
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
	function setTabindex($tabindex)
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
	function setAccessKey($accesskey)
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
	function cHTMLHiddenField($name, $value = "", $id = "")
	{
		cHTMLFormElement :: cHTMLFormElement($name, $id);
		$this->setContentlessTag();
		$this->updateAttributes(array ("type" => "hidden"));
		$this->_tag = "input";

		$this->setValue($value);
	}

	/**
	 * Sets the value for the field
		 *
	 * @param $value string Value of the field
	 * @return none
	 */
	function setValue($value)
	{
		$this->updateAttributes(array ("value" => $value));
	}

	/**
	 * Renders the hidden field
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHtml()
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
	function cHTMLButton($name, $title = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "", $mode = "submit")
	{
		cHTMLFormElement :: cHTMLFormElement($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "input";
		$this->setContentlessTag();
		$this->setTitle($title);
		$this->setMode($mode);
	}

	/**
	 * Sets the title (caption) for the button
		 *
	 * @param $title string The title to set
	 * @return none
	 */
	function setTitle($title)
	{
		$this->updateAttributes(array ("value" => $title));
	}

	/**
	 * Sets the mode (submit or reset) for the button
		 *
	 * @param $mode string Either "submit", "reset" or "image".
	 * @return boolean Returns false if failed to set the mode
	 */
	function setMode($mode)
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
	function setImageSource($src)
	{
		$this->updateAttributes(array ("src" => $src));
	}

	/**
	 * Renders the button
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHtml()
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
	function cHTMLTextbox($name, $initvalue = "", $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "")
	{
		cHTMLFormElement :: cHTMLFormElement($name, $id, $disabled, $tabindex, $accesskey);
        
		$this->_tag = "input";
		$this->setContentlessTag();
		$this->setValue($initvalue);

		$this->setWidth($width);
		$this->setMaxLength($maxlength);

		$this->updateAttributes(array ("type" => "text"));
	}

	/**
	 * sets the width of the text box.
	 *
	 * @param $width int width of the text box
	 *
	 * @return none
	 */
	function setWidth($width)
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
	function setMaxLength($maxlen)
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
	function setValue($value)
	{
		$this->updateAttributes(array ("value" => $value));
	}

	/**
	 * Renders the textbox
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHtml()
	{
		return parent::toHtml();
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
	function cHTMLPasswordbox($name, $initvalue = "", $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "")
	{
		cHTMLFormElement :: cHTMLFormElement($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "input";
		$this->setValue($initvalue);

		$this->setWidth($width);
		$this->setMaxLength($maxlength);

		$this->updateAttributes(array ("type" => "password"));
	}

	/**
	 * sets the width of the text box.
	 *
	 * @param $width int width of the text box
	 *
	 * @return none
	 */
	function setWidth($width)
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
	function setMaxLength($maxlen)
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
	function setValue($value)
	{
		$this->updateAttributes(array ("value" => $value));
	}

	/**
	 * Renders the textbox
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHtml()
	{
		return parent::toHTML();
	}

}

class cHTMLTextarea extends cHTMLFormElement
{
	var $_value;

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
	function cHTMLTextarea($name, $initvalue = "", $width = "", $height = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "")
	{
		cHTMLFormElement :: cHTMLFormElement($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "textarea";
		$this->setValue($initvalue);
		$this->setContentlessTag(false);
		$this->setWidth($width);
		$this->setHeight($height);
	}

	/**
	 * sets the width of the text box.
	 *
	 * @param $width int width of the text box
	 *
	 * @return none
	 */
	function setWidth($width)
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
	function setHeight($height)
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
	function setValue($value)
	{
		$this->_value = $value;
	}

	/**
	 * Renders the textbox
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHtml()
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
	var $text;

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
	function cHTMLLabel($text, $for)
	{
		cHTML :: cHTML();
		$this->_tag = "label";
		$this->setContentlessTag(false);
		$this->updateAttributes(array ("for" => $for));
		$this->text = $text;

	}

	/**
	 * Renders the label
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHtml()
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
	var $_options;

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
	function cHTMLSelectElement($name, $width = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "")
	{
		cHTMLFormElement :: cHTMLFormElement($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "select";
		$this->setContentlessTag(false);
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
	function autoFill($stuff)
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
	function addOptionElement($index, $element)
	{
		$this->_options[$index] = $element;
	}

	function setMultiselect()
	{
		$this->updateAttributes(array ("multiple" => "multiple"));
	}

	function setSize($size)
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
	function setDefault($lvalue)
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
	function getDefault()
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
	function setSelected($aElements)
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
	function toHtml()
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
	 * @access private
	 */
	var $_title;

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
	function cHTMLOptionElement($title, $value, $selected = false, $disabled = false)
	{
		cHTML :: cHTML();
		$this->_tag = "option";
		$this->_title = $title;

		$this->updateAttributes(array ("value" => $value));
		$this->setContentlessTag(false);

		$this->setSelected($selected);
		$this->setDisabled($disabled);
	}

	/**
	 * sets the selected flag
	 *
	 * @param $selected boolean If true, adds the "selected" attribute 
	 *
	 * @return none
	 */
	function setSelected($selected)
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
	function isSelected()
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

	function setDisabled($disabled)
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
	function toHtml()
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
	 * @access private
	 */
	var $_value;

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
	function cHTMLRadiobutton($name, $value, $id = "", $checked = false, $disabled = false, $tabindex = null, $accesskey = "")
	{
		cHTMLFormElement :: cHTMLFormElement($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "input";
		$this->_value = $value;
		$this->setContentlessTag();

		$this->setChecked($checked);
		$this->updateAttributes(array ("type" => "radio"));
		$this->updateAttributes(array ("value" => $value));
	}

	/**
	 * Sets the checked flag.
	 *
	 * @param $checked boolean If true, the "checked" attribute will be assigned.
	 *
	 * @return none
	 */
	function setChecked($checked)
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
	function setLabelText($text)
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
	function toHtml($renderLabel = true)
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
	var $_value;

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
	function cHTMLCheckbox($name, $value, $id = "", $checked = false, $disabled = false, $tabindex = null, $accesskey = "")
	{

		cHTMLFormElement :: cHTMLFormElement($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "input";
		$this->_value = $value;
		$this->setContentlessTag();

		$this->setChecked($checked);
		$this->updateAttributes(array ("type" => "checkbox"));
		$this->updateAttributes(array ("value" => $value));
	}

	/**
	 * Sets the checked flag.
	 *
	 * @param $checked boolean If true, the "checked" attribute will be assigned.
	 *
	 * @return none
	 */
	function setChecked($checked)
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
	function setLabelText($text)
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
	function toHtml($renderlabel = true)
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
	function cHTMLUpload($name, $width = "", $maxlength = "", $id = "", $disabled = false, $tabindex = null, $accesskey = "")
	{
		cHTMLFormElement :: cHTMLFormElement($name, $id, $disabled, $tabindex, $accesskey);
		$this->_tag = "input";
		$this->setContentlessTag();

		$this->setWidth($width);
		$this->setMaxLength($maxlength);

		$this->updateAttributes(array ("type" => "file"));
	}

	/**
	 * sets the width of the text box.
	 *
	 * @param $width int width of the text box
	 *
	 * @return none
	 */
	function setWidth($width)
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
	function setMaxLength($maxlen)
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
	function toHtml()
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
	var $_link;

	/* Stores the content */
	var $_content;

	/* Stores the anchor */
	var $_anchor;

	/* Stores the custom entries */
	var $_custom;

	/**
	 * Constructor. Creates an HTML link.
	 *
	 * @param $href String with the location to link to
	 *
	 */
	function cHTMLLink($href = "")
	{
		global $sess;
		cHTML :: cHTML();

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

	function enableAutomaticParameterAppend()
	{
		$this->setEvent("click", 'var doit = true; try { var i = get_registered_parameters() } catch (e) { doit = false; }; if (doit == true) { this.href += i; }');
	}

	function disableAutomaticParameterAppend()
	{
		$this->unsetEvent("click");
	}

	/**
	 * setLink: Sets the link to a specific location
	 *
	 * @param $href String with the location to link to
	 *
	 */
	function setLink($href)
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
	function setTargetFrame($target)
	{
		$this->updateAttributes(array ("target" => $target));
	}

	/**
	 * setLink: Sets a Contenido link (area, frame, action)
	 *
	 * @param $targetarea 	string	Target backend area
	 * @param $targetframe 	string	Target frame (1-4)
	 * @param $targetaction string	Target action
	 */
	function setCLink($targetarea, $targetframe, $targetaction = "")
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
	function setMultiLink($righttoparea, $righttopaction, $rightbottomarea, $rightbottomaction)
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
	function setCustom($key, $value)
	{
		$this->_custom[$key] = $value;
	}

	function getHref()
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
	function setAnchor($anchor)
	{
		$this->_anchor = $anchor;
	}

	/**
	 * setContent: Sets the link's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the link
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
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
class cHTMLDIV extends cHTML
{
	/**
	 * Constructor. Creates an HTML DIV element.
	 *
	 * @param $content mixed String or object with the contents
	 *
	 */
	function cHTMLDIV($content = "")
	{
		cHTML :: cHTML();
		$this->setContent($content);
		$this->setContentlessTag(false);
		$this->_tag = "div";
	}

	/**
	 * setContent: Sets the div's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the DIV element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
	{
		return parent::toHTML();
	}
}

/**
 * SPAN Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLSPAN extends cHTML
{
	/**
	 * Constructor. Creates an HTML DIV element.
	 *
	 * @param $content mixed String or object with the contents
	 *
	 */
	function cHTMLSPAN($content = "")
	{
		cHTML :: cHTML();
		$this->setContent($content);
		$this->setContentlessTag(false);
		$this->_tag = "span";
	}

	/**
	 * setContent: Sets the div's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the SPAN element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
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
	 * @access private
	 */
	var $_src;

	/**
	 * Image width
	 * @var int 
	 * @access private
	 */
	var $_width;

	/**
	 * Image height
	 * @var int
	 * @access private
	 */
	var $_height;

	/**
	 * Constructor. Creates an HTML IMG element.
	 *
	 * @param $content mixed String or object with the contents
	 *
	 */
	function cHTMLImage($src = NULL)
	{
		cHTML :: cHTML();

		$this->_tag = "img";
		$this->setContentlessTag();

		$this->setBorder(0);
		$this->setSrc($src);
	}

	/**
	 * setSrc: Sets the image's source file
	 *
	 * @param $src string source location
	 *
	 */
	function setSrc($src)
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
	function setWidth($width)
	{
		$this->_width = $width;
	}

	/**
	 * setHeight: Sets the image's height
	 *
	 * @param $height int Image height
	 *
	 */
	function setHeight($height)
	{
		$this->_height = $height;
	}

	/**
	 * setBorder: Sets the border size
	 *
	 * @param $border int Border size
	 *
	 */
	function setBorder($border)
	{
		$this->_border = $border;
	}

	function setAlignment($alignment)
	{
		$this->updateAttributes(array ("align" => $alignment));
	}

	/**
	 * applyDimensions: Apply dimensions from the source image
	 *
	 * @param none
	 *
	 */
	function applyDimensions()
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
	function toHTML()
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
	function cHTMLTable()
	{
		cHTML :: cHTML();

		$this->_tag = "table";
		$this->setContentlessTag(false);
		$this->setPadding(0);
		$this->setSpacing(0);
		$this->setBorder(0);
	}

	/**
	 * setContent: Sets the table's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * setCellSpacing: Sets the spacing between cells
	 *
	 * @param $cellspacing Spacing
	 *
	 */
	function setCellSpacing($cellspacing)
	{
		$this->updateAttributes(array ("cellspacing" => $cellspacing));
	}

	function setPadding($cellpadding)
	{
		$this->setCellPadding($cellpadding);
	}

	function setSpacing($cellspacing)
	{
		$this->setCellSpacing($cellspacing);
	}

	/**
	 * setCellPadding: Sets the padding between cells
	 *
	 * @param $cellpadding Padding
	 *
	 */
	function setCellPadding($cellpadding)
	{
		$this->updateAttributes(array ("cellpadding" => $cellpadding));
	}

	/**
	 * setBorder: Sets the table's border
	 *
	 * @param border Border size
	 *
	 */
	function setBorder($border)
	{
		$this->updateAttributes(array ("border" => $border));
	}

	/**
	 * setWidth: Sets the table width
	 *
	 * @param $width Width
	 *
	 */
	function setWidth($width)
	{
		$this->updateAttributes(array ("width" => $width));
	}

	/**
	 * Renders the Table element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
	{
		return parent::toHTML();
	}
}

/**
 * Table Body Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableBody extends cHTML
{
	function cHTMLTableBody()
	{
		cHTML :: cHTML();
		$this->setContentlessTag(false);
		$this->_tag = "tbody";
	}

	/**
	 * setContent: Sets the table body's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the table body element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
	{
		return parent::toHTML();
	}
}

/**
 * Table Row Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableRow extends cHTML
{
	function cHTMLTableRow()
	{
		cHTML :: cHTML();
		$this->setContentlessTag(false);
		$this->_tag = "tr";
	}

	/**
	 * setContent: Sets the table row's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the table row element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
	{
		return parent::toHTML();
	}
}

/**
 * Table Data Element
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cHTMLTableData extends cHTML
{
	function cHTMLTableData()
	{
		cHTML :: cHTML();
		$this->setContentlessTag(false);
		$this->_tag = "td";
	}

	/**
	 * setWidth: Sets the table width
	 *
	 * @param $width Width
	 *
	 */
	function setWidth($width)
	{
		$this->updateAttributes(array ("width" => $width));
	}

	function setHeight($height)
	{
		$this->updateAttributes(array ("height" => $height));
	}

	function setAlignment($alignment)
	{
		$this->updateAttributes(array ("align" => $alignment));
	}

	function setVerticalAlignment($alignment)
	{
		$this->updateAttributes(array ("valign" => $alignment));
	}

	function setBackgroundColor($color)
	{
		$this->updateAttributes(array ("bgcolor" => $color));
	}

	function setColspan($colspan)
	{
		$this->updateAttributes(array ("colspan" => $colspan));
	}

	/**
	 * setContent: Sets the table data's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the table data element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
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
	function cHTMLTableHead()
	{
		cHTML :: cHTML();
		$this->setContentlessTag(false);
		$this->_tag = "th";
	}

	/**
	 * setContent: Sets the table head's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the table head element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
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
	function cHTMLTableHeader()
	{
		cHTML :: cHTML();
		$this->setContentlessTag(false);
		$this->_tag = "thead";
	}

	/**
	 * setContent: Sets the table head's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the table head element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
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
	function cHTMLIFrame()
	{
		cHTML :: cHTML();
		$this->setContentlessTag(false);
		$this->_tag = "iframe";
	}

	/**
	 * setSrc: Sets this frame's source
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setSrc($src)
	{
		$this->updateAttributes(array ("src" => $src));
	}

	/**
	 * setWidth: Sets this frame's width
	 *
	 * @param $width Width of the item
	 *
	 */
	function setWidth($width)
	{
		$this->updateAttributes(array ("width" => $width));
	}

	/**
	 * setHeight: Sets this frame's height
	 *
	 * @param $height Height of the item
	 *
	 */
	function setHeight($height)
	{
		$this->updateAttributes(array ("height" => $height));
	}

	/**
	 * setBorder: Sets wether this iframe should have a border or not
	 *
	 * @param $border If 1 or true, this frame will have a border
	 *
	 */
	function setBorder($border)
	{
		$this->updateAttributes(array ("frameborder" => intval($border)));
	}

	/**
	 * Renders the table head element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->_content.$this->fillCloseSkeleton();
	}
}

class cHTMLAlignmentTable extends cHTMLTable
{
	function cHTMLAlignmentTable()
	{
		cHTMLTable :: cHTMLTable();

		$this->_data = func_get_args();
		$this->setContentlessTag(false);
	}

	function render()
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
	function cHTMLForm()
	{
		cHTML :: cHTML();
		$this->setContentlessTag(false);
		$this->_tag = "form";
	}

	function setVar($var, $value)
	{
		$this->_vars[$var] = $value;
	}

	/**
	 * setContent: Sets the form's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the form element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
	{
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
	function cHTMLScript()
	{
		cHTML :: cHTML();
		$this->setContentlessTag(false);
		$this->_tag = "script";
	}

	/**
	 * setContent: Sets the table head's content
	 *
	 * @param $content string/object String with the content or an object to render.
	 *
	 */
	function setContent($content)
	{
		$this->_setContent($content);
	}

	/**
	 * Renders the table head element
		 *
	 * @param none
	 * @return string Rendered HTML
	 */
	function toHTML()
	{
		$attributes = $this->getAttributes(true);
		return $this->fillSkeleton($attributes).$this->_content.$this->fillCloseSkeleton();
	}
}
?>