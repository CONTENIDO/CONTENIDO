<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Page widgets
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.24
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2007-01-30
 *   
 *   $Id: class.widgets.page.php,v 1.24 2007/01/30 20:00:29 bjoern.behrens Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.htmlelements.php");
cInclude("classes", "widgets/class.widgets.buttons.php");
cInclude("classes", "contenido/class.user.php");

/**
 * Regular page
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cPage extends cHTML
{
	/**
	 * Storage of scripts to be used on the page
     * @var array
     * @access private
	 */	
	var $_scripts;
	
	/**
	 * Storage of the page's content
     * @var string
     * @access private
	 */		
	var $_content;
	
	/**
	 * Storage of the margin
     * @var int
     * @access private
	 */		
	var $_margin;

	/**
	 * Storage of the desired encoding
     * @var string
     * @access private
	 */			
	var $_encoding;
	
	/**
	 * Storage of the sub navigation
     * @var string
     * @access private
	 */			
	var $_subnav;	
	
	/**
	 * Storage of the extra data (see template)
     * @var string
     * @access private
	 */			
	var $extra;		

	/**
     * Constructor Function
	 *
     * @param none
     */		
	function cPage ($object = false)
	{
		global $auth, $lang;
		
		$this->_margin = 10; 
		$this->_object = $object;
		
		/* Check for global register parameters */
		if (array_key_exists("u_register", $_GET))
		{
			$user = new cApiUser($auth->auth["uid"]);
			
			if (is_array($_GET["u_register"]))
			{
				foreach ($_GET["u_register"] as $type => $values)
				{
					foreach ($values as $name => $value)
					{
						$user->setProperty($type, $name, $value);
					}
				}
			}
		}
		
		/* Try to extract the current contenido language */
		$clang = new Language;
		$clang->loadByPrimaryKey($lang);
		
		if (!$clang->virgin)
		{
			$this->setEncoding($clang->get("encoding"));
		}			
	}

	/**
     * set the margin width (pixels)
	 *
     * @param $margin int Margin width
     */		
	function setMargin ($margin)
	{
		$this->_margin = $margin;
	}

	/**
     * sets a specific JavaScript for the header
	 * Important: The passed script needs to define <script></script> tags.
	 *
     * @param $name string Script identifier
     * @param $script string Script code
     */		
	function addScript ($name, $script)
	{
		$this->_scripts[$name] = $script;
	}
	
	/**
     * sets the link to the subnavigation. Should be set on the first page only.
	 *
     * @param $append URL to append
     */
    function setSubnav ($append, $marea = false)
    {
		if ($marea === false)
		{
			global $area;
			$marea = $area;
		}
		$this->_subnavArea = $marea;    	
    	$this->_subnav = $append;
    }			

	/**
     * adds the default script to reload the left pane (frame 2)
	 *
     * @param none
     */		
	function setReload ($location = false)
	{
		if ($location != false)
		{
    		$this->_scripts["__reload"] =
    			'<script type="text/javascript">'.
    			"if (parent.parent.frames['left'].frames['left_bottom'].get_registered_parameters) {".
    			"parent.parent.frames['left'].frames['left_bottom'].location.href = '$location' + parent.parent.frames['left'].frames['left_bottom'].get_registered_parameters();".
    			"} else {".
    			"parent.parent.frames['left'].frames['left_bottom'].location.href = '$location';".
    			"}"
    			
    			."</script>";
		} else {
    		$this->_scripts["__reload"] =
    			'<script type="text/javascript">'.
    			"if (parent.parent.frames['left'].frames['left_bottom'].get_registered_parameters) {".
    			"parent.parent.frames['left'].frames['left_bottom'].location.href = parent.parent.frames['left'].frames['left_bottom'].location.href + parent.parent.frames['left'].frames['left_bottom'].get_registered_parameters();".
    			"} else {".
    			"parent.parent.frames['left'].frames['left_bottom'].location.href = parent.parent.frames['left'].frames['left_bottom'].location.href;}".
    			"</script>";			
		}
	}

	/**
     * Sets the content for the page
	 *
     * @param $content mixed Object with a render method or a string containing the content
     */		
    function setContent ($content)
    {
    	/* Is it an array? */
    	if (is_array($content))
    	{
    		foreach ($content as $item)
    		{
				if (is_object($item))
        		{
        			if (method_exists($item, "render"))
        			{
        				$this->_content .= $item->render();
        			}
        		} else {
        			$this->_content .= $item;
        		}    			
    		}
    	} else {
    		if (is_object($content))
    		{
    			if (method_exists($content, "render"))
    			{
    				$this->_content = $content->render();
    				return;
    			}
    		} else {
    			$this->_content = $content;
    		}
    	}
	
   } 

	function setExtra ($extra)
	{
		$this->extra = $extra;
	}
	
	/**
     * adds the default script for a messagebox
	 *
     * @param none
     */		
	function setMessageBox ()
	{
		global $sess;
		$this->_scripts["__msgbox"] = 
		   '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>'.
		   '<script type="text/javascript"> 

            /* Session-ID */
            var sid = "'.$sess->id.'";

            /* Create messageBox
               instance */
            box = new messageBox("", "", "", 0, 0);

           </script>';
	}
	
	function setMarkScript ($item)
	{
		$this->_scripts["__markscript"] = markSubMenuItem($item, true);	
	}
	
	function setEncoding ($encoding)
	{
		$this->_encoding = $encoding;	
	}    

	/**
     * render the page
	 *
     * @param none
     */		
	function render ($print = true)
	{
		global $sess, $cfg;
		
		$tpl = new Template;
		
		$scripts = "";
		
		if (is_array($this->_scripts))
		{
			foreach ($this->_scripts as $key => $value)
			{
				$scripts .= $value;
			}
		}
		
		if ($this->_object !== false && method_exists($this->_object, "render") && is_array($this->_requiredScripts))
		{
			foreach ($this->_requiredScripts as $key => $value)
			{
				$scripts .= '<script type="text/javascript" src="scripts/'.$value.'"></script>';	
			}
		}
		
		if ($this->_object !== false && method_exists($this->_object, "render"))
		{
			$this->_content = $this->_object->render();	
		}
		
		if ($this->_subnav != "")
		{
			$scripts .= '<script type="text/javascript">';
			$scripts .= 'parent.frames["right_top"].location.href = "'.$sess->url("main.php?area=".$this->_subnavArea."&frame=3&".$this->_subnav).'";';
			$scripts .= '</script>';
		}
		
		if ($this->_encoding != "")
		{
			$scripts .= '<meta http-equiv="Content-type" content="text/html;charset='.$this->_encoding.'">'."\n";	
		}
		
		$tpl->set('s', 'SCRIPTS', $scripts);
		$tpl->set('s', 'CONTENT', $this->_content);
		$tpl->set('s', 'MARGIN', $this->_margin);
		$tpl->set('s', 'EXTRA', $this->extra);
		
		if ($print == true) {
			$tplRender = false;
		} else {
			$tplRender = true;
		}
		$rendered = $tpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_page'],$tplRender, false);
		
		if ($print == true)
		{
			echo $rendered;
		} else {
			return $rendered;
		}
	}

		
}

/**
 * Predefined page for use in frame 1
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cPageLeftTop extends cPage
{
	/**
     * Constructor Function
	 *
     * @param $showCloser boolean True if the closer should be shown (default)
     */	
	function cPageLeftTop ($showCloser = true)
	{
		$this->showCloser($showCloser);
	}
	
	/**
     * set wether the closer should be shown. 
	 *
     * @param $show boolean True if the closer should be shown (default)
     */		
	function showCloser ($show)
	{
		$this->_showCloser = $show;	
	}

	/**
     * render
	 *
     */		
	function render ($print = true)
	{
		global $cfg;
		
		$tpl = new Template;
		$tpl->set('s', 'CONTENT', $content);
		$this->setContent($tpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'].$cfg['templates']['widgets']['left_top'],true));
		
		cPage::render($print);
	}
}

/**
 * Predefined page for use in frame 1 with a multipane
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cPageLeftTopMultiPane extends cPageLeftTop
{
	/**
	 * Storage of the items
     * @var array
     * @access private
	 */		
	var $_items;
	
	/**
     * Constructor Function
	 *
	 * The passed array needs to be a multi-array in the following format:
	 *
	 *	$items = array(
	 *		array(	"image", "description", "link"),
	 *		array(	"image", "description", "link")
	 *	);
	 *
	 * Each sub-array needs to define an image, a description and a link.
	 * Note that the images are relative to the current directory, so you
	 * should include $cfg["path"]["images"] to retrieve the correct path.
	 *
     * @param $items array All items passed as multi array (see constructor description)
     */		
	function cPageLeftTopMultiPane ($items)
	{
		$this->_items = $items;
		
		cPageLeftTop::cPageLeftTop();
	}

	/**
     * set wether the closer should be shown. 
	 *
     * @param $show boolean True if the closer should be shown (default)
     */			
	function showCloser ($show)
	{
		$this->_showCloser = $show;	
	}

	/**
     * render
	 *
     */		
	function render ($print = true)
	{
		global $cfg;

		$infodiv = new cHTMLDIV;

		if (count($this->_items) > 0)
		{
	   		foreach ($this->_items as $item)
    		{
    			if (count($item) != 3)
    			{
    				echo "Error: the passed multi-array for cPageLeftTopMultiPane should contain 3 entries for each sub-item (see documentation for cPageLeftTopMultiPane)";
				} else {
        			$button = new cWidgetMultiToggleButton($item[0], $item[1], $item[2]);
        			$button->setBorder(1);
        			$button->setHint($infodiv->getID(), $item[1]);
        			$button->_link->setTargetFrame("left_bottom");
        			$linkedids[] = $button->_img->getID();
        			$buttons[] = $button;
				} 	
    		}
    		
    		$buttons[0]->setDefault();
    		$infodiv->setContent($buttons[0]->_hinttext);
    		
    		foreach ($buttons as $button)
    		{
    			foreach ($linkedids as $value)
    			{
    				$button->addLinkedItem($value);	
    			}
    			
    			$button->setStyle("margin-right: 2px;");
    			
    			$content .= $button->render();
    		}
    		
    		$content .= $infodiv->render();
    		
    		$wrapdiv = new cHTMLDIV;
    		$wrapdiv->setStyle("padding: 10px;");
    		$wrapdiv->setContent($content);
    		$this->setContent($wrapdiv);
		}
		
		$content = $this->_content;
		
		$tpl = new Template;
		$tpl->set('s', 'CONTENT', $content);
		$this->setContent($tpl->generate($cfg['path']['templates'].$cfg['templates']['widgets']['left_top'],true));
		
		cPage::render();
	}
}

class cNewPageLeftTopMultiPane extends cPageLeftTopMultiPane
{
	function cNewPageLeftTopMultiPane ($items)
	{
		cPageLeftTopMultiPane::cPageLeftTopMultiPane($items);
	}

	function render ($print = true)
	{
		global $cfg;

		$infodiv = new cHTMLDIV;

		if (count($this->_items) > 0)
		{
	   		foreach ($this->_items as $item)
    		{
    			if (count($item) != 3)
    			{
    				echo "Error: the passed multi-array for cPageLeftTopMultiPane should contain 3 entries for each sub-item (see documentation for cPageLeftTopMultiPane)";
				} else {
        			$button = new cWidgetMultiToggleButton($item[0], $item[1], $item[2]);
        			$button->setBorder(1);
        			$button->setHint($infodiv->getID(), $item[1]);
        			$button->_link->setTargetFrame("left_bottom");
        			$linkedids[] = $button->_img->getID();
        			$buttons[] = $button;
				} 	
    		}
    		
    		$buttons[0]->setDefault();
    		$infodiv->setContent($buttons[0]->_hinttext);
    		
    		foreach ($buttons as $button)
    		{
    			foreach ($linkedids as $value)
    			{
    				$button->addLinkedItem($value);	
    			}
    			
    			$button->setStyle("margin-right: 2px;");
    			
    			$content .= $button->render();
    		}
    		
    		$content .= $infodiv->render();
    		
    		$wrapdiv = new cHTMLDIV;
    		$wrapdiv->setStyle("padding: 10px;");
    		$wrapdiv->setContent($content);
    		$this->setContent($wrapdiv);
		}
		
		return $content;
	}
  
}
?>