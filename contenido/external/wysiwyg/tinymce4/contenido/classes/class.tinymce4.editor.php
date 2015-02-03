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
        global $idart;
        
        $belang = cRegistry::getBackendLanguage();
        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $cfgClient = cRegistry::getClientConfig();
        $lang = cRegistry::getLanguageId();

        parent::__construct($sEditorName, $sEditorContent);
        $this->_setEditor("tinymce4");

        // Retrieve all settings for tinymce 4
        $this->_aSettings = cTinymce4Configuration::get(array(), 'tinymce4');

        // CEC for template pre processing
        $this->_aSettings = cApiCecHook::executeAndReturn('Contenido.WYSIWYG.LoadConfiguration', $this->_aSettings, $this->_sEditor);

        // change datastructure to be json for tinymce 4
        reset($this->_aSettings);
        foreach ($this->_aSettings as &$setting) {
            $setting = json_encode($setting);
        }        

        $this->_setSetting("article_url_suffix", 'front_content.php?idart=' . $idart, true);

        // Default values

        // apply editor to any element with class CMS_HTML or CMS_HTMLHEAD
        $this->_setSetting('selector', '*.CMS_HTML, *.CMS_HTMLHEAD', true);

        $this->_setSetting("content_css", $cfgClient[$client]["path"]["htmlpath"] . "css/style_tiny.css");

        $this->_setSetting("theme", "modern");
        $this->_setSetting("remove_script_host", false);

        $this->_setSetting("urlconverter_callback", "Con.Tiny.customURLConverterCallback");
        // New in V3.x
        $this->_setSetting("pagebreak_separator", "<!-- my page break -->"); // needs pagebreak plugin
        // Source formatting (ugh!)
        $this->_setSetting("remove_linebreaks", false); // Remove linebreaks - GREAT idea...

        // Convert URLs and Relative URLs default
        $this->_setSetting("convert_urls", false);
        $this->_setSetting("relative_urls", false);

        // Editor language
        $aLangs = i18nGetAvailableLanguages();
        $this->_setSetting("language", $aLangs[$belang][4]);
        unset($aLangs);

        // Set document base URL for all relative URLs
        // http://www.tinymce.com/wiki.php/Configuration:document_base_url
         $this->_setSetting('document_base_url', cRegistry::getFrontendUrl(), true);

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

//         if ($sDirection == "rtl") {
//             $this->_setSetting("theme_advanced_toolbar_align", "right", true);
//         } else {
//             $this->_setSetting("theme_advanced_toolbar_align", "left", true);
//         }

        // Date and time formats
        $this->_setSetting("plugin_insertdate_dateFormat", $this->convertFormat(getEffectiveSetting("dateformat", "date", "Y-m-d")));
        $this->_setSetting("plugin_insertdate_timeFormat", $this->convertFormat(getEffectiveSetting("dateformat", "time", "H:i:s")));

        // Setting the toolbar (toolbar_mode and tinymce-toolbar-mode accepted)
        $sMode = "full";
        if (array_key_exists("contenido_toolbar_mode", $this->_aSettings)) {
            $sMode = $this->_aSettings["contenido_toolbar_mode"];
        }
        $this->setToolbar(trim(strtolower($sMode)));

        $autoFullElements = $this->_aSettings['auto_full_elements'];
        if (true === isset($this->_aSettings['auto_full_elements'])) {
            unset($this->_aSettings['auto_full_elements']);
        }

        // Specify valid elements that tinymce 4 is allowed to write

        // allow any element
        if ($autoFullElements === 'true') {
            $this->_setSetting('valid_elements', '*[*]');
            $this->_setSetting('extended_valid_elements', '*[*]');
        }

        $this->_setSetting("valid_elements", "a[name|href|target|title],strong/b[class],em/i[class],strike[class],u[class],p[dir|class|style],ol,ul,li[style],br,img[class|src|border=0|alt|title|hspace|vspace|width|height|style],sub,sup,blockquote[dir|style],table[border=0|cellspacing|cellpadding|width|height|class|style],tr[class|rowspan|width|height|valign|style],td[dir|class|colspan|rowspan|width|height|valign|style],div[dir|class|style],span[class|style],pre[class|style],address[class|style],h1[dir|class|style],h2[dir|class|style],h3[dir|class|style],h4[dir|class|style],h5[dir|class|style],h6[dir|class|style],hr");

        // Extended valid elements, for compatibility also accepts "tinymce-extended-valid-elements"
        if (!array_key_exists("extended_valid_elements", $this->_aSettings) && array_key_exists("tinymce-extended-valid-elements", $this->_aSettings)) {
            $this->_setSetting("extended_valid_elements", $this->_aSettings["tinymce-extended-valid-elements"]);
        }


        //print_r($this->_aSettings['valid_elements']);

        $this->_setSetting("extended_valid_elements", "form[name|action|method],textarea[name|style|cols|rows],input[type|name|value|style|onclick],a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|style|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|style]");

        // Clean all possible URLs
        $this->cleanURLs();

        // Remove CONTENIDO specific settings
        unset($this->_aSettings["contenido_toolbar_mode"], $this->_aSettings["contenido_lists"]);
        // Remove obsolete, deprecated values
        unset($this->_aSettings["tinymce-stylesheet-file"], $this->_aSettings["tinymce-valid-elements"], $this->_aSettings["tinymce-extended-valid-elements"], $this->_aSettings["tinymce-lists"], $this->_aSettings["tinymce-styles"], $this->_aSettings["tinymce-toolbar-mode"], $this->_aSettings["tinymce-toolbar1"], $this->_aSettings["tinymce-toolbar2"], $this->_aSettings["tinymce-toolbar3"], $this->_aSettings["tinymce4-plugins"]);
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

        // convert tinymce's style formats from string to required JSON value
        // http://www.tinymce.com/wiki.php/Configuration:style_formats
        if (array_key_exists('style_formats', $this->_aSettings)) {
            $sStyles = $this->_aSettings["style_formats"];
            if (strlen($sStyles) > 0) {
                // if json can be decoded
                if (null !== json_decode($sStyles)) {
                    $this->_setSetting('style_formats', json_decode($sStyles), true);
                }
            }
        }
    }

    /**
     * The special name "contenido_lists"
     *
     * @param string    sLists    Deprecated, for compatibility, only
     */
    function setLists() {
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();
        $aLists = array();
        if (array_key_exists("contenido_lists", $this->_aSettings)) {
            $aLists = $this->_aSettings["contenido_lists"];
        }

        // check if link list is activated
        if (in_array('link', $aLists)) {
            $this->_setSetting('link_list', $this->_sBaseURL . 'contenido/ajax/class.tinymce_list.php?mode=link&lang=' . $lang . '&client=' . $client . '#', true);
        }
        // check if image list is activated
        if (in_array('image', $aLists)) {
            $this->_setSetting('image_list', $this->_sBaseURL . 'contenido/ajax/class.tinymce_list.php?mode=image&lang=' . $lang . '&client=' . $client . '#', true);
        }
        // media list does not exist in tinymce 4, media plugin still available though
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
     * For compatibility also accepts "tinymce-toolbar-mode", "tinymce-toolbar1-3" and "tinymce4-plugins"
     */
    function setToolbar($sMode = "") {
        global $cfg, $cfgClient, $client;

        // hide visualaid button because it has no icon
        // http://www.tinymce.com/develop/bugtracker_view.php?id=6003

        // Overview of available controls and their required plugins:
        // http://www.tinymce.com/wiki.php/Controls

        // TODO:
        // Consider using
        // http://www.tinymce.com/wiki.php/Configuration:toolbar
        // instead of
        // http://www.tinymce.com/wiki.php/Configuration:toolbar%3CN%3E
        // 
        // This would allow users to specify more than just 3 toolbars in total

        switch ($sMode) {
            case "full": // Show all options
                $this->_setSetting('toolbar1', 'cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen', true);
                $this->_setSetting('toolbar2', 'link unlink anchor image media hr | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code', true);
                $this->_setSetting('toolbar3', 'table | formatselect fontselect fontsizeselect', true);
                $this->_setSetting('plugins',  'charmap code table save hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor',  true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', 'tinymce4_full');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }
                break;

            case "fullscreen": // Show all options
                // fullscreen of inline-editor
                $this->_setSetting('inline', false, true);
                $this->_setSetting('menubar', true, true);
                $this->_setSetting('toolbar1', 'cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen', true);
                $this->_setSetting('toolbar2', 'link unlink anchor image media | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code', true);
                $this->_setSetting('toolbar3', 'table | formatselect fontselect fontsizeselect', true);
                // load some plugins
                $this->_setSetting('plugins', 'charmap code table save hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor', true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', 'tinymce4_fullscreen');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }

                break;

            case "simple": // Does not show font and table options
                $this->_setSetting("toolbar1", "cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview", true);
                $this->_setSetting("toolbar2", "link unlink anchor image | bullist numlist | outdent indent | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code", true);
                $this->_setSetting("toolbar3", "", true);

                $this->_setSetting("plugins", "anchor charmap code insertdatetime preview searchreplace print contextmenu paste directionality textcolor", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', 'tinymce4_simple');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }

                break;

            case "mini": // Minimal toolbar
                $this->_setSetting("toolbar1", "undo redo | bold italic underline strikethrough | link", true);
                $this->_setSetting("toolbar2", "", true);
                $this->_setSetting("toolbar3", "", true);

                $this->_setSetting("plugins", "contextmenu", true);
                

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', 'tinymce4_mini');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }

                break;

            case "custom": // Custom toolbar
                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', 'tinymce4_custom');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }

                break;

            case "inline_edit":
                $this->_setSetting('inline', true, true);
                $this->_setSetting('menubar', false, true);
                $this->_setSetting('toolbar1', 'conabbr bold italic underline strikethrough | undo redo | bullist numlist separator forecolor backcolor | alignleft aligncenter alignright | fullscreen | save close', true);
                $this->_setSetting('toolbar2', '', true);
                $this->_setSetting('toolbar3', '', true);

//                 $this->_setSetting("setupcontent_callback", "Con.Tiny.customSetupContentCallback", true);

                $this->_unsetSetting("width");
                $this->_setSetting("height", "210px", true);

                // use custom plugins
                // they are specified in con_tiny.js
                // close plugin: save and close button
                // confullscreen plugin: switches inline mode to off and adjusts toolbar in fullscreen mode
                $this->_setSetting("plugins", "table close confullscreen textcolor", true);

                // fullscreen plugin does not work with inline turned on, custom plugin confullscreen required for this
                $this->_setSetting('inline', true);
                $this->_setSetting('menubar', false);
                $this->_setSetting("content_css", $cfgClient[$client]["path"]["htmlpath"] . "css/style_tiny.css", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', 'tinymce4_inline');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($sKey, $sValue, true);
                }

                break;

            default: // Default options
                $this->_setSetting('toolbar1', 'undo redo | bold italic underline strikethrough | link unlink anchor image | table', true);
                $this->_setSetting('toolbar2', 'styleselect,|,bullist,numlist,|,outdent,indent,|,alignleft,aligncenter,alignright,alignfull,removeformat,|,forecolor,backcolor,|,subscript,superscript,|,code', true);
                $this->_setSetting('toolbar3', "", true);
                $this->_setSetting('plugins', "anchor code table,searchreplace,contextmenu,paste textcolor", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', 'tinymce_default');
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

//         // Contenido-specific: Set article_url_suffix setting as it is used in plugins/advlink/jscripts/functions.js on anchor tags
//         $this->_setSetting("setupcontent_callback", 'Con.Tiny.customSetupContentCallback', true);
//         $this->_setSetting("save_callback", 'Con.Tiny.customSaveCallback', true);

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
        $oTemplate->set('s', 'BACKEND_URL', cRegistry::getBackendUrl());

        // check if file with list of client plugins is supplied
        if ('true' === getEffectiveSetting('tinymce4', 'contenido_load_client_plugins', false)) {
            // disallow any file not pointing into tinymce 4 config folder of client
            // to do that use fixed paths
            if ('true' === getEffectiveSetting('tinymce4', 'contenido_load_all_client_plugins')) {
                // fixed path for plugins to load
                $pluginFolderPath = cRegistry::getFrontendPath() . 'external/wysiwyg/tinymce4/contenido/client_plugins/plugins/';
                // look for all plugins (they are folders) in plugin folder
                $pluginFolderList = cDirHandler::read($pluginFolderPath, false, true);
                $tiny4ClientPlugins = array();
                foreach ($pluginFolderList as $pluginFolderName) {
                    $pluginPath = $pluginFolderPath . $pluginFolderName . '/';
                    // replace lagging frontend path with frontend url
                    $pluginUrl = substr_replace($pluginPath, cRegistry::getFrontendUrl(), 0, strlen(cRegistry::getFrontendPath()));
                    // check if minified version of plugin exists
                    if (true === cFileHandler::exists($pluginPath . 'plugin.min.js')) {
                        $tiny4ClientPlugins[] = (object) array('name' => $pluginFolderName,
                                                               'path' => $pluginUrl . 'plugin.min.js');
                    }  else {
                        // check if non-minified version of plugin exists
                        if (true === cFileHandler::exists($pluginPath . 'plugin.js')) {
                            $tiny4ClientPlugins[] = (object) array('name' => $pluginFolderName,
                                                                   'path' => $pluginUrl . 'plugin.js');
                        }
                    }
                }
                $oTemplate->set('s', 'CLIENT_PLUGINS', json_encode($tiny4ClientPlugins));
            } else {
                // load only specific plugins from config file
                $tiny4ClientPlugins = cRegistry::getFrontendPath() . 'data/tinymce4config/clientplugins.json';
                if (cFileHandler::exists($tiny4ClientPlugins)
                && cFileHandler::readable($tiny4ClientPlugins)) {
                    $oTemplate->set('s', 'CLIENT_PLUGINS', cFileHandler::read($tiny4ClientPlugins));
                }
            }
        } else {
            // no client plugins to load
            $oTemplate->set('s', 'CLIENT_PLUGINS', '[]');
        }

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
                    $sValue = 'true';
                } else {
                    $sValue = 'false';
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

        foreach ($this->_aSettings as $key => $val) {
            if (is_bool($val)) {
                $sConfig .= "'$key' : " . var_export($val, true) . ",\n";
            } else {
                $sConfig .= "'$key' : '" . $val . "',\n";
            }
        }

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