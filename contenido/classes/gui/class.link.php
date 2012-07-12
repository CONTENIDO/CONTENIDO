<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Manages HTML links
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO Backend
 * @subpackage GUI
 * @author     mischa.holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2012-07-12
 *
 *   $Id: class.link.php 2379 2012-06-22 21:00:16Z xmurrix $:
 * }}
 */
if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cGuiLink extends cHTML
{
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
     * Constructor. Creates an HTML link.
     * @param  string  $href  String with the location to link to
     */
    public function __construct($href = "") {
        global $sess;
        parent::__construct();

        $this->setLink($href);
        $this->setContentlessTag(false);
        $this->_tag = "a";
        $this->_image = "";

        // Check for backend
        if (is_object($sess)) {
            if ($sess->classname == "cSession") {
                $this->enableAutomaticParameterAppend();
            }
        }
    }

    public function enableAutomaticParameterAppend() {
        $this->setEvent("click", 'var doit = true; try { var i = get_registered_parameters() } catch (e) { doit = false; }; if (doit == true) { this.href += i; }');
    }

    public function disableAutomaticParameterAppend() {
        $this->unsetEvent("click");
    }

    /**
     * Sets the link to a specific location
     * @param  string  $href  String with the location to link to
     */
    public function setLink($href) {
        $this->_link = $href;
        $this->_type = "link";

        if (strpos($href, "javascript:") !== false) {
            $this->disableAutomaticParameterAppend();
        }
    }

    /**
     * Sets the target frame
     * @param  string  $target  Target frame identifier
     */
    public function setTargetFrame($target) {
        $this->updateAttributes(array("target" => $target));
    }

    /**
     * Sets a CONTENIDO link (area, frame, action)
     * @param  string  $targetarea  Target backend area
     * @param  string  $targetframe  Target frame (1-4)
     * @param  string  $targetaction  Target action
     */
    public function setCLink($targetarea, $targetframe, $targetaction = "") {
        $this->_targetarea = $targetarea;
        $this->_targetframe = $targetframe;
        $this->_targetaction = $targetaction;
        $this->_type = "clink";
    }

    /**
     * Sets a multilink
     * @param  string  $righttoparea       Area   (right top)
     * @param  string  $righttopaction     Action (right top)
     * @param  string  $rightbottomarea    Area   (right bottom)
     * @param  string  $rightbottomaction  Action (right bottom)
     */
    public function setMultiLink($righttoparea, $righttopaction, $rightbottomarea, $rightbottomaction) {
        $this->_targetarea = $righttoparea;
        $this->_targetframe = 3;
        $this->_targetaction = $righttopaction;
        $this->_targetarea2 = $rightbottomarea;
        $this->_targetframe2 = 4;
        $this->_targetaction2 = $rightbottomaction;
        $this->_type = "multilink";
    }

    /**
     * Sets a custom attribute to be appended to the link
     * @param  string  $key    Parameter name
     * @param  string  $value  Parameter value
     */
    public function setCustom($key, $value) {
        $this->_custom[$key] = $value;
    }

    public function setImage($src) {
        $this->_image = $src;
    }

    /**
     * Unsets a previous set custom attribute
     * @param  string  $key    Parameter name
     */
    public function unsetCustom($key) {
        if (isset($this->_custom[$key])) {
            unset($this->_custom[$key]);
        }
    }

    public function getHref() {
        global $sess;

        if (is_array($this->_custom)) {
            $custom = "";

            foreach ($this->_custom as $key => $value) {
                $custom .= "&$key=$value";
            }
        }

        if ($this->_anchor) {
            $anchor = "#".$this->_anchor;
        } else {
            $anchor = "";
        }

        switch ($this->_type) {
            case "link" :
                $custom = "";
                if (is_array($this->_custom)) {
                    foreach ($this->_custom as $key => $value) {
                        if ($custom == "") {
                            $custom .= "?$key=$value";
                        } else {
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
     * Sets an anchor
     * Only works for the link types Link and cLink.
     * @param  string  $content  Anchor name
     */
    public function setAnchor($anchor) {
        $this->_anchor = $anchor;
    }

    /**
     * Sets the link's content
     * @param  string|object  $content  String with the content or an object to render.
     */
    public function setContent($content) {
        $this->_setContent($content);
    }

    /**
     * Renders the link
     *
     * @return string Rendered HTML
     */
    public function toHTML() {
        $this->updateAttributes(array("href" => $this->getHref()));

         if($this->_image != "") {
             $image = new cHTMLImage($this->_image);
             $this->setContent($image);
         }

        return parent::toHTML();
    }
}

/**
 * Old class name for downwards compatibility
 * @deprecated This class was renamed to cGuiLink
 */
class Link extends cGuiLink {

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
        cDeprecated("This class was renamed to cGuiLink");

        parent::__construct();
    }

    function setJavascript ($js) {
        cDeprecated("This function never did anything.");
    }
}

/**
 * Old class name for downwards compatibility
 * @deprecated This class was renamed to cGuiLink
 */
class cHTMLLink extends cGuiLink {

    public function __construct($link = "") {
        cDeprecated("This class was renamed to cGuiLink.");

        parent::__construct($link);
    }
}

?>