<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Button Widgets
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.12
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2005-08-22
 *   
 *   $Id: class.widgets.buttons.php,v 1.6 2005/08/22 12:20:23 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


/**
 * Regular push button with hover and push effect
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cWidgetButton extends cHTMLSPAN
{
	
	/**
	 * Storage of the image object
     * @var object
     * @access private
	 */	
	var $_img;
	
	/**
	 * Storage of the link object
     * @var object
     * @access private
	 */	
	var $_link;
	
	/**
     * Constructor Function
	 *
     * @param $img string	Image location
     * @param $alt string	Alternative text
	 * @param $link string	Link
     */		
	function cWidgetButton ($img, $alt, $link)
	{
		cHTMLSPAN::cHTMLSPAN();
		
		$this->_img = new cHTMLImage($img);
		$this->_link = new cHTMLLink($link);
		
		$this->_img->setAlt($alt);
		$this->setBorder(1);
	}

	/**
     * Sets wether the button should be indented when clicking
	 *
     * @param $indent boolean True if indenting should be used
     */	
	function setIndent ($indent)
	{
		$this->_indent = $indent;
	}
	
	/**
     * Sets wether the button should be hovered
	 *
     * @param $hover boolean True if hovering should be used
     */	
	function setHover ($hover)
	{
		$this->_hover = $hover;
	}	
	
	/**
     * Render method
	 *
     * @param none
     */	
	function render ()
	{
		$regularState  = "this.style.backgroundColor='#F1F1F1';";
		$regularState .= "this.style.borderColor = '#999999 #B3B3B3 #B3B3B3 #E8E8EE';";

		$toggleState   = "this.style.backgroundColor='#CCCCCC';";
		$toggleState  .= "this.style.borderColor = '#666666 #E8E8EE #E8E8EE #B3B3B3';";
		
		if ($this->_indent == true)
		{
			$regularState .= "this.style.padding = '1px'";
			$toggleState  .= "this.style.padding = '2px 0px 0px 2px'";
		}
		
		if ($this->_hover == true)
		{
			$hoverState    = "this.style.backgroundColor='#D7D7E7'";
			$this->_img->setEvent("mouseover", $hoverState);
		}
		
		$this->_img->setEvent("mouseout", $regularState);
		$this->_img->setEvent("mousedown", $toggleState);
		$this->_img->setEvent("mouseup", $regularState);
		
		$this->_link->setContent($this->_img->render());
		
		$this->setContent($this->_link->render());
		
		return cHTMLSPAN::render();	
	}
	
	/**
     * sets the border width
	 * 
	 * @param $border integer border size
     */		
	function setBorder ($border)
	{
		if ($border == 0)
		{
    		$this->_img->setStyleDefinition("margin", "0px");
    		$this->_img->setStyleDefinition("padding", "0px");
    		$this->_img->setStyleDefinition("border", "0px");
		} else {
    		$this->_img->setStyleDefinition("margin", "0px");
    		$this->_img->setStyleDefinition("padding", "1px");
    		$this->_img->setStyleDefinition("border", $border."px");
    		$this->_img->setStyleDefinition("border-style", "solid");
    		$this->_img->setStyleDefinition("border-color", "#E8E8EE #747488 #747488 #E8E8EE");
		}
	}
	
	
}

/**
 * Toggle button (on/off) with two different links
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cWidgetToggleButton extends cWidgetButton
{
	
	/**
     * Constructor Function
	 *
     * @param $img string	Image location
     * @param $alt string	Alternative text
	 * @param $uplink string Link when the button is turned off (=up)
	 * @param $downlink string Link when the button is turned on (=down)
     */		
	function cWidgetToggleButton ($img, $alt, $uplink, $downlink)
	{
		cHTMLSPAN::cHTMLSPAN();
		
		$this->_img = new cHTMLImage($img);
		$this->_link = new cHTMLLink("#");
		
		$this->_img->setAlt($alt);
	}
	
	/**
     * Render method
	 *
     * @param none
     */	
	function render ()
	{
		
		$regularState  = "this.style.backgroundColor='#C6C6D5';";
		$regularState .= "this.style.borderColor = '#E8E8EE #747488 #747488 #E8E8EE';";
		
		$downState  = "this.style.backgroundColor='#B1B1BE';";
		$downState .= "this.style.borderColor = '#747488 #E8E8EE #E8E8EE #747488';";
		
		$toggleState   = "this.style.backgroundColor='#A7A7B3';";
		$toggleState  .= "this.style.borderColor = '#747488 #E8E8EE #E8E8EE #747488';";

		if ($this->_hover == true)
		{
			$hoverState    = "this.style.backgroundColor='#D7D7E7';";
		}
		
		if ($this->_indent == true)
		{
    		$downState .= "this.style.padding = '2px 0px 0px 2px';";
    		$regularState .= "this.style.padding = '1px';";
    		$toggleState  .= "this.style.padding = '2px 0px 0px 2px';";
		}				

		
		


		$linkid = $this->_link->getID();
		
		$linkup = "document.getElementById('$linkid').href = '$uplink';";
		$linkdown = "document.getElementById('$linkid').href = '$downlink';";
		
		$this->_img->setEvent("click", "this.toggle = !this.toggle; if (!this.toggle) { $regularState $linkup } else { $downState $linkdown};");
		$this->_img->setEvent("mousedown", $toggleState);
		$this->_img->setEvent("mouseover", $hoverState);
		$this->_img->setEvent("mouseout", "if (!this.toggle) { $regularState } else { $downState };");
		
		$this->_link->setContent($this->_img->render());
		
		$this->setContent($this->_link->render());
		
		return cHTMLSPAN::render();	
	}	
	
}

/**
 * Toggle buttons for creating a grouped set of buttons
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cWidgetMultiToggleButton extends cWidgetButton
{
	/**
     * Constructor Function
	 *
     * @param $img string	Image location
     * @param $alt string	Alternative text
	 * @param $lnik string  Link to call when the button is clicked
     */	
	function cWidgetMultiToggleButton ($img, $alt, $link)
	{
		cHTMLSPAN::cHTMLSPAN();
		
		$this->_img = new cHTMLImage($img);
		$this->_link = new cHTMLLink($link);
		$this->_img->setAlt($alt);
		$this->_linkedItems = Array();
		$this->_default = false;
		$this->setHint("","");
	}

	/**
     * links to another cWidgetMultiToggleButton (or any derived class)
	 * for automatically updating other buttons.
	 *
     * @param $item mixed Either another cWidgetMultiToggleButton (or derivates) or the ID of the target button.
     */	
	function addLinkedItem ($item)
	{
		if (is_object($item))
		{
			if (is_a($item, "cWidgetMultiToggleButton"))
			{
				$id = $item->_img->getID();	
				$this->addLinkedItem($id);
			}
			
		} else {
    		if (!in_array($item, $this->_linkedItems))
    		{
    			$this->_linkedItems[] = "'$item'";	
    		}
		}	
	}

	/**
     * Sets or deletes the default state for this button.
	 * Warning: This method doesn't update other buttons.
	 *
     * @param $default boolean If true, this button is a default (e.g. pushed) button.
     */		
	function setDefault ($default = true)
	{
		$this->_default = $default;	
	}

	/**
     * Sets that the specified object receives a hint whenever the button is hovered.
	 *
     * @param $object string ID of the object which receives the hint
     * @param $hint   string Text to display
     */		
	function setHint ($object, $hint)
	{
		$this->_hinttext = $hint;
		$this->_hintobject= $object;
	}

	/**
     * Render method
	 *
     * @param none
     */	
	function render ()
	{
		/* Retrieve the image id for this button */
		$imgid = $this->_img->getID();
		
		/* Initialize the default state if desired */
		if ($this->_default)
		{
    		$this->_img->setStyleDefinition("border-color", "#747488 #E8E8EE #E8E8EE #747488");	
    		$this->_img->setStyleDefinition("background", "#A7A7B3");
    		$this->_img->setStyleDefinition("padding", "2px 0px 0px 2px");
    		$defaultscript = '<script language="javascript"> document.getElementById("'.$imgid.'").toggle = 1;</script>';
		}
		
		$regularState  = "this.style.backgroundColor='#C6C6D5';";
		$regularState .= "this.style.borderColor = '#E8E8EE #747488 #747488 #E8E8EE';";
		$regularState .= "this.style.padding = '1px';";

		$downState  = "this.style.backgroundColor='#B1B1BE';";
		$downState .= "this.style.borderColor = '#747488 #E8E8EE #E8E8EE #747488';";
		$downState .= "this.style.padding = '2px 0px 0px 2px';";
				
		$hoverState    = "this.style.backgroundColor='#D7D7E7';";
		
		/* Hint renderer */
		if ($this->_hinttext != "" && $this->_hintobject != "")
		{
			/* htmldecode was added to avoid double-htmlencoding by the pear
			 * HTML classes, where they are doing a htmlspecialchars where
			 * they shouldn't do it.
			 * 
			 */
			$txt = htmldecode($this->_hinttext);
			$obj = $this->_hintobject;
			
			$hoverState .= "this.oldhint = document.getElementById('$obj').firstChild.data;";	
			$hoverState .= "document.getElementById('$obj').firstChild.data = '$txt';";
			$oldhint     = "document.getElementById('$obj').firstChild.data = this.oldhint;";
			$newhint     = "document.getElementById('$obj').firstChild.data = '$txt'; ";
			$newhint    .= "this.oldhint = '$txt'; ";
		}
		
		$toggleState   = "this.style.backgroundColor='#A7A7B3';";
		$toggleState  .= "this.style.borderColor = '#747488 #E8E8EE #E8E8EE #747488';";
		$toggleState  .= "this.style.padding = '2px 0px 0px 2px';";
		
		$resetRegularState  = "document.getElementById(item).style.backgroundColor='#C6C6D5';";
		$resetRegularState .= "document.getElementById(item).style.borderColor = '#E8E8EE #747488 #747488 #E8E8EE';";
		$resetRegularState .= "document.getElementById(item).style.padding = '1px';";
		$resetRegularState .= "document.getElementById(item).toggle = 0;";

		$linkid = $this->_link->getID();
		
		$linkup = "document.getElementById('$linkid').href = '$link';";
		$linkdown = "document.getElementById('$linkid').href = '$link2';";
		
		$linkItems = implode(",", $this->_linkedItems);
		
		$updateLinks  = "this.linked = new Array($linkItems); ";
		$updateLinks .= "this.toggle = 1; ";
		$updateLinks .= "for (var i=0; i < this.linked.length; ++i) ";
		$updateLinks .= "{ ";
		$updateLinks .= "   var item = this.linked[i]; ";
		$updateLinks .= "   if (this.id != item) ";
		$updateLinks .= "   { ";
		$updateLinks .= "   	$resetRegularState ";
		$updateLinks .= "   } ";
		$updateLinks .= "} ";
		$updateLinks .= "$newhint ";
		
		$this->_img->setEvent("click", "$updateLinks");
		$this->_img->setEvent("mousedown", $toggleState);
		$this->_img->setEvent("mouseover", $hoverState);
		$this->_img->setEvent("mouseout", "if (!this.toggle) { $regularState } else { $downState }; $oldhint");
		$this->_link->setContent($this->_img->render());
		
		$this->setContent($this->_link->render());
		
		return cHTMLSPAN::render() . $defaultscript;	
	}
	
}
?>