<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Manages HTML pages and provides functions for rendering them.
 * As a usage example you can take a look at include.upl_files_upload.php
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
 *   created  2012-07-10
 *
 *   $Id: class.page.php 2379 2012-06-22 21:00:16Z xmurrix $:
 * }}
 */
if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Manages HTML pages and provides functions for rendering them
 */
class cGuiPage {

    /**
     * The name of the page. This will be used to load the template, stylesheets and scripts.
     * @var string
     */
    protected $_pagename;

    /**
     * The name of the plugin of the current webpage.
     * @var string
     */
    protected $_pluginname;

    /**
     * The general page template.
     * @var Template
     */
    protected $_pagetemplate;

    /**
     * The template for everything that is inside the body. (Usually template.PAGENAME.html)
     * @var Template
     */
    protected $_contenttemplate;

    /**
     * An array of script names (inside /scripts/) which will be included in the final page.
     * @var array
     */
    protected $_scripts;

    /**
     * An array of stylesheets (inside /styles/) which will be included in the final page.
     * @var array
     */
    protected $_styles;

    /**
     * The encoding meta tag.
     * @var string
     */
    protected $_encoding;

    /**
     * The script to set the subnavigation. This will be included in the final page.
     * @var string
     */
    protected $_subnav;

    /**
     * The script to markup the current submenu item. This will be included in the final page.
     * @var string
     */
    protected $_markscript;

    /**
     * An error message which will be used to display an error with the help of cGuiNotification
     * @var string
     */
    protected $_error;

    /**
     * A warning which will be used to display an error with the help of cGuiNotification
     * @var string
     */
    protected $_warning;

    /**
     * An info which will be used to display an error with the help of cGuiNotification
     * @var unknown_type
     */
    protected $_info;

    /**
     * If true, just display the message and don't render the template
     * @var bool
     */
    protected $_abort;

    /**
     * An array of cHTML objects which will be rendered instead of filling a template.
     * @var array
     */
    protected $_objects;

    /**
     * The constructor initializes the class and tries to get the encoding from the currently selected language.
     * It will also add every script in the form of /scripts/*.PAGENAME.js and every stylesheet in the form of /styles/*.PAGENAME.css to the page as well as /scripts/PAGENAME.js and /styles/PAGENAME.css.
     *
     * @param string $pagename The name of the page which will be used to load corresponding stylehseets, templates and scripts.
     * @param string $pluginname The name of the plugin in which the site is run
     * @param string $submenu The number of the submenu which should be highlighted when this page is shown.
     */
    public function __construct($pagename, $pluginname = "", $submenu = "") {
        global $lang, $cfg;

        $this->_pagename = $pagename;
        $this->_pluginname = $pluginname;
        $this->_pagetemplate = new Template();
        $this->_contenttemplate = new Template();
        $this->_scripts = array();
        $this->_styles = array();
        $this->_encoding = "";
        $this->_subnav = "";
        $this->_markscript = "";
        $this->_error = "";
        $this->_warning = "";
        $this->_info = "";
        $this->_abort = false;
        $this->_objects = array();

        /* Try to extract the current CONTENIDO language */
        $clang = new cApiLanguage($lang);

        if (!$clang->virgin) {
            $this->setEncoding($clang->get("encoding"));
        }

        $this->_pagetemplate->set("s", "SUBMENU", $submenu);
        $this->_pagetemplate->set("s", "PAGENAME", $pagename);

        $stylefiles = glob($cfg['path']['styles']."*.".$pagename.".css");
        if($stylefiles === false) {
            $stylefiles = array();
        }
        if(cFileHandler::exists($cfg['path']['styles'].$pagename.".css")) {
            $stylefiles[] = $cfg['path']['styles'].$pagename.".css";
        }
        foreach($stylefiles as $stylefile) {
            $this->addStyle(substr($stylefile, strpos($stylefile, "/") + 1));
        }

        $scriptfiles = glob($cfg['path']['scripts']."*.".$pagename.".js");
        if($scriptfiles === false) {
            $scriptfiles = array();
        }
        if(cFileHandler::exists($cfg['path']['scripts'].$pagename.".js")) {
            $scriptfiles[] = $cfg['path']['scripts'].$pagename.".js";
        }
        foreach($scriptfiles as $scriptfile) {
            $this->addScript(substr($scriptfile, strpos($scriptfile, "/") + 1));
        }
    }

    /**
     * Adds a script to the website.
     * NOTE: This function will also add inline JavaScript in the form of "<script...". However this shouldn't be used.
     * @param string $script The filename of the script. It has to reside in /scripts/ in order to be found.
     */
    public function addScript($script) {
        $this->_scripts[] = $script;
    }

    /**
     * Adds a stylesheet to the website.
     * @param string $stylesheet The filename of the stylesheet. It has to reside in /styles/ in order to be found.
     */
    public function addStyle($stylesheet) {
        $this->_styles[] = $stylesheet;
    }

    /**
     * Loads the subnavigation of the current area upon rendering.
     * @param string $additional Additional parameters the subnavigation might need. These have to look like "key=value&key2=value2..."
     * @param string $aarea The area of the subnavigation. If none is given the current area will be loaded
     */
    public function setSubnav($additional = "", $aarea = "") {
        global $area, $sess;

        if($aarea == "") {
            $aarea = $area;
        }

        $this->_subnav = '<script type="text/javascript">';
        $this->_subnav .= 'parent.frames["right_top"].location.href = "';
        $this->_subnav .= $sess->url("main.php?area=".$aarea."&frame=3&".$additional).'";';
        $this->_subnav .= '</script>';
    }

    /**
     * Adds the reload script for the left_bottom frame to the website
     */
    public function setReload() {
        $this->_scripts[] = "reload.js";
    }

    /**
     * Sets the markscript
     * @param string $item The number of the submenu which should be marked.
     */
    public function setMarkScript($item) {
        $this->_markscript = markSubMenuItem($item, true);
    }

    /**
     * Sets the encoding of the website
     * @param string $encoding An encoding which should be valid to use in the meta tag
     */
    public function setEncoding($encoding) {
        $this->_encoding = $encoding;
    }

    /**
     * Applies a value to a key in the content template.
     * @param string $type Either "s" or "d" for "static" or "dynamic" values
     * @param string $key The key which should be replaced
     * @param string $value The value which should replace the key
     * @see Template::set()
     */
    public function set($type, $key, $value) {
        $this->_contenttemplate->set($type, $key, $value);
    }

    /**
     * After calling this the page will only display messages and not render the content template.
     * NOTE: You still have to call render() to actually show any messages
     */
    public function abortRendering() {
        $this->_abort = true;
    }

    /**
     * Displays an error message and aborts rendering after that
     * NOTE: You still have to call render() to actually show any messages
     * @param string $msg A message
     */
    public function displayCriticalError($msg) {
        $this->_error = $msg;
        $this->_abort = true;
    }

    /**
     * Displays an error but the rendering of the content template will continue.
     * @param string $msg A message
     */
    public function displayError($msg) {
        $this->_error .= $msg."<br>";
    }

    /**
     * Displays a warning
     * @param string $msg The warning
     */
    public function displayWarning($msg) {
        $this->_warning .= $msg."<br>";
    }

    /**
     * Displays an info
     * @param string $msg The info message
     */
    public function displayInfo($msg) {
        $this->_info .= $msg."<br>";
    }

    /**
     * Sets an array (or a single object) of cHTML objects which build up the site instead of a content template.
     * NOTE: All these objects must have a render() method or else they won't be shown
     * @param array|object $objects An array of objects
     */
    public function setContent($objects) {
        if(!is_array($objects)) {
            $objects = array($objects);
        }
        $this->_objects = $objects;
    }

    /**
     * Renders the page and either prints it or returns it
     * @param Template|null $template If set, use this content template instead of the default one
     * @param bool $return If true, the page will be returned instead of echoed
     * @return string|void Returns either the webpage or nothing
     */
    public function render($template = null, $return = false) {
        global $cfg, $notification;

        if($template == null) {
            $template = $this->_contenttemplate;
        }

        if($this->_encoding != "") {
            $this->_pagetemplate->set("s", "META", '<meta http-equiv="Content-type" content="text/html;charset='.$this->_encoding.'">'."\n");
        }

        $strscript = $this->_subnav.$this->_markscript;
        foreach($this->_scripts as $script) {
            if(strpos($script, "<script") === false) {
                $strscript .= "<script type='text/javascript' src='scripts/".$script."'></script>\n";
            } else {
                cDeprecated("You shouldn't use inline JS for the backend pages.");

                $strscript .= $script;
            }
        }
        $this->_pagetemplate->set("s", "SCRIPTS", $strscript);

        $strstyle = "";
        foreach($this->_styles as $style) {
            $strstyle .= "<link href='styles/".$style."' type='text/css' rel='stylesheet'>\n";
        }
        $this->_pagetemplate->set("s", "STYLES", $strstyle);

        $text = "";
        if($this->_info != "") {
            $text = $notification->returnNotification("info", $this->_info)."<br>";
        }
        if($this->_warning != "") {
            $text = $notification->returnNotification("warning", $this->_warning)."<br>";
        }
        if($this->_error != "") {
            $text = $notification->returnNotification("error", $this->_error)."<br>";
        }

        $file = "";
        if($this->_pluginname == "") {
            $file = $cfg['path']['templates']."template.".$this->_pagename.".html";
        } else {
            $file = $cfg['path']['plugins'].$this->_pluginname."/templates/template.".$this->_pagename.".html";
        }

        if(!$this->_abort) {
            if(count($this->_objects) == 0) {
                if(cFileHandler::exists($file)) {
                    $this->_pagetemplate->set("s", "CONTENT", $text.$template->generate($file, true));
                } else {
                    $this->_pagetemplate->set("s", "CONTENT", $text);
                }
            } else {
                $str = "";
                foreach($this->_objects as $obj) {
                    if(method_exists($obj, "render")) {
                        //Ridiculous workaround because some objects return code if the parameter is true and some return the code if the parameter is false.
                        $old_str = $str;
                        ob_start(); //We don't want any code outside the body (in case the object outputs directly we will catch this output)
                        $str .= $obj->render(false); //We get the code either directly or via the output
                        $str .= ob_get_contents();
                        if($old_str == $str) {
                            cWarning(__FILE__, __LINE__, "Rendering this object (".print_r($obj, true).") doesn't seem to have any effect.");
                        }
                        ob_end_clean();
                    }
                }
                $this->_pagetemplate->set("s", "CONTENT", $text.$str);
            }
        } else {
            $this->_pagetemplate->set("s", "CONTENT", $text);
        }

        return $this->_pagetemplate->generate($cfg['path']['templates'].$cfg['templates']['generic_page'], $return);
    }
}

?>