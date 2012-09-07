<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   
 *   $Id: class.widgets.actionbutton.php 738 2008-08-27 10:21:19Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.htmlelements.php");
cInclude("classes", "contenido/class.action.php");

define("QUESTIONACTION_PROMPT", "prompt");
define("QUESTIONACTION_YESNO" , "yesno");

/**
 * class cApiClickableAction
 * cApiClickableAction is a subclass of cApiAction. It provides an image for visual
 * representation. Inherited classes should call the "setNamedAction" operation in
 * their constructors; on-the-fly-implementations should call it directly after
 * creating an object instance.
 */
class cApiClickableAction extends cApiAction
{

	/*** Attributes: ***/

	/**
	 * Help text
	 * @access private
	 */
	var $_helpText;

	/**
	 * cHTMLLink for rendering the icon
	 * @access private
	 */
	var $_link;
	
	/**
	 * cHTMLImage for rendering the icon
	 * @access private
	 */
	var $_img;
	
		
	function cApiClickableAction ()
	{
		global $area;
		
		cApiAction::cApiAction();
		
		$this->_area  = $area;
		$this->_frame = 4;
		$this->_target = "right_bottom";
		
		$this->_link = new cHTMLLink;
		$this->_img  = new cHTMLImage;
		$this->_img->setBorder(0);
		$this->_img->setStyle("padding-left: 1px; padding-right: 1px;");
		
		$this->_parameters = array();
		
		$this->setEnabled();
	}
	
	/**
	 * Sets the action icon for this action.
	 *
	 * @param string icon Path to the icon. Relative to the backend, if not passed as absolute path.
	 * @return void
	 * @access public
	 */
	function setIcon( $icon )
	{
		$this->_img->setSrc($icon);
	} // end of member function setIcon

	function getIcon ()
	{
		return $this->_img;	
	}

	/**
	 * Sets this class to use a specific action, example "con_makestart".
	 *
	 * @param string actionName Name of the action to use. This action must exist in the actions table before
	 * using it, otherwise, this method will fail.
	 * @return void
	 * @access public
	 */
	function setNamedAction( $actionName )
	{
		if ($this->loadBy("name", $actionName) !== false)
		{
			$a = new cApiArea;
			$a->loadByPrimaryKey($this->get("idarea"));
			
			$this->_namedAction = $actionName;
			$this->_area = $a->get("name"); 	
			
			$this->_parameters = array();
			$this->_wantParameters = array();
		}
	} // end of member function useNamedAction

	function setDisabled ()
	{
		$this->_enabled = false;
		$this->_onDisable();
	}
	
	function setEnabled ()
	{
		$this->_enabled = true;
		$this->_onEnable();	
	}
	
	function _onDisable ()
	{
	}
	
	function _onEnable ()
	{
	}
	
	/**
	 * Change linked area
	 */
	function changeArea ($sArea)
	{
		$this->_area = $sArea;
	}
	
	function wantParameter ($parameter)
	{
		$this->_wantParameters[] = $parameter;
		
		$this->_wantParameters = array_unique($this->_wantParameters);	
	}
	
	/**
	 * sets the help text for this action.
	 *
	 * @param string helptext The helptext to apply
	 * @return void
	 * @access public
	 */
	function setHelpText( $helptext )
	{
		$this->_helpText = $helptext;
	} // end of member function setHelpText

	function getHelpText ()
	{
		return $this->_helpText;
	}

	function setParameter ($name, $value)
	{
		$this->_parameters[$name] = $value;	
	}

	function process ($parameters)
	{
		echo "Process should be overridden";	
		return false;
	}
	
	function render ()
	{
		$this->_img->setAlt($this->_helpText);
		
		foreach ($this->_parameters as $name => $value)
		{
			$this->_link->setCustom($name, $value);	
		}
		
		$this->_link->setAlt($this->_helpText);
		$this->_link->setCLink($this->_area, $this->_frame, $this->_namedAction);
		$this->_link->setTargetFrame($this->_target);
		$this->_link->setContent($this->_img);
		
		if ($this->_enabled == true)
		{
			return ($this->_link->render());
		} else {
			return ($this->_img->render());	
		}
	}
	
	function renderText ()
	{
		foreach ($this->_parameters as $name => $value)
		{
			$this->_link->setCustom($name, $value);	
		}
		
		$this->_link->setAlt($this->_helpText);
		$this->_link->setCLink($this->_area, $this->_frame, $this->_namedAction);
		$this->_link->setTargetFrame($this->_target);
		$this->_link->setContent($this->_helpText);
		
		if ($this->_enabled == true)
		{
			return ($this->_link->render());
		} else {
			return ($this->_helpText);	
		}		
	}
} // end of cApiClickableAction

class cApiClickableQuestionAction extends cApiClickableAction
{
	function cApiClickableQuestionAction ()
	{
		cApiClickableAction::cApiClickableAction();
	}
	
	function setQuestionMode ($mode)
	{
		$this->_mode = $mode;
	}
	
	function setQuestion ($question)
	{
		$this->_question = $question;	
	}
	
	function setResultVar ($var)
	{
		$this->_resultVar = $var;	
	}
	
	function render ()
	{
		switch ($this->_mode)
		{
			case QUESTIONACTION_PROMPT:
				$this->_link->attachEventDefinition("_".get_class($this).rand(), "onclick", 'var answer = prompt("'.htmlspecialchars($this->_question).'");if (answer == null) {return false;} else { this.href = this.href + "&'.$this->_resultVar.'="+answer; return true;}');
				break;
			case QUESTIONACTION_YESNO:
			default:
			 	$this->_link->attachEventDefinition("_".get_class($this).rand(), "onclick", 'var answer = confirm("'.htmlspecialchars($this->_question).'");if (answer == false) {return false;} else { return true;}');
			 	break;
		}
			
		return parent::render();
	}
	

}
?>
