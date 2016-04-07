<?php
/**
 * This file contains the WYSIWYG editor class for TinyMCE.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.lang.php');

/**
 * The object cTinyMCEEditor is a wrapper class to the TinyMCE WYSIWYG Editor.
 * Attributes can be defined to generate JavaScript options and functions to initialise the global
 * tinyMCE object in file ./contenido/external/wysiwyg/tinymce3/tinymce.tpl.html.
 *
 * All settings accepted by tinyMCE and its plugins may be specified using system, client
 * group or user property/setting.
 *
 * The following parameters will be always set on initialization (even, if they have been specified
 * as property. They can be set using setSetting later on, if needed):
 * document_base_url
 * cleanup_callback (-> XHTML)
 * file_browser_callback
 * external_link_list_url
 * external_image_list_url
 *
 * The following settings are only used in CONTENIDO:
 * contenido_toolbar_mode: full, simple, mini, custom
 * contenido_lists: link,image
 * contenido_height_html
 * contenido_height_head
 * See backend.customizing.html for details
 *
 * @package    Core
 * @subpackage Backend
 */
class cTinyMCEEditor extends cWYSIWYGEditor {

    /**
     * Stores base url of page
     *
     * @var string
     */
    protected $_baseURL = '';

    /**
     * Stores, if GZIP compression will be used
     *
     * @var bool
     */
    protected $_useGZIP = false;


    /**
     * cTinyMCEEditor constructor
     *
     * @param string $editorName
     * @param string $editorContent
     */
    public function __construct($editorName, $editorContent) {

        $belang = cRegistry::getBackendLanguage();
        $cfgClient = cRegistry::getClientConfig();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();
        $idart = cRegistry::getArticleId();

        parent::__construct($editorName, $editorContent);

        $this->_setEditor("tinymce3");

        // Retrieve all settings for tinymce
        $this->_aSettings = getEffectiveSettingsByType("tinymce");

        // For compatibility, read settings in previous syntax also (< V4.7, type "wysiwyg" vs. "tinymce")
        $this->_aSettings = array_merge(getEffectiveSettingsByType("wysiwyg"), $this->_aSettings);

        // modified 23.10.2006
        $this->setSetting(null, "article_url_suffix", 'front_content.php?idart=' . $idart, true);

        // Default values
        $this->setSetting(null, "mode", "exact");
        $aPathFragments = explode('/', $cfgClient[$client]["path"]["htmlpath"]);
        $this->setSetting(null, "content_css", $cfgClient[$client]["path"]["htmlpath"] . "css/style_tiny.css");

        $this->setSetting(null, "theme", "advanced");
        $this->setSetting(null, "theme_advanced_toolbar_location", "top");
        $this->setSetting(null, "theme_advanced_path_location", "bottom");
        $this->setSetting(null, "remove_script_host", false);
        $this->setSetting("file_browser_callback", "Con.Tiny.customFileBrowserCallback", true);
        //$this->_setSetting("urlconverter_callback", "Con.Tiny.customURLConverterCallback");
        // New in V3.x
        $this->setSetting(null, "theme_advanced_resizing", true);
        $this->setSetting(null, "pagebreak_separator", "<!-- my page break -->"); // needs pagebreak plugin
        // Source formatting (ugh!)
        $this->setSetting(null, "apply_source_formatting", true);
        $this->setSetting(null, "remove_linebreaks", false); // Remove linebreaks - GREAT idea...

        // Convert URLs and Relative URLs default
        $this->setSetting(null, "convert_urls", false);
        $this->setSetting(null, "relative_urls", false);

        // Editor name (a comma separated list of instances)
        $this->setSetting(null, "elements", $editorName);

        // Editor language
        $langs = i18nGetAvailableLanguages();
        $this->setSetting(null, "language", $langs[$belang][4]);
        unset($langs);

        // Set document base URL
        $this->setSetting(null, 'document_base_url', cRegistry::getFrontendUrl(), true);

        // The following "base URL" is the URL used to reference JS script files
        // - it is not the base href value
        //$this->setBaseURL(preg_replace('/^https?:\/\/[^\/]+(.*)$/', '$1', $this->_getEditorPath()));
        $this->setBaseURL($this->_getEditorPath());

        // XHTML
        if (getEffectiveSetting("generator", "xhtml", false) == "true") {
            $this->setXHTMLMode(true);
        } else {
            $this->setXHTMLMode(false);
        }

        // GZIP
        if ($this->_aSettings["contenido_gzip"] == "true") {
            $this->setGZIPMode(true);
        } else {
            $this->setGZIPMode(false);
        }

        // Stylesheet file, for compatibility
        if (!array_key_exists("content_css", $this->_aSettings) && array_key_exists("tinymce-stylesheet-file", $this->_aSettings)) {
            $this->setSetting(null, "content_css", $this->_aSettings["tinymce-stylesheet-file"], true);
        }

        // Set lists (for links and image elements)
        $this->setLists();

        // Set user defined styles (be sure, that previous and SPAW syntax works)
        $this->setUserDefinedStyles();

        // Width and height
        $this->setSetting(null, "width", "100%");
        $this->setSetting(null, "height", "480px");

        // Text direction (rtl = right to left)
        $sDirection = langGetTextDirection($lang);
        $this->setSetting(null, "directionality", $sDirection);

        if ($sDirection == "rtl") {
            $this->setSetting(null, "theme_advanced_toolbar_align", "right", true);
        } else {
            $this->setSetting(null, "theme_advanced_toolbar_align", "left", true);
        }

        // Date and time formats
        $this->setSetting(null, "plugin_insertdate_dateFormat", $this->convertFormat(getEffectiveSetting("dateformat", "date", "Y-m-d")));
        $this->setSetting(null, "plugin_insertdate_timeFormat", $this->convertFormat(getEffectiveSetting("dateformat", "time", "H:i:s")));

        // Setting the toolbar (toolbar_mode and tinymce-toolbar-mode accepted)
        $mode = "full";
        if (array_key_exists("tinymce-toolbar-mode", $this->_aSettings)) {
            $mode = $this->_aSettings["tinymce-toolbar-mode"];
        }
        if (array_key_exists("contenido_toolbar_mode", $this->_aSettings)) {
            $mode = $this->_aSettings["contenido_toolbar_mode"];
        }
        $this->setToolbar(trim(strtolower($mode)));

        $autoFullElements = $this->_aSettings['auto_full_elements'];
        unset($this->_aSettings['auto_full_elements']);

        // Valid elements, for compatibility also accepts "tinymce-valid-elements"
        if (!array_key_exists("valid_elements", $this->_aSettings) && array_key_exists("tinymce-valid-elements", $this->_aSettings)) {
            $this->setSetting(null, "valid_elements", $this->_aSettings["tinymce-valid-elements"], true);
        }

        // _setSetting checks, if value is empty
        if ($autoFullElements === 'true') {
            $this->setSetting(null, 'valid_elements', '*[*]');
            $this->setSetting(null, 'extended_valid_elements', '*[*]');
        }

        $this->setSetting(null, "valid_elements", "a[name|href|target|title],strong/b[class],em/i[class],strike[class],u[class],p[dir|class|align],ol,ul,li,br,img[class|src|border=0|alt|title|hspace|vspace|width|height|align],sub,sup,blockquote[dir|style],table[border=0|cellspacing|cellpadding|width|height|class|align|style],tr[class|rowspan|width|height|align|valign|style],td[dir|class|colspan|rowspan|width|height|align|valign|style],div[dir|class|align],span[class|align],pre[class|align],address[class|align],h1[dir|class|align],h2[dir|class|align],h3[dir|class|align],h4[dir|class|align],h5[dir|class|align],h6[dir|class|align],hr");

        // Extended valid elements, for compatibility also accepts "tinymce-extended-valid-elements"
        if (!array_key_exists("extended_valid_elements", $this->_aSettings) && array_key_exists("tinymce-extended-valid-elements", $this->_aSettings)) {
            $this->setSetting(null, "extended_valid_elements", $this->_aSettings["tinymce-extended-valid-elements"]);
        }


        //print_r($this->_aSettings['valid_elements']);

        $this->setSetting(null, "extended_valid_elements", "form[name|action|method],textarea[name|style|cols|rows],input[type|name|value|style|onclick],a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]");

        // Clean all possible URLs
        $this->cleanURLs();

        // Remove CONTENIDO specific settings
        unset($this->_aSettings["contenido_toolbar_mode"], $this->_aSettings["contenido_lists"]);
        // Remove obsolete, deprecated values
        unset($this->_aSettings["tinymce-stylesheet-file"], $this->_aSettings["tinymce-valid-elements"], $this->_aSettings["tinymce-extended-valid-elements"], $this->_aSettings["tinymce-lists"], $this->_aSettings["tinymce-styles"], $this->_aSettings["tinymce-toolbar-mode"], $this->_aSettings["tinymce-toolbar1"], $this->_aSettings["tinymce-toolbar2"], $this->_aSettings["tinymce-toolbar3"], $this->_aSettings["tinymce-plugins"]);
    }

    /**
     * Old constructor
     *
     * @param string $editorName
     * @param string $editorContent
     * @deprecated [2016-02-18]
     * 				This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @return __construct()
     */
    public function cTinyMCEEditor($editorName, $editorContent) {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        return $this->__construct($editorName, $editorContent);
    }

    /**
     * Convert formats
     *
     * @param string $input
     * @return string
     */
    public function convertFormat($input) {
        $aFormatCodes = array(
            "y" => "%y", "Y" => "%Y", "d" => "%d", "m" => "%m", "H" => "%H", "h" => "%I", "i" => "%M", "s" => "%S", "a" => "%P", "A" => "%P"
        );

        foreach ($aFormatCodes as $sFormatCode => $sReplacement) {
            $input = str_replace($sFormatCode, $sReplacement, $input);
        }

        return $input;
    }

    /**
     * Set user defined styles
     *
     */
    public function setUserDefinedStyles() {
        $styles = "";

        if (array_key_exists("theme_advanced_styles", $this->_aSettings)) {
            $styles = $this->_aSettings["theme_advanced_styles"];
        } else if (array_key_exists("tinymce-styles", $this->_aSettings)) {
            $styles = $this->_aSettings["tinymce-styles"];
        }

        if ($styles) {
            $this->setSetting(null, "theme_advanced_styles", preg_replace('/;$/i', '', str_replace("|", "=", trim($styles))), true);
        }
    }

    /**
     * The special name "contenido_lists", for compatibility also
     * accepts "tinymce-lists".
     *
     * @param string $lists
     *        Deprecated, for compatibility, only
     */
    public function setLists($lists = "") {

        $lang = cRegistry::getLanguageId();
        $client = cRegistry::getClientId();

        if ($lists == "") {
            if (array_key_exists("contenido_lists", $this->_aSettings)) {
                $lists = $this->_aSettings["contenido_lists"];
            } else if (array_key_exists("tinymce-lists", $this->_aSettings)) {
                $lists = $this->_aSettings["tinymce-lists"];
            }
        }

        $aLists = array();
        $aLists = explode(",", strtolower(str_replace(" ", "", $lists)));

        if (in_array("link", $aLists)) {
            $this->setSetting(null, "external_link_list_url", $this->_baseURL . "list.php?mode=link&lang=" . $lang . "&client=" . $client . "#", true);
        }
        if (in_array("image", $aLists)) {
            $this->setSetting(null, "external_image_list_url", $this->_baseURL . "list.php?mode=image&lang=" . $lang . "&client=" . $client . "#", true);
        }
        if (in_array("media", $aLists)) {
            $this->setSetting(null, "media_external_list_url", $this->_baseURL . "list.php?mode=media&lang=" . $lang . "&client=" . $client . "#", true);
        }
    }

    /**
     * Set method XHTML mode
     * Standard: true
     *
     * @param bool $beabled
     */
    public function setXHTMLMode($beabled = true) {
        if ($beabled) {
            $this->setSetting(null, "cleanup_callback", "", true);
        } else {
            $this->setSetting(null, "cleanup_callback", "Con.Tiny.customCleanupCallback", true);
        }
    }

    /**
     * Set method GZIP mode
     * Standard: true
     *
     * @param bool $enabled
     */
    public function setGZIPMode($enabled = true) {
        if ($enabled) {
            $this->_useGZIP = true;
        } else {
            $this->_useGZIP = false;
        }
    }

    /**
     * Get method for GZIP mode
     *
     * @return boolean
     */
    public function getGZIPMode() {
        return cSecurity::toBoolean($this->_useGZIP);
    }

    /**
     * For compatibility also accepts "tinymce-toolbar-mode", "tinymce-toolbar1-3" and "tinymce-plugins"
     *
     * @param string $mode
     */
    public function setToolbar($mode = "") {

        $cfgClient = cRegistry::getClientConfig();
        $client = cRegistry::getClientId();

        switch ($mode) {
            case "full": // Show all options
                $this->setSetting(null, "theme_advanced_buttons1", "cut,copy,paste,pastetext,pasteword,|,search,replace,|,undo,redo,|,bold,italic,underline,strikethrough,sub,sup,|,insertdate,inserttime,preview,|,styleselect,|,visualchars,nonbreaking,template,pagebreak,|,help,|,fullscreen", true);
                $this->setSetting(null, "theme_advanced_buttons2", "link,unlink,anchor,image,media,advhr,|,bullist,numlist,|,outdent,indent,blockquote,|,justifyleft,justifycenter,justifyright,justifyfull,removeformat,|,forecolor,backcolor,|,ltr,rtl,|,visualaid,charmap,cleanup,|,code", true);
                $this->setSetting(null, "theme_advanced_buttons3", "tablecontrols,|,formatselect,fontselect,fontsizeselect,|,styleprops,|,cite,abbr,acronym,del,ins,attribs", true);
                //table,save,advhr,advimage,advlink,pagebreak,style,layer,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template
                $this->setSetting(null, "plugins", "table,save,advhr,advimage,advlink,pagebreak,style,layer,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,visualchars,nonbreaking,xhtmlxtras,template,inlinepopups", true);
                $this->setSetting(null, "theme_advanced_toolbar_align", "left", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce");
                foreach ($aCustSettings as $key=> $value) {
                    $this->setSetting(null, $key, $value, true);
                }

                break;
            case "fullscreen": // Show all options
                $this->setSetting(null, "theme_advanced_buttons1", "cut,copy,paste,pastetext,pasteword,|,search,replace,|,undo,redo,|,bold,italic,underline,strikethrough,sub,sup,|,insertdate,inserttime,preview,|,styleselect,|,visualchars,nonbreaking,template,pagebreak,|,help,|,fullscreen", true);
                $this->setSetting(null, "theme_advanced_buttons2", "link,unlink,anchor,image,media,advhr,|,bullist,numlist,|,outdent,indent,blockquote,|,justifyleft,justifycenter,justifyright,justifyfull,removeformat,|,forecolor,backcolor,|,ltr,rtl,|,visualaid,charmap,cleanup,|,code", true);
                $this->setSetting(null, "theme_advanced_buttons3", "tablecontrols,|,formatselect,fontselect,fontsizeselect,|,styleprops,|,cite,abbr,acronym,del,ins,attribs", true);
                //table,save,advhr,advimage,advlink,pagebreak,style,layer,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template
                $this->setSetting(null, "plugins", "table,save,advhr,advimage,advlink,pagebreak,style,layer,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,visualchars,nonbreaking,xhtmlxtras,template,inlinepopups", true);
                $this->setSetting(null, "theme_advanced_toolbar_align", "left", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce_fullscreen");
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting(null, $key, $value, true);
                }

                break;
            case "simple": // Does not show font and table options
                $this->setSetting(null, "theme_advanced_buttons1", "cut,copy,paste,pastetext,pasteword,|,search,replace,|,undo,redo,|,bold,italic,underline,strikethrough,sub,sup,|,insertdate,inserttime,preview,|,styleselect", true);
                $this->setSetting(null, "theme_advanced_buttons2", "link,unlink,anchor,image,advhr,|,bullist,numlist,|,outdent,indent,|,justifyleft,justifycenter,justifyright,justifyfull,removeformat,|,forecolor,backcolor,|,ltr,rtl,|,visualaid,charmap,cleanup,|,code", true);
                $this->setSetting(null, "theme_advanced_buttons3", "", true);
                $this->setSetting(null, "plugins", "advhr,advimage,advlink,insertdatetime,preview,searchreplace,print,contextmenu,paste,directionality", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce_simple");
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting(null, $key, $value, true);
                }

                break;
            case "mini": // Minimal toolbar
                $this->setSetting(null, "theme_advanced_buttons1", "undo,redo,|,bold,italic,underline,strikethrough,|,link", true);
                $this->setSetting(null, "theme_advanced_buttons2", "", true);
                $this->setSetting(null, "theme_advanced_buttons3", "", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce_mini");
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting(null, $key, $value, true);
                }

                break;
            case "custom": // Custom toolbar
                // tinymce-toolbar1/2/3 and tinymce-plugins are only mentioned for compatibility
                // They are ignored, if theme_advanced_buttons1/2/3 and plugins have been already
                // specified
                $this->setSetting(null, "theme_advanced_buttons1", $this->_aSettings["tinymce-toolbar1"]);
                $this->setSetting(null, "theme_advanced_buttons2", $this->_aSettings["tinymce-toolbar2"]);
                $this->setSetting(null, "theme_advanced_buttons3", $this->_aSettings["tinymce-toolbar3"]);
                $this->setSetting(null, "plugins", $this->_aSettings["tinymce-plugins"]);
                $this->setSetting(null, "theme_advanced_toolbar_location", "bottom");

                $aCustSettings = getEffectiveSettingsByType("tinymce_custom");
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting(null, $key, $value, true);
                }

                break;
            case "inline_edit":
                $this->setSetting(null, "theme_advanced_buttons1", "bold,italic,underline,strikethrough,separator,undo,separator,bullist,numlist,separator,forecolor,backcolor,separator,justifyleft,justifycenter,justifyright,separator,fullscreen,separator,save,close", true);
                $this->setSetting(null, "theme_advanced_buttons2", "", true);
                $this->setSetting(null, "theme_advanced_buttons3", "", true);

                $this->setSetting(null, "setupcontent_callback", "Con.Tiny.customSetupContentCallback", true);

                $this->_unsetSetting("width");
                $this->_unsetSetting("theme_advanced_toolbar_location");
                $this->setSetting(null, "theme_advanced_toolbar_location", "external");
                $this->setSetting(null, "height", "210px", true);
                // close plugin not in plugins directory but still working if listed
                $this->setSetting(null, "plugins", "table,inlinepopups,fullscreen,close", true);
                $this->setSetting(null, "mode", "exact", true);
                $this->setSetting(null, "elements", "*", true);
                $this->setSetting(null, "content_css", $cfgClient[$client]["path"]["htmlpath"] . "css/style_tiny.css", true);

                if (!array_key_exists("auto_resize", $this->_aSettings)) {
                    $this->setSetting(null, "auto_resize", "false", true);
                }

                if (!array_key_exists("theme_advanced_toolbar_location", $this->_aSettings)) {
                    $this->setSetting(null, "theme_advanced_toolbar_location", "top", true);
                }

                if (!array_key_exists("theme_advanced_resizing_use_cookie", $this->_aSettings)) {
                    $this->setSetting(null, "theme_advanced_resizing_use_cookie", "false", true);
                }

                if (!array_key_exists("theme_advanced_toolbar_align", $this->_aSettings)) {
                    $this->setSetting(null, "theme_advanced_toolbar_align", "center", true);
                }

                $aCustSettings = getEffectiveSettingsByType("tinymce_inline");
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting($key, $value, true);
                }

                break;
            default: // Default options
                $this->setSetting(null, "theme_advanced_buttons1", "undo,redo,|,bold,italic,underline,strikethrough,|,link,unlink,anchor,image,advhr,|,tablecontrols", true);
                $this->setSetting(null, "theme_advanced_buttons2", "styleselect,|,bullist,numlist,|,outdent,indent,|,justifyleft,justifycenter,justifyright,justifyfull,removeformat,|,forecolor,backcolor,|,sub,sup,|,code", true);
                $this->setSetting(null, "theme_advanced_buttons3", "", true);
                $this->setSetting(null, "plugins", "table,advhr,advimage,advlink,searchreplace,contextmenu,paste", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce_default");
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting(null, $key, $value, true);
                }
        }
    }

    /**
     * Clean urls function
     *
     */
    public function cleanURLs() {

        $sess = cRegistry::getBackendSessionId();

        // Add the path to the following values
        $aParameters = array(
            //builtin
            'content_css', 'popups_css', 'popups_css_add', 'editor_css', // plugins
            'plugin_preview_pageurl', //preview plugin
            'media_external_list_url', //media plugin
            'template_external_list_url' //template plugin
        );

        foreach ($aParameters as $sParameter) {
            if (array_key_exists($sParameter, $this->_aSettings)) {
                $this->setSetting(null, $sParameter, $this->addPath($this->_aSettings[$sParameter]), true);
            }
        }

        // Session for template and media support files that are written in PHP
        $aParameters = array(
            'media_external_list_url', //media plugin
            'template_external_list_url' //template plugin
        );

        foreach ($aParameters as $sParameter) {
            if (array_key_exists($sParameter, $this->_aSettings) && preg_match('/\\.php$/i', $this->_aSettings[$sParameter])) {
                $this->setSetting(null, $sParameter, $this->_aSettings[$sParameter] . '?contenido=' . $sess->id, true);
            }
        }
    }

    /**
     * Add path before filename
     *
     * @param string $file
     * @return string
     */
    public function addPath($file) {

        $cfgClient = cRegistry::getClientConfig();
        $client = cRegistry::getClientId();

        // Quick and dirty hack
        if (!preg_match('/^(http|https):\/\/((?:[a-zA-Z0-9_-]+\.?)+):?(\d*)/', $file)) {
            if (preg_match('/^\//', $file)) {
                $file = "http://" . $_SERVER['HTTP_HOST'] . $file;
            } else {
                $file = $cfgClient[$client]['htmlpath']['frontend'] . $file;
            }
        }

        return $file;
    }

    /**
     * Set method for base url
     *
     * @param string $baseUrl
     */
    public function setBaseURL($baseUrl) {
        $this->_baseURL = $baseUrl;
    }

    /**
     * Get method for scripts
     *
     * @return string
     */
    public function getScripts() {
        if ($this->_useGZIP) {
            $return = "\n<!-- tinyMCE -->\n" . '<script language="javascript" type="text/javascript" src="' . $this->_baseURL . 'jscripts/tiny_mce/tiny_mce_gzip.js"></script>';
        } else {
            $return = "\n<!-- tinyMCE -->\n" . '<script language="javascript" type="text/javascript" src="' . $this->_baseURL . 'jscripts/tiny_mce/tiny_mce.js"></script>';
        }

        return $return;
    }

    /**
     * Get method for editor
     *
     * @return string
     */
    public function getEditor() {

        $sess = cRegistry::getSession();
        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $cfgClient = cRegistry::getClientConfig();

        // TODO: Check functionality - doesn't seem to have any effect...
        $sess->register("browserparameters");

        // Contenido-specific: Set article_url_suffix setting as it is used in plugins/advlink/jscripts/functions.js on anchor tags
        $this->setSetting(null, "setupcontent_callback", 'Con.Tiny.customSetupContentCallback', true);
        $this->setSetting(null, "save_callback", 'Con.Tiny.customSaveCallback', true);

        // Set browser windows
        // Difference between file and image browser is with (file) or without categories/articles (image)
        $template = new cTemplate();
        $template->set('s', 'IMAGEBROWSER', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
        $template->set('s', 'FILEBROWSER', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
        $template->set('s', 'MEDIABROWSER', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
        $template->set('s', 'FRONTEND_PATH', $cfgClient[$client]['path']['htmlpath']);

        // GZIP support options
        $GZIPScript = '';
        if ($this->_useGZIP) {
            // tinyMCE_GZ.init call must be placed in its own script tag
            // User defined plugins and themes should be identical in both "inits"
            $GZIPScript = <<<JS
<script type="text/javascript">
tinyMCE_GZ.init({
    plugins: '{$this->_aSettings["plugins"]}',
    themes: '{$this->_aSettings["theme"]}',
    languages: '{$this->_aSettings["language"]}',
    disk_cache: true,
    debug: false
});
</script>
JS;
        }
        $template->set('s', 'COMPRESSOR', $GZIPScript);

        // Calculate the configuration
        $config = '';

        foreach ($this->_aSettings as $key => $value) {
            if (is_bool($value)) {
                if ($value === true) {
                    $value = "true";
                } else {
                    $value = "false";
                }
            }

            if ($value == "true" || $value == "false" || $key == "oninit" || $key == "onpageload" || $key == 'style_formats') {
                $config .= "'$key': " . $value;
            } else {
                $config .= "'$key': '" . $value . "'";
            }
            $config .= ",\n\t";
        }

        $config = substr($config, 0, -3);
        $template->set('s', 'CONFIG', $config);

        $oTxtEditor = new cHTMLTextarea($this->_sEditorName, $this->_sEditorContent);
        $oTxtEditor->setId($this->_sEditorName);

        $oTxtEditor->setStyle("width: " . $this->_aSettings["width"] . "; height: " . $this->_aSettings["height"] . ";");

        $return = $template->generate($cfg['path']['all_wysiwyg'] . $this->_sEditor . "/tinymce.tpl.html", true);
        $return .= $oTxtEditor->render();

        return $return;
    }

    /**
     * Get method for inline editing
     *
     * @return string
     */
    public function getConfigInlineEdit() {
        $config = '';
        $this->setToolbar('inline_edit');

        foreach ($this->_aSettings as $key => $value) {
            if (is_bool($value)) {
                if ($value === true) {
                    $value = "true";
                } else {
                    $value = "false";
                }
            }

            if ($value == "true" || $value == "false" || $key == "oninit" || $key == "onpageload" || $key == 'style_formats') {
                $config .= "'$key': " . $value;
            } else {
                $config .= "'$key': '" . $value . "'";
            }
            $config .= ",\n\t";
        }

        $config = substr($config, 0, -3);

        return $config;
    }

    /**
     * Get method for fullscreen mode
     *
     * @return string
     */
    public function getConfigFullscreen() {
        $config = '';
        $this->setToolbar('fullscreen');

        $config .= "'theme_advanced_buttons1': '" . $this->_aSettings['theme_advanced_buttons1'] . "',\n";
        $config .= "'theme_advanced_buttons2': '" . $this->_aSettings['theme_advanced_buttons2'] . "',\n";
        $config .= "'theme_advanced_buttons3': '" . $this->_aSettings['theme_advanced_buttons3'] . "',\n";
        $config .= "'theme_advanced_toolbar_align': '" . $this->_aSettings['theme_advanced_toolbar_align'] . "',\n";
        $config .= "'plugins': '" . $this->_aSettings['plugins'] . "'\n";

        return $config;
    }

    /**
     * Get method for plugins
     *
     * @return array
     */
    public function getPlugins() {
        return $this->_aSettings['plugins'];
    }

    /**
     * Get method for themes
     *
     * @return array
     */
    public function getThemes() {
        return $this->_aSettings['theme'];
    }

}
