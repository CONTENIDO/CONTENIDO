<?php
/**
 * This file contains the WYSIWYG editor class for TinyMCE.
 *
 * @package    Core
 * @subpackage Backend
 * @version    SVN Revision $Rev:$
 *
 * @author     Thomas Stauer
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.lang.php');

/**
 * The object cTinyMCE4Editor is a wrapper class to the TinyMCE WYSIWYG Editor.
 * Attributes can be defined to generate JavaScript options and functions to initialise the global
 * tinymce object in file ./contenido/external/wysiwyg/tinymce4/contenido/templates/template.tinymce_tpl.html.
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
class cTinyMCE4Editor extends cWYSIWYGEditor {
    /**
     * Stores base url of page
     */
    private $_sBaseURL;

    /**
     * Stores, if GZIP compression will be used
     */
    private $_bUseGZIP = false;

    public function __construct($sEditorName, $sEditorContent) {
        global $belang, $cfg, $cfgClient, $client, $lang, $idart;

        parent::__construct($sEditorName, $sEditorContent);
        $this->_setEditor("tinymce4");

        // Retrieve all settings for tinymce 4
        $this->_aSettings = getEffectiveSettingsByType("tinymce4");

        $this->_setSetting("article_url_suffix", 'front_content.php?idart=' . $idart, true); # modified 23.10.2006

        // Default values

        // apply editor to any element with class CMS_HTML or CMS_HTMLHEAD
        $this->_setSetting('selector', '*.CMS_HTML, *.CMS_HTMLHEAD', true);

        $aPathFragments = explode('/', $cfgClient[$client]["path"]["htmlpath"]);
        $this->_setSetting("content_css", $cfgClient[$client]["path"]["htmlpath"] . "css/style_tiny.css");

        $this->_setSetting("theme", "modern");
        $this->_setSetting("remove_script_host", false);
//         $this->_setSetting("file_browser_callback", 'function(field_name, url, type, win) {Con.Tiny.customFileBrowserCallback(field_name, url, type, win);}', true);
        //$this->_setSetting("file_browser_callback", "Con.Tiny.customFileBrowserCallback");
        $this->_setSetting("urlconverter_callback", "Con.Tiny.customURLConverterCallback");
        // New in V3.x
        $this->_setSetting("pagebreak_separator", "<!-- my page break -->"); // needs pagebreak plugin
        // Source formatting (ugh!)
        $this->_setSetting("remove_linebreaks", false); // Remove linebreaks - GREAT idea...

        // Convert URLs and Relative URLs default
        $this->_setSetting("convert_urls", false);
        $this->_setSetting("relative_urls", false);

//         // Editor name (a comma separated list of instances)
//         $this->_setSetting("elements", $sEditorName);

        // Editor language
        $aLangs = i18nGetAvailableLanguages();
        $this->_setSetting("language", $aLangs[$belang][4]);
        unset($aLangs);

        // Set document base URL
        //$this->setSetting("document_base_url", $cfgClient[$client]["path"]["htmlpath"], true);

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
        if (false === isset($this->_aSettings["contenido_gzip"])
        || "true" !== $this->_aSettings["contenido_gzip"]) {
            $this->setGZIPMode(false);
        } else {
            $this->setGZIPMode(true);
        }

        // Stylesheet file, for compatibility
        if (!array_key_exists("content_css", $this->_aSettings) && array_key_exists("tinymce-stylesheet-file", $this->_aSettings)) {
            $this->_setSetting("content_css", $this->_aSettings["tinymce-stylesheet-file"], true);
        }

        // Set lists (for links and image elements)
        $this->setLists();

        // Set user defined styles (be sure, that previous and SPAW syntax works)
        $this->setUserDefinedStyles();

        // Width and height
        $this->_setSetting("width", "100%");
        $this->_setSetting("height", "480px");

        // Text direction (rtl = right to left)
        $sDirection = langGetTextDirection($lang);
        $this->_setSetting("directionality", $sDirection);

        if ($sDirection == "rtl") {
            $this->_setSetting("theme_advanced_toolbar_align", "right", true);
        } else {
            $this->_setSetting("theme_advanced_toolbar_align", "left", true);
        }

        // Date and time formats
        $this->_setSetting("plugin_insertdate_dateFormat", $this->convertFormat(getEffectiveSetting("dateformat", "date", "Y-m-d")));
        $this->_setSetting("plugin_insertdate_timeFormat", $this->convertFormat(getEffectiveSetting("dateformat", "time", "H:i:s")));

        // Setting the toolbar (toolbar_mode and tinymce-toolbar-mode accepted)
        $sMode = "full";
        if (array_key_exists("tinymce-toolbar-mode", $this->_aSettings)) {
            $sMode = $this->_aSettings["tinymce-toolbar-mode"];
        }
        if (array_key_exists("contenido_toolbar_mode", $this->_aSettings)) {
            $sMode = $this->_aSettings["contenido_toolbar_mode"];
        }
        $this->setToolbar(trim(strtolower($sMode)));

        $autoFullElements = $this->_aSettings['auto_full_elements'];
        unset($this->_aSettings['auto_full_elements']);

        // Valid elements, for compatibility also accepts "tinymce-valid-elements"
        if (!array_key_exists("valid_elements", $this->_aSettings) && array_key_exists("tinymce-valid-elements", $this->_aSettings)) {
            $this->_setSetting("valid_elements", $this->_aSettings["tinymce-valid-elements"], true);
        }

        // _setSetting checks, if value is empty

        if ($autoFullElements === 'true') {
            $this->_setSetting('valid_elements', '*[*]');
            $this->_setSetting('extended_valid_elements', '*[*]');
        }

        $this->_setSetting("valid_elements", "a[name|href|target|title],strong/b[class],em/i[class],strike[class],u[class],p[dir|class|align],ol,ul,li,br,img[class|src|border=0|alt|title|hspace|vspace|width|height|align],sub,sup,blockquote[dir|style],table[border=0|cellspacing|cellpadding|width|height|class|align|style],tr[class|rowspan|width|height|align|valign|style],td[dir|class|colspan|rowspan|width|height|align|valign|style],div[dir|class|align],span[class|align],pre[class|align],address[class|align],h1[dir|class|align],h2[dir|class|align],h3[dir|class|align],h4[dir|class|align],h5[dir|class|align],h6[dir|class|align],hr");

        // Extended valid elements, for compatibility also accepts "tinymce-extended-valid-elements"
        if (!array_key_exists("extended_valid_elements", $this->_aSettings) && array_key_exists("tinymce-extended-valid-elements", $this->_aSettings)) {
            $this->_setSetting("extended_valid_elements", $this->_aSettings["tinymce-extended-valid-elements"]);
        }


        //print_r($this->_aSettings['valid_elements']);

        $this->_setSetting("extended_valid_elements", "form[name|action|method],textarea[name|style|cols|rows],input[type|name|value|style|onclick],a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]");

        // Clean all possible URLs
        $this->cleanURLs();

        // Remove CONTENIDO specific settings
        unset($this->_aSettings["contenido_toolbar_mode"], $this->_aSettings["contenido_lists"]);
        // Remove obsolete, deprecated values
        unset($this->_aSettings["tinymce-stylesheet-file"], $this->_aSettings["tinymce-valid-elements"], $this->_aSettings["tinymce-extended-valid-elements"], $this->_aSettings["tinymce-lists"], $this->_aSettings["tinymce-styles"], $this->_aSettings["tinymce-toolbar-mode"], $this->_aSettings["tinymce-toolbar1"], $this->_aSettings["tinymce-toolbar2"], $this->_aSettings["tinymce-toolbar3"], $this->_aSettings["tinymce-plugins"]);
    }

    function convertFormat($sInput) {
        $aFormatCodes = array(
            "y" => "%y", "Y" => "%Y", "d" => "%d", "m" => "%m", "H" => "%H", "h" => "%I", "i" => "%M", "s" => "%S", "a" => "%P", "A" => "%P"
        );

        foreach ($aFormatCodes as $sFormatCode => $sReplacement) {
            $sInput = str_replace($sFormatCode, $sReplacement, $sInput);
        }

        return ($sInput);
    }

    function setUserDefinedStyles() {
        $sStyles = "";

        if (array_key_exists("theme_advanced_styles", $this->_aSettings)) {
            $sStyles = $this->_aSettings["theme_advanced_styles"];
        } else if (array_key_exists("tinymce-styles", $this->_aSettings)) {
            $sStyles = $this->_aSettings["tinymce-styles"];
        }

        if ($sStyles) {
            $this->_setSetting("theme_advanced_styles", preg_replace('/;$/i', '', str_replace("|", "=", trim($sStyles))), true);
        }
    }

    /**
     * The special name "contenido_lists"
     *
     * @param string    sLists    Deprecated, for compatibility, only
     */
    function setLists() {
        global $lang, $client;

        $sLists = '';
        if (array_key_exists("contenido_lists", $this->_aSettings)) {
            $sLists = $this->_aSettings["contenido_lists"];
        }

        $aLists = array();
        $aLists = explode(',', strtolower(str_replace(' ', '', $sLists)));

        if (in_array('link', $aLists)) {
            $this->_setSetting('link_list', $this->_sBaseURL . 'contenido/ajax/class.tinymce_list.php?mode=link&lang=' . $lang . '&client=' . $client . '#', true);
        }
        if (in_array('image', $aLists)) {
            $this->_setSetting('image_list', $this->_sBaseURL . 'contenido/ajax/class.tinymce_list.php?mode=image&lang=' . $lang . '&client=' . $client . '#', true);
        }
        // media list does not exist, media plugin still available though
//         if (in_array('media', $aLists)) {
//             $this->_setSetting('media_external_list_url', $this->_sBaseURL . 'contenido/ajax/class.tinymce_list.php?mode=media&lang=' . $lang . '&client=' . $client . '#', true);
//         }
    }

    function setXHTMLMode($bEnabled = true) {
        if ($bEnabled) {
            $this->_setSetting('cleanup_callback', '', true);
        } else {
            $this->_setSetting('cleanup_callback', 'Con.Tiny.customCleanupCallback', true);
        }
    }

    /**
     * Set if editor should be loaded using tinymce4's gzip compression
     * @param string $bEnabled
     */
    private function setGZIPMode($bEnabled = true) {
        if ($bEnabled) {
            $this->_bUseGZIP = true;
        } else {
            $this->_bUseGZIP = false;
        }
    }

    /**
     * 
     * @return boolean if editor is loaded using gzip compression
     */
    public function getGZIPMode() {
        return (bool) $this->_bUseGZIP;
    }

    /**
     * For compatibility also accepts "tinymce-toolbar-mode", "tinymce-toolbar1-3" and "tinymce-plugins"
     */
    function setToolbar($sMode = "") {
        global $cfg, $cfgClient, $client;

        // hide visualaid button because it has no icon
        // http://www.tinymce.com/develop/bugtracker_view.php?id=6003

        // Overview of available controls and their required plugins:
        // http://www.tinymce.com/wiki.php/Controls

        switch ($sMode) {
            case "full": // Show all options
                $this->_setSetting('toolbar1', 'cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen', true);
                $this->_setSetting('toolbar2', 'link unlink anchor image media hr | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code', true);
                $this->_setSetting('toolbar3', 'table | formatselect fontselect fontsizeselect', true);
                $this->_setSetting('plugins',  'charmap code table save hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor',  true);
                $aCustSettings = getEffectiveSettingsByType('tinymce4');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }
                break;

            case "fullscreen": // Show all options
                $this->_setSetting('toolbar1', 'cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen', true);
                $this->_setSetting('toolbar2', 'link unlink anchor image media | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code', true);
                $this->_setSetting('toolbar3', 'table | formatselect fontselect fontsizeselect', true);
                // load some plugins
                $this->_setSetting('plugins', 'charmap code table save hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor', true);

                $aCustSettings = getEffectiveSettingsByType("tinymce4_fullscreen");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }

                break;

            case "simple": // Does not show font and table options
//                 $this->_setSetting("theme_advanced_buttons1", "cut,copy,paste,pastetext,|,searchreplace,|,undo,redo,|,bold,italic,underline,strikethrough,subscript,superscript,|,insertdatetime,preview", true);
//                 $this->_setSetting("theme_advanced_buttons2", "link,unlink,anchor,image,|,bullist,numlist,|,outdent,indent,|,alignleft,aligncenter,alignright,alignfull,removeformat,|,forecolor,backcolor,|,ltr,rtl,|,charmap,|,code", true);
//                 $this->_setSetting("theme_advanced_buttons3", "", true);
                $this->_setSetting("toolbar1", "cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview", true);
                $this->_setSetting("toolbar2", "link unlink anchor image | bullist numlist | outdent indent | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code", true);
                $this->_setSetting("toolbar3", "", true);

                $this->_setSetting("plugins", "anchor charmap code insertdatetime preview searchreplace print contextmenu paste directionality textcolor", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce4_simple");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }

                break;

            case "mini": // Minimal toolbar
                $this->_setSetting("toolbar1", "undo redo | bold italic underline strikethrough | link", true);
                $this->_setSetting("toolbar2", "", true);
                $this->_setSetting("toolbar3", "", true);

                $this->_setSetting("plugins", "contextmenu", true);
                

                $aCustSettings = getEffectiveSettingsByType("tinymce4_mini");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }

                break;

            case "custom": // Custom toolbar
                // tinymce4-toolbar1/2/3 and tinymce4-plugins are only mentioned for compatibility
                // They are ignored, if toolbar1/2/3 and plugins have been already
                // specified
                $this->_setSetting("toolbar1", $this->_aSettings["tinymce4-toolbar1"]);
                $this->_setSetting("toolbar2", $this->_aSettings["tinymce4-toolbar2"]);
                $this->_setSetting("toolbar3", $this->_aSettings["tinymce4-toolbar3"]);
                $this->_setSetting("plugins", $this->_aSettings["tinymce4-plugins"]);

                $aCustSettings = getEffectiveSettingsByType("tinymce4_custom");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }

                break;

            case "inline_edit":
                $this->_setSetting('toolbar1', 'bold italic underline strikethrough | undo redo | bullist numlist separator forecolor backcolor | alignleft aligncenter alignright | fullscreen | save close', true);
                $this->_setSetting('toolbar2', '', true);
                $this->_setSetting('toolbar3', '', true);

                $this->_setSetting("setupcontent_callback", "Con.Tiny.customSetupContentCallback", true);

                $this->_unsetSetting("width");
                $this->_setSetting("height", "210px", true);
                
                // use custom plugins
                // they are specified in con_tiny.js
                // close plugin: save and close button
                // confullscreen plugin: switches inline mode to off and adjusts toolbar in fullscreen mode
                $this->_setSetting("plugins", "table close confullscreen textcolor", true);

                // fullscreen plugin does not work with inline turned on, custom plugin confullscreen required for this
                $this->_setSetting('inline', 'true');
                $this->_setSetting('menubar', 'false');
                $this->_setSetting("content_css", $cfgClient[$client]["path"]["htmlpath"] . "css/style_tiny.css", true);

                // auto-resize needs autoresize plugin
                // http://www.tinymce.com/wiki.php/Plugin:autoresize
//                 if (!array_key_exists("auto_resize", $this->_aSettings)) {
//                     $this->_setSetting("auto_resize", "false", true);
//                 }

                $aCustSettings = getEffectiveSettingsByType("tinymce4_inline");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }

                break;

            default: // Default options
                $this->_setSetting('toolbar1', 'undo redo | bold italic underline strikethrough | link unlink anchor image | table', true);
                $this->_setSetting('toolbar2', 'styleselect,|,bullist,numlist,|,outdent,indent,|,alignleft,aligncenter,alignright,alignfull,removeformat,|,forecolor,backcolor,|,subscript,superscript,|,code', true);
                $this->_setSetting('toolbar3', "", true);
                $this->_setSetting('plugins', "anchor code table,searchreplace,contextmenu,paste textcolor", true);

                $aCustSettings = getEffectiveSettingsByType("tinymce_default");
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }
        }
    }

    function cleanURLs() {
        global $sess;

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
                $this->_setSetting($sParameter, $this->addPath($this->_aSettings[$sParameter]), true);
            }
        }

        // Session for template and media support files that are written in PHP
        $aParameters = array(
            'media_external_list_url', //media plugin
            'template_external_list_url' //template plugin
        );

        foreach ($aParameters as $sParameter) {
            if (array_key_exists($sParameter, $this->_aSettings) && preg_match('/\\.php$/i', $this->_aSettings[$sParameter])) {
                $this->_setSetting($sParameter, $this->_aSettings[$sParameter] . '?contenido=' . $sess->id, true);
            }
        }
    }

    function addPath($sFile) {
        global $cfgClient, $client;

        // Quick and dirty hack
        if (!preg_match('/^(http|https):\/\/((?:[a-zA-Z0-9_-]+\.?)+):?(\d*)/', $sFile)) {
            if (preg_match('/^\//', $sFile)) {
                $sFile = "http://" . $_SERVER['HTTP_HOST'] . $sFile;
            } else {
                $sFile = $cfgClient[$client]["htmlpath"]["frontend"] . $sFile;
            }
        }

        return $sFile;
    }

    function setBaseURL($sBaseUrl) {
        $this->_sBaseURL = $sBaseUrl;
    }

    public function _getScripts() {
        if ($this->_bUseGZIP) {
            $sReturn = "\n<!-- tinyMCE -->\n" . '<script language="javascript" type="text/javascript" src="' . $this->_sBaseURL . 'tinymce/js/tinymce/tinymce.gzip.js"></script>';
        } else {
            $sReturn = "\n<!-- tinyMCE -->\n" . '<script language="javascript" type="text/javascript" src="' . $this->_sBaseURL . 'tinymce/js/tinymce/tinymce.min.js"></script>';
        }

        return $sReturn;
    }

    public function _getEditor() {
        global $sess, $cfg, $lang, $client, $idart, $cfgClient;

        // TODO: Check functionality - doesn't seem to have any effect...
        $browserparameters = array("restrict_imagebrowser" => array("jpg", "gif", "jpeg", "png"));
        $sess->register("browserparameters");

        // Contenido-specific: Set article_url_suffix setting as it is used in plugins/advlink/jscripts/functions.js on anchor tags
        $this->_setSetting("setupcontent_callback", 'Con.Tiny.customSetupContentCallback', true);
        $this->_setSetting("save_callback", 'Con.Tiny.customSaveCallback', true);

        // Set browser windows
        // Difference between file and image browser is with (file) or without categories/articles (image)
        $oTemplate = new cTemplate();
        $oTemplate->set('s', 'IMAGEBROWSER', $cfg["path"]["contenido_fullhtml"] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
        $oTemplate->set('s', 'FILEBROWSER', $cfg["path"]["contenido_fullhtml"] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
        $oTemplate->set('s', 'MEDIABROWSER', $cfg["path"]["contenido_fullhtml"] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
        $oTemplate->set('s', 'FRONTEND_PATH', $cfgClient[$client]["path"]["htmlpath"]);

//         // GZIP support options
//         $sGZIPScript = '';
//         if ($this->_bUseGZIP) {
//             // tinyMCE_GZ.init call must be placed in its own script tag
//             // User defined plugins and themes should be identical in both "inits"
//             $sGZIPScript = <<<JS
// <script type="text/javascript">
// tinyMCE_GZ.init({
//     plugins: '{$this->_aSettings["plugins"]}',
//     themes: '{$this->_aSettings["theme"]}',
//     languages: '{$this->_aSettings["language"]}',
//     disk_cache: true,
//     debug: false
// });
// </script>
// JS;
//         }
//         $oTemplate->set('s', 'COMPRESSOR', $sGZIPScript);
        
        // Calculate the configuration
        $sConfig = '';

        foreach ($this->_aSettings as $sKey => $sValue) {
            if (is_bool($sValue)) {
                if ($sValue === true) {
                    $sValue = "true";
                } else {
                    $sValue = "false";
                }
            }

            if ($sValue == "true" || $sValue == "false" || $sKey == "oninit" || $sKey == "onpageload" || $sKey == 'style_formats') {
                $sConfig .= "'$sKey': " . $sValue;
            } else {
                $sConfig .= "'$sKey': '" . $sValue . "'";
            }
            $sConfig .= ",\n\t";
        }

        $sConfig = substr($sConfig, 0, -3);
        $oTemplate->set('s', 'CONFIG', $sConfig);
//         $oTemplate->set('s', 'CONFIGSETTINGS', )

        $oTxtEditor = new cHTMLTextarea($this->_sEditorName, $this->_sEditorContent);
        $oTxtEditor->setId($this->_sEditorName);
        $oTxtEditor->setClass(htmlentities($this->_sEditorName));

        $oTxtEditor->setStyle("width: " . $this->_aSettings["width"] . "; height: " . $this->_aSettings["height"] . ";");

        $sReturn = $oTemplate->generate($cfg['path']['all_wysiwyg'] . $this->_sEditor . "contenido/templates/template.tinymce_tpl.html", true);
        $sReturn .= $oTxtEditor->render();

        return $sReturn;
    }

    public function getConfigInlineEdit() {
        $sConfig = '';
        $this->setToolbar('inline_edit');

        foreach ($this->_aSettings as $sKey => $sValue) {
            if (is_bool($sValue)) {
                if ($sValue === true) {
                    $sValue = "true";
                } else {
                    $sValue = "false";
                }
            }

            if ($sValue == "true" || $sValue == "false" || $sKey == "oninit" || $sKey == "onpageload" || $sKey == 'style_formats') {
                $sConfig .= "'$sKey': " . $sValue;
            } else {
                $sConfig .= "'$sKey': '" . $sValue . "'";
            }
            $sConfig .= ",\n\t";
        }

        $sConfig = substr($sConfig, 0, -3);

        return $sConfig;
    }

    public function getConfigFullscreen() {
        $sConfig = '';
        $this->setToolbar('fullscreen');

        $sConfig .= "'toolbar1': '" . $this->_aSettings['toolbar1'] . "',\n";
        $sConfig .= "'toolbar2': '" . $this->_aSettings['toolbar2'] . "',\n";
        $sConfig .= "'toolbar3': '" . $this->_aSettings['toolbar3'] . "',\n";

        $sConfig .= "'plugins': '" . $this->_aSettings['plugins'] . "'\n";

        return $sConfig;
    }

    /**
     * function to obtain a comma separated list of plugins that are tried to be loaded 
     * @return string plugins the plugins
     */
    public function getPlugins() {
        return (string) $this->_aSettings['plugins'];
    }
    
    /**
     * function to obtain a comma separated list of themes that are tried to be loaded
     * @return string themes the themes
     */
    function getThemes() {
        return (string) $this->_aSettings['theme'];
    }
}

?>