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

    /**
     * Access key under which the wysiwyg editor settings will be stored
     * @var string
     */
    protected static $_sConfigPrefix = '[\'wysiwyg\'][\'tinymce4\']';

    public function __construct($sEditorName, $sEditorContent) {
        global $idart;
        
        $belang = cRegistry::getBackendLanguage();
        $cfg = cRegistry::getConfig();
        $client = cRegistry::getClientId();
        $cfgClient = cRegistry::getClientConfig();
        $lang = cRegistry::getLanguageId();

        parent::__construct($sEditorName, $sEditorContent);
        $this->_setEditor("tinymce4");
        $this->_aSettings = array();

        // Retrieve all settings for tinymce 4, depending on CMS types
        // define empty arrays for all CMS types that can be edited using a WYSIWYG editor
        $oTypeColl = new cApiTypeCollection();
        $oTypeColl->select();
        while (false !== ($typeEntry = $oTypeColl->next())) {
            // specify a shortcut for type field
            $curType = $typeEntry->get('type');

            $contentTypeClassName = cTypeGenerator::getContentTypeClassName($curType);
            if (false === class_exists($contentTypeClassName)) {
                continue;
            }
            $cContentType = new $contentTypeClassName(null, 0, array());
            if (false === $cContentType->isWysiwygCompatible()) {
                continue;
            }
            $this->_aSettings[$curType] = cTinymce4Configuration::get(array(), 'tinymce4');
        }

        // CEC for template pre processing
        $this->_aSettings = cApiCecHook::executeAndReturn('Contenido.WYSIWYG.LoadConfiguration', $this->_aSettings, $this->_sEditor);

        // encode data to json when doing output instead of doing this here
        // this way we can manipulate data easier in PHP

        // process settings for each cms type
        foreach ($this->_aSettings as $cmsType => $setting) {
            $this->_setSetting($cmsType, "article_url_suffix", 'front_content.php?idart=' . $idart, true);

            // Default values

            // apply editor to any cms type provided in preferences
            $this->_setSetting($cmsType, 'selector', ('.' . $cmsType), true);

            $this->_setSetting($cmsType, "content_css", $cfgClient[$client]["path"]["htmlpath"] . "css/style_tiny.css");

            $this->_setSetting($cmsType, "theme", "modern");
            $this->_setSetting($cmsType, "remove_script_host", false);

            $this->_setSetting($cmsType, "urlconverter_callback", "Con.Tiny.customURLConverterCallback");
            // New in V3.x
            $this->_setSetting($cmsType, "pagebreak_separator", "<!-- my page break -->"); // needs pagebreak plugin
            // Source formatting (ugh!)
            $this->_setSetting($cmsType, "remove_linebreaks", false); // Remove linebreaks - GREAT idea...

            // Convert URLs and Relative URLs default
            $this->_setSetting($cmsType, "convert_urls", false);
            $this->_setSetting($cmsType, "relative_urls", false);

            // Editor language
            $aLangs = i18nGetAvailableLanguages();
            $this->_setSetting($cmsType, "language", $aLangs[$belang][4]);
            unset($aLangs);

            // Set document base URL for all relative URLs
            // http://www.tinymce.com/wiki.php/Configuration:document_base_url
            $this->_setSetting($cmsType, 'document_base_url', cRegistry::getFrontendUrl(), true);

            // The following "base URL" is the URL used to reference JS script files
            // - it is not the base href value
            //$this->setBaseURL(preg_replace('/^https?:\/\/[^\/]+(.*)$/', '$1', $this->_getEditorPath()));
            $this->setBaseURL($this->_getEditorPath());

            // XHTML
            if (getEffectiveSetting("generator", "xhtml", false) == "true") {
                $this->setXHTMLMode($cmsType, true);
            } else {
                $this->setXHTMLMode($cmsType, false);
            }

            // GZIP
            if (false === isset($this->_aSettings[$cmsType]["contenido_gzip"])
            || "true" !== $this->_aSettings[$cmsType]["contenido_gzip"]) {
                $this->setGZIPMode(false);
            } else {
                $this->setGZIPMode(true);
            }

            // Set lists (for links and image elements)
            $this->setLists($cmsType);

            // Set user defined styles (be sure, that previous and SPAW syntax works)
            $this->setUserDefinedStyles($cmsType);

            // Width and height
            $this->_setSetting($cmsType, "width", "100%");
            $this->_setSetting($cmsType, "height", "480px");

            // Text direction (rtl = right to left)
            $sDirection = langGetTextDirection($lang);
            $this->_setSetting($cmsType, "directionality", $sDirection);

            // Date and time formats
            $this->_setSetting($cmsType, "plugin_insertdate_dateFormat", $this->convertFormat(getEffectiveSetting("dateformat", "date", "Y-m-d")));
            $this->_setSetting($cmsType, "plugin_insertdate_timeFormat", $this->convertFormat(getEffectiveSetting("dateformat", "time", "H:i:s")));

            // Setting the toolbar (toolbar_mode and tinymce-toolbar-mode accepted)
            $sMode = "full";
            if (array_key_exists("contenido_toolbar_mode", $this->_aSettings[$cmsType])) {
                $sMode = $this->_aSettings[$cmsType]["contenido_toolbar_mode"];
            }
            $this->setToolbar($cmsType, trim(strtolower($sMode)));

            $autoFullElements = $this->_aSettings[$cmsType]['auto_full_elements'];
            if (true === isset($this->_aSettings[$cmsType]['auto_full_elements'])) {
                unset($this->_aSettings[$cmsType]['auto_full_elements']);
            }

            // Specify valid elements that tinymce 4 is allowed to write

            // allow any element
            if ($autoFullElements === 'true') {
                $this->_setSetting($cmsType, 'valid_elements', '*[*]');
                $this->_setSetting($cmsType, 'extended_valid_elements', '*[*]');
            }

            $this->_setSetting($cmsType, "valid_elements", "a[name|href|target|title],strong/b[class],em/i[class],strike[class],u[class],p[dir|class|style],ol,ul,li[style],br,img[class|src|border=0|alt|title|hspace|vspace|width|height|style],sub,sup,blockquote[dir|style],table[border=0|cellspacing|cellpadding|width|height|class|style],tr[class|rowspan|width|height|valign|style],td[dir|class|colspan|rowspan|width|height|valign|style],div[dir|class|style],span[class|style],pre[class|style],address[class|style],h1[dir|class|style],h2[dir|class|style],h3[dir|class|style],h4[dir|class|style],h5[dir|class|style],h6[dir|class|style],hr,source[*],video[*]");

            // Extended valid elements, for compatibility also accepts "tinymce-extended-valid-elements"
            if (!array_key_exists("extended_valid_elements", $this->_aSettings[$cmsType]) && array_key_exists("tinymce-extended-valid-elements", $this->_aSettings[$cmsType])) {
                $this->_setSetting($cmsType, "extended_valid_elements", $this->_aSettings["tinymce-extended-valid-elements"]);
            }

            $this->_setSetting($cmsType, "extended_valid_elements", "form[name|action|method],textarea[name|style|cols|rows],input[type|name|value|style|onclick],a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|style|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|style]");

            // Clean all possible URLs
            $this->cleanURLs($cmsType);

            // Remove CONTENIDO specific settings
            unset($this->_aSettings[$cmsType]["contenido_toolbar_mode"], $this->_aSettings[$cmsType]["contenido_lists"]);
            // Remove obsolete, deprecated values
            unset($this->_aSettings[$cmsType]["tinymce-stylesheet-file"], $this->_aSettings[$cmsType]["tinymce-valid-elements"], $this->_aSettings[$cmsType]["tinymce-extended-valid-elements"], $this->_aSettings[$cmsType]["tinymce-lists"], $this->_aSettings[$cmsType]["tinymce-styles"], $this->_aSettings[$cmsType]["tinymce-toolbar-mode"], $this->_aSettings[$cmsType]["tinymce-toolbar1"], $this->_aSettings[$cmsType]["tinymce-toolbar2"], $this->_aSettings[$cmsType]["tinymce-toolbar3"], $this->_aSettings[$cmsType]["tinymce4-plugins"]);
        }
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

    function setUserDefinedStyles($sType) {
        $sStyles = "";

        // convert tinymce's style formats from string to required JSON value
        // http://www.tinymce.com/wiki.php/Configuration:style_formats
        if(true === isset($this->_aSettings[$sType])
        && true === isset($this->_aSettings[$sType][$sType])) {
            if (array_key_exists('style_formats', $this->_aSettings[$sType][$sType])) {
                $sStyles = $this->_aSettings[$sType]["style_formats"];
                if (strlen($sStyles) > 0) {
                    // if json can be decoded
                    if (null !== json_decode($sStyles)) {
                        $this->_setSetting($sType, 'style_formats', json_decode($sStyles), true);
                    }
                }
            }
        }
    }

    /**
     * The special name "contenido_lists"
     *
     * @param string $sType CMS type where XHTML mode setting wil be applies
     */
    function setLists($sType) {
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        $aLists = array();
        if (array_key_exists("contenido_lists", $this->_aSettings[$sType])) {
            $aLists = json_decode($this->_aSettings[$sType]["contenido_lists"], true);
        }

        // check if link list is activated
        if (true === isset($aLists['link'])) {
            $this->_setSetting($sType, 'link_list', $this->_sBaseURL . 'contenido/ajax/class.tinymce_list.php?mode=link&lang=' . $lang . '&client=' . $client . '#', true);
        }
        // check if image list is activated
        if (true === isset($aLists['image'])) {
            $this->_setSetting($sType, 'image_list', $this->_sBaseURL . 'contenido/ajax/class.tinymce_list.php?mode=image&lang=' . $lang . '&client=' . $client . '#', true);
        }
        // media list does not exist in tinymce 4, media plugin still available though
    }

    /**
     * Turn XHTML mode on or off
     * @param string $sType CMS type where XHTML mode setting wil be applies
     * @param string $bEnabled Whether to turn on XHTML mode
     */
    function setXHTMLMode($sType, $bEnabled = true) {
        if ($bEnabled) {
            $this->_setSetting($sType, 'cleanup_callback', '', true);
        } else {
            $this->_setSetting($sType, 'cleanup_callback', 'Con.Tiny.customCleanupCallback', true);
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
    public function setToolbar($cmsType, $sMode = "") {
        $cfg = cRegistry::getConfig();
        $cfgClient = cRegistry::getClientConfig();
        $client = cRegistry::getClientId();

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
                if ('CMS_HTMLHEAD' === $cmsType) {
                    $defaultToolbar1 = cTinymce4Configuration::get('undo redo | consave conclose', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar1');
                    $defaultToolbar2 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar2');
                    $defaultToolbar3 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar3');
                    $defaultPlugins = cTinymce4Configuration::get('conclose', 'tinymce4', $cmsType, 'tinymce4_full', 'plugins');
                    $this->_setSetting($cmsType, 'menubar', false, true);
                } else {
                    $defaultToolbar1 = cTinymce4Configuration::get('cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar1');
                    $defaultToolbar2 = cTinymce4Configuration::get('link unlink anchor image media hr | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar2');
                    $defaultToolbar3 = cTinymce4Configuration::get('table | formatselect fontselect fontsizeselect | consave conclose', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar3');
                    $defaultPlugins = cTinymce4Configuration::get('charmap code conclose table conclose hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor', 'tinymce4', $cmsType, 'tinymce4_full', 'plugins');
                }
                $this->_setSetting($cmsType, 'inline', false, true);
                $this->_setSetting($cmsType, 'toolbar1', $defaultToolbar1, true);
                $this->_setSetting($cmsType, 'toolbar2', $defaultToolbar2, true);
                $this->_setSetting($cmsType, 'toolbar3', $defaultToolbar3, true);
                $this->_setSetting($cmsType, 'plugins',  $defaultPlugins,  true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_full');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($cmsType, $sKey, $sValue, true);
                }
                break;

            case "fullscreen": // Show all options
                // fullscreen of inline-editor
                if ('CMS_HTMLHEAD' === $cmsType) {
                    $defaultToolbar1 = cTinymce4Configuration::get('undo redo | consave conclose', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar1');
                    $defaultToolbar2 = cTinymce4Configuration::get('', 'tinymce4',$cmsType, 'tinymce4_fullscreen', 'toolbar2');
                    $defaultToolbar3 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar3');
                    $defaultPlugins = cTinymce4Configuration::get('conclose', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'plugins');
                } else {
                    $defaultToolbar1 = cTinymce4Configuration::get('cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar1');
                    $defaultToolbar2 = cTinymce4Configuration::get('link unlink anchor image media hr | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar2');
                    $defaultToolbar3 = cTinymce4Configuration::get('table | formatselect fontselect fontsizeselect | consave conclose', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar3');
                    $defaultPlugins = cTinymce4Configuration::get('charmap code table conclose hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'plugins');
                }
                $this->_setSetting($cmsType, 'inline', false, true);
                $this->_setSetting($cmsType, 'menubar', true, true);
                $this->_setSetting($cmsType, 'toolbar1', $defaultToolbar1, true);
                $this->_setSetting($cmsType, 'toolbar2', $defaultToolbar2, true);
                $this->_setSetting($cmsType, 'toolbar3', $defaultToolbar3, true);
                // load some plugins
                $this->_setSetting($cmsType, 'plugins', $defaultPlugins, true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_fullscreen');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($cmsType, $sKey, $sValue, true);
                }

                break;

            case "simple": // Does not show font and table options
                $this->_setSetting($cmsType, "toolbar1", "cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview", true);
                $this->_setSetting($cmsType, "toolbar2", "link unlink anchor image | bullist numlist | outdent indent | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code", true);
                $this->_setSetting($cmsType, "toolbar3", "", true);

                $this->_setSetting($cmsType, "plugins", "anchor charmap code insertdatetime preview searchreplace print contextmenu paste directionality textcolor", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_simple');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($cmsType, $sKey, $sValue, true);
                }

                break;

            case "mini": // Minimal toolbar
                $this->_setSetting($cmsType, "toolbar1", "undo redo | bold italic underline strikethrough | link", true);
                $this->_setSetting($cmsType, "toolbar2", "", true);
                $this->_setSetting($cmsType, "toolbar3", "", true);

                $this->_setSetting($cmsType, "plugins", "contextmenu", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_mini');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($cmsType, $sKey, $sValue, true);
                }

                break;

            case "custom": // Custom toolbar
                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_custom');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($cmsType, $sKey, $sValue, true);
                }

                break;

            case "inline_edit":
                if ('CMS_HTMLHEAD' === $cmsType) {
                    $defaultToolbar1 = cTinymce4Configuration::get('undo redo | consave conclose', 'tinymce4', $cmsType, 'tinymce4_inline', 'toolbar1');
                    $defaultToolbar2 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_inline', 'toolbar2');
                    $defaultToolbar3 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_inline', 'toolbar3');
                    $defaultPlugins = cTinymce4Configuration::get('conclose', 'tinymce4', $cmsType, 'tinymce4_inline', 'plugins');
                } else {
                    $defaultToolbar1 = cTinymce4Configuration::get('bold italic underline strikethrough | undo redo | bullist numlist separator forecolor backcolor | alignleft aligncenter alignright | confullscreen | consave conclose', 'tinymce4', $cmsType, 'tinymce4_inline', 'plugins');
                    $defaultToolbar2 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_inline', 'toolbar2');
                    $defaultToolbar3 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_inline', 'toolbar3');
                    $defaultPlugins = cTinymce4Configuration::get('conclose confullscreen media table textcolor', 'tinymce4', $cmsType, 'tinymce4_inline', 'plugins');
                }
                $this->_setSetting($cmsType, 'inline', true, true);
                $this->_setSetting($cmsType, 'menubar', false, true);
                $this->_setSetting($cmsType, 'toolbar1', $defaultToolbar1, true);
                $this->_setSetting($cmsType, 'toolbar2', $defaultToolbar2, true);
                $this->_setSetting($cmsType, 'toolbar3', $defaultToolbar3, true);


                $this->_unsetSetting($cmsType, "width");
                $this->_setSetting($cmsType, "height", "210px", true);

                // use custom plugins
                // they are specified in con_tiny.js
                // close plugin: save and close button
                // confullscreen plugin: switches inline mode to off and adjusts toolbar in fullscreen mode
                $this->_setSetting($cmsType, "plugins", $defaultPlugins, true);

                // fullscreen plugin does not work with inline turned on, custom plugin confullscreen required for this
                $this->_setSetting($cmsType, 'inline', true);
                $this->_setSetting($cmsType, 'menubar', false);
                $this->_setSetting($cmsType, "content_css", $cfgClient[$client]["path"]["htmlpath"] . "css/style_tiny.css", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_inline');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($cmsType, $sKey, $sValue, true);
                }

                break;

            default: // Default options
                $this->_setSetting($cmsType, 'toolbar1', 'undo redo | bold italic underline strikethrough | link unlink anchor image | table', true);
                $this->_setSetting($cmsType, 'toolbar2', 'styleselect | bullist numlist | outdent indent | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | subscript superscript | code', true);
                $this->_setSetting($cmsType, 'toolbar3', "", true);
                $this->_setSetting($cmsType, 'plugins', "anchor code contextmenu media paste table searchreplace textcolor", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce_default');
                foreach ($aCustSettings as $sKey => $sValue) {
                    $this->_setSetting($cmsType, $sKey, $sValue, true);
                }
        }
    }

    function cleanURLs($cmsType) {
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
            if (array_key_exists($sParameter, $this->_aSettings[$cmsType])) {
                $this->_setSetting($cmsType, $sParameter, $this->addPath($this->_aSettings[$cmsType][$sParameter]), true);
            }
        }

        // Session for template and media support files that are written in PHP
        $aParameters = array(
            'media_external_list_url', //media plugin
            'template_external_list_url' //template plugin
        );

        foreach ($aParameters as $sParameter) {
            if (array_key_exists($sParameter, $this->_aSettings[$cmsType]) && preg_match('/\\.php$/i', $this->_aSettings[$cmsType][$sParameter])) {
                $this->_setSetting($cmsType, $sParameter, $this->_aSettings[$cmsType][$sParameter] . '?contenido=' . $sess->id, true);
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


        // Set browser windows
        // Difference between file and image browser is with (file) or without categories/articles (image)
        $oTemplate = new cTemplate();

        $oTemplate->set('s', 'CONFIG', json_encode($this->_aSettings));

        $oTemplate->set('s', 'PATH_CONTENIDO_FULLHTML', cRegistry::getConfigValue('path', 'contenido_fullhtml'));
        $oTemplate->set('s', 'IMAGEBROWSER', $cfg["path"]["contenido_fullhtml"] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
        $oTemplate->set('s', 'FILEBROWSER', $cfg["path"]["contenido_fullhtml"] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
        $oTemplate->set('s', 'MEDIABROWSER', $cfg["path"]["contenido_fullhtml"] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
        $oTemplate->set('s', 'FRONTEND_PATH', $cfgClient[$client]["path"]["htmlpath"]);
        $oTemplate->set('s', 'CLOSE', html_entity_decode(i18n('Close editor'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
        $oTemplate->set('s', 'SAVE', html_entity_decode(i18n('Close editor and save changes'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
        $oTemplate->set('s', 'QUESTION', html_entity_decode(i18n('You have unsaved changes.'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
        $oTemplate->set('s', 'BACKEND_URL', cRegistry::getBackendUrl());

        $oTxtEditor = new cHTMLTextarea($this->_sEditorName, $this->_sEditorContent);
        $oTxtEditor->setId($this->_sEditorName);
        $oTxtEditor->setClass(htmlentities($this->_sEditorName));

        $oTxtEditor->setStyle("width: " . $this->_aSettings["width"] . "; height: " . $this->_aSettings["height"] . ";");

        $sReturn = $oTemplate->generate($cfg['path']['all_wysiwyg'] . $this->_sEditor . "contenido/templates/template.tinymce_tpl.html", true);
        $sReturn .= $oTxtEditor->render();

        return $sReturn;
    }

    /**
     * Sets given setting if setting was not yet defined.
     * Overwriting defined setting can be achieved with $bForceSetting = true.
     * 
     * @param string $sType CMS type where setting should apply
     * @param string $sKey of setting to set
     * @param string $sValue of setting to set
     * @param bool $bForceSetting to overwrite defined setting
     */
    protected function _setSetting($sType, $sKey, $sValue, $bForceSetting = false) {
        if ($bForceSetting || !array_key_exists($sKey, $this->_aSettings[$sType])) {
            $this->_aSettings[$sType][$sKey] = $sValue;
        }
    }

    /**
     * Variadic function to unset a setting using multiple key values
     * @param string $sKey
     */
    protected function _unsetSetting() {
        $numargs = func_num_args();
        // if no args passed there is nothing to do
        if (0 === $numargs) {
            return;
        }

        $result = &$this->_aSettings;
        for ($i = 0; $i < $numargs -1; $i++) {
            // if key does not exist there is nothing to unset
            if (false === in_array(func_get_arg(1 + $i), $this->_aSettings)) {
                return;
            }
            // jump one array level deeper into the result
            $result = $result[func_get_arg(1 + $i)];
        }

        // remove key from array
        unset($result);
    }
    

    public function getConfigInlineEdit() {
        $sConfig = '';

        foreach($this->_aSettings as $cmsType => $setting) {
            $this->setToolbar($cmsType, 'inline_edit');
        }

        return $this->_aSettings;

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
        foreach($this->_aSettings as $cmsType => $setting) {
            $this->setToolbar($cmsType, 'fullscreen');
        }

        return $this->_aSettings;

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

    /**
     * Saves configuration of WYSIWYG editor into a file
     * This function does not validate input! This has to be done by classes that extend cWYSIWYGEditor
     * because this class does not know what each WYSIWYG editor expects.
     * @param array Array with configuration values for the current WYSIWYG editor to save
     * @return array Array with values that were not accepted
     */
    public static function safeConfig($config) {
        parent::safeConfig($config['tinymce4']);
    }
}

?>