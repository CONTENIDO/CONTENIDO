<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Page widgets
 * 
 *
 * @package    CONTENIDO Backend Classes
 * @subpackage cPage widgets
 * @version    $Id:$
 * @author     Bjoern Behrens
 * @author     Ortwin Pinke
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 */

/**
 * Security define
 */
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


/**
 * Regular page
 *
 * @author      Timo A. Hummel <timo.hummel@4fb.de>
 */
class cPage extends cHTML {
    
    /**
     * Storage of scripts to be used on the page
     * @var array
     */	
    protected $_scripts;
	
    /**
     * Storage of the margin
     * @var int
     */		
    protected $_margin;

    /**
     * Storage of the desired encoding
     * @var string
     */			
    protected $_encoding;

    /**
     * Storage of the sub navigation
     * @var string
     */			
    protected $_subnav;	

    /**
     * Storage of the extra data (see template)
     * @var string
     */			
    protected $extra;
    
    /**
     *
     * @global obj $auth
     * @global string $lang
     * @param obj $object 
     */
    public function __construct($object = false) {
        global $auth, $lang;
		
        $this->_margin = 10; 
        $this->_object = $object;

        /* Check for global register parameters */
        if (array_key_exists("u_register", $_GET)) {
            
            $user = new cApiUser($auth->auth["uid"]);
            
            if (is_array($_GET["u_register"])) {
                foreach ($_GET["u_register"] as $type => $values) {
                    foreach ($values as $name => $value) {
                        $user->setProperty($type, $name, $value);
                    }
                }
            }
        }

        /* Try to extract the current CONTENIDO language */
        $clang = new cApiLanguage($lang);

        if (!$clang->virgin) {
            $this->setEncoding($clang->get("encoding"));
        }			
    }


    /**
     * @deprecated  [2011-08-31] Old constructor function for downwards compatibility
     * @uses __construct()
     */
    public function cPage($object = false)	{
        cDeprecated("Use __construct() instead");
        $this->__construct($object);
    }

    /**
     * set the margin width (pixels)
     *
     * @param type $margin 
     */
    public function setMargin($margin)	{
        $this->_margin = $margin;
    }

	    /**
     * sets a specific JavaScript for the header
     * Important: The passed script needs to define <script></script> tags.
     *
     * @param string $name a name for internal usage
     * @param string $script
     */
    public function addScript($name, $script) {
        $this->_scripts[$name] = $script;
    }
	
    /**
     * sets the link to the subnavigation. Should be set on the first page only.
     *
     * @global string $area
     * @param string $append
     * @param string $marea 
     */
    public function setSubnav($append, $marea = false) {
        if ($marea === false) {
            global $area;
            $marea = $area;
        }
        $this->_subnavArea = $marea;
        $this->_subnav = $append;
    }			

    /**
     * adds the default script to reload the left pane (frame 2)
     *
     * @param string $location default = false
     */
    public function setReload($location = false) {
        if ($location != false) {
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
     * @param mixed $content Object with a render method or a string containing the content
     * @return void 
     */
    public function setContent($content) {
        /* Is it an array? */
        if(is_array($content)) {
            foreach($content as $item) {
                if(is_object($item)) {
                    if(method_exists($item, "render")) {
                        $this->_content .= $item->render();
                    }
                } else {
                    $this->_content .= $item;
                }    			
            }
        } else {
            if(is_object($content)) {
                if (method_exists($content, "render")) {
                    $this->_content = $content->render();
                    return; 
                }
            } else {
                $this->_content = $content;
            }
        }
    } 

    /**
     *
     * @param string $extra 
     */
    public function setExtra($extra) {
        $this->extra = $extra;
    }
    
    /**
     * set default JS for a messagebox
     *
     * @global Contenido_Session $sess 
     */
    public function setMessageBoxScript() {
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

    /**
     * @deprecated [2011-08-31]
     * @see setMessageBoxScript()
     */
    public function setMessageBox()	{
        cDeprecated();
        $this->setMessageBoxScript();
    }
    
    /**
     * set JS for markup of a menu item
     *
     * @param string $item id of item
     */
    public function setMarkScript($item) {
        $this->_scripts["__markscript"] = markSubMenuItem($item, true);
    }

    /**
     * set encoding
     *
     * @param string $encoding 
     */
    public function setEncoding($encoding) {
        $this->_encoding = $encoding;
    }    

    /**
     * returns or echos rendered HTML-page
     *
     * @global Contenido_Session $sess
     * @global array $cfg
     * @param bool $print if true echo, if false print (default: true)
     * @return string or nothing if print
     */
    public function render($print = true) {
        global $sess, $cfg;
        
        $tpl = new Template();
        $scripts = "";
        
        if(is_array($this->_scripts)) {
            foreach($this->_scripts as $key => $value) {
                $scripts .= $value;
            }
        }
        
        if($this->_object !== false 
                && method_exists($this->_object, "render") 
                && is_array($this->_requiredScripts)) {
            
            foreach($this->_requiredScripts as $key => $value) {
                $scripts .= '<script type="text/javascript" src="scripts/'.$value.'"></script>';
            }
        }
        
        if ($this->_object !== false && method_exists($this->_object, "render")) {
            $this->_content = $this->_object->render();
        }
        
        if($this->_subnav != "") {
            $scripts .= '<script type="text/javascript">';
            $scripts .= 'parent.frames["right_top"].location.href = "';
            $scripts .= $sess->url("main.php?area=".$this->_subnavArea."&frame=3&".$this->_subnav).'";';
            $scripts .= '</script>';
        }
        
        if($this->_encoding != "") {
            $scripts .= '<meta http-equiv="Content-type" content="text/html;charset='.$this->_encoding.'">'."\n";
        }
        
        $tpl->set('s', 'SCRIPTS', $scripts);
        $tpl->set('s', 'CONTENT', $this->_content);
        $tpl->set('s', 'MARGIN', $this->_margin);
        $tpl->set('s', 'EXTRA', $this->extra);
        
        if($print == true) {
            $tplRender = false;
        } else {
            $tplRender = true;
        }
        
        $rendered = $tpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_page'],$tplRender, false);
        
        if($print == true) {
            echo $rendered;
        } else {
            return $rendered;
        }
    }
}

/* @deprecated 2012-03-10 This class is not longer supported. */
class cPageLeftTop extends cPage {
    
    /**
     * should closer be shown or not
     * @var bool 
     */
    protected $_showCloser;

    /**
     *
     * @param bool $showCloser 
     */
    public function cPageLeftTop($showCloser = true) {
		cDeprecated("This class is not supported any longer.");
        $this->showCloser($showCloser);
    }
    
    /**
     * 
     * @param bool $showCloser 
     */
    public function showCloser($showCloser) {
        $this->_showCloser = $showCloser;
    }
    
    /**
     *
     * @global type $cfg
     * @param type $print 
     */
    public function render($print = true) {
        global $cfg;
        
        $tpl = new Template();
        $tpl->set('s', 'CONTENT', $content);
        $this->setContent($tpl->generate($cfg['path']['contenido']
                .$cfg['path']['templates'].$cfg['templates']['widgets']['left_top'],true));
        
        parent::render($print);
    }
}


/* @deprecated 2012-03-10 This class is not longer supported. */
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
		cDeprecated("This class is not longer supported.");
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

		$infodiv = new cHTMLDiv;

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
    		
    		$wrapdiv = new cHTMLDiv;
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

/* @deprecated 2012-03-10 This class is not longer supported. */
class cNewPageLeftTopMultiPane extends cPageLeftTopMultiPane
{
	function cNewPageLeftTopMultiPane ($items)
	{
		cDeprecated("This class is not longer supported.");
		cPageLeftTopMultiPane::cPageLeftTopMultiPane($items);
	}

	function render ($print = true)
	{
		global $cfg;

		$infodiv = new cHTMLDiv;

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
    		
    		$wrapdiv = new cHTMLDiv;
    		$wrapdiv->setStyle("padding: 10px;");
    		$wrapdiv->setContent($content);
    		$this->setContent($wrapdiv);
		}
		
		return $content;
	}
  
}
?>