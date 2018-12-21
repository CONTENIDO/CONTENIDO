<?php
/**
 * This file contains the WYSIWYG editor class for TinyMCE.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Thomas Stauer
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.lang.php');

/**
 * The object cTinyMCE4Editor is a wrapper class to the TinyMCE WYSIWYG
 * Editor.
 *
 * Attributes can be defined to generate JavaScript options and
 * functions to initialise the global tinymce object in file
 * ./contenido/external/wysiwyg/tinymce4/contenido/templates/template.tinymce_tpl.html.
 *
 * All settings accepted by tinyMCE and its plugins may be specified
 * using system, client, group or user property/setting.
 *
 * The following parameters will be always set on initialization
 * (even, if they have been specified as property.
 * They can be set using setSetting later on, if needed):
 *
 * <ul>
 * <li>document_base_url
 * <li>cleanup_callback (-> XHTML)
 * <li>file_browser_callback
 * <li>external_link_list_url
 * <li>external_image_list_url
 * </ul>
 *
 * The following settings are only used in CONTENIDO:
 *
 * <ul>
 * <li>contenido_toolbar_mode: full, simple, mini, custom
 * <li>contenido_lists: link,image
 * <li>contenido_height_html
 * <li>contenido_height_head
 * </ul>
 *
 * See backend.customizing.html for details
 *
 * @package    Core
 * @subpackage Backend
 */
class cTinyMCE4Editor extends cWYSIWYGEditor {

    /**
     * Stores base url of page
     *
     * @var string
     */
    private $_baseURL;

    /**
     * Stores, if GZIP compression will be used
     *
     * @var bool
     */
    private $_useGZIP = false;

    /**
     * Shortcut to content types tinymce is mapped to
     *
     * @var array
     */
    private $_cmsTypes = array();

    /**
     * Access key under which the wysiwyg editor settings will be stored
     *
     * @var string
     */
    protected static $_configPrefix = '[\'wysiwyg\'][\'tinymce4\']';

    /**
     *
     * @param string $editorName
     * @param string $editorContent
     */
    public function __construct($editorName, $editorContent) {

        $belang = cRegistry::getBackendLanguage();
        $client = cRegistry::getClientId();
        $cfgClient = cRegistry::getClientConfig();
        $lang = cRegistry::getLanguageId();
        $idart = cRegistry::getArticleId();

        parent::__construct($editorName, $editorContent);
        $this->_setEditor("tinymce4");
        $this->_aSettings = array();

        // Retrieve all settings for tinymce 4
        $this->_aSettings = cTinymce4Configuration::get(array(), 'tinymce4');

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

            if (false === isset($this->_aSettings[$curType])) {
                $this->_aSettings[$curType] = array();
            }
            // cache allowed cms types
            $this->_cmsTypes[$curType] = true;
        }

        // apply global settings to all cms-types
        foreach ($this->_aSettings as $curSettingKey => $curSetting) {
            // if current setting is not a cms type
            if (false === array_key_exists($curSettingKey, $this->_cmsTypes)) {
                // copy current setting into all cms types
                // if there is such setting already set for the cms type
                // (already set cms type specific values override global config values)
                foreach ($this->_cmsTypes as $curTypeKey => $curType) {
                    if (false === isset($this->_aSettings[$curType])) {
                        $this->_aSettings[$curTypeKey][$curSettingKey] = $curSetting;
                   }
                }
                // remove global setting for further processing in con_tiny.js
                // that js-code assumes each setting key maps a cms type
                unset($this->_aSettings[$curSettingKey]);
            }
        }


        // CEC for template pre processing
        $this->_aSettings = cApiCecHook::executeAndReturn('Contenido.WYSIWYG.LoadConfiguration', $this->_aSettings, $this->_sEditor);

        // encode data to json when doing output instead of doing this here
        // this way we can manipulate data easier in PHP

        // process settings for each cms type
        foreach ($this->_aSettings as $cmsType => $setting) {
            // ignore any non cms type (do not process global settings)
            if (false === isset($this->_cmsTypes[$cmsType])) {
                continue;
            }
            $this->setSetting($cmsType, "article_url_suffix", 'front_content.php?idart=' . $idart, true);

            // Default values

            // apply editor to any cms type provided in preferences
            $this->setSetting($cmsType, 'selector', ('.' . $cmsType), true);

            $this->setSetting($cmsType, "content_css", $cfgClient[$client]["path"]["htmlpath"] . "css/style_tiny.css");

            $this->setSetting($cmsType, "theme", "modern");
            $this->setSetting($cmsType, "remove_script_host", false);

            $this->setSetting($cmsType, "urlconverter_callback", "Con.Tiny.customURLConverterCallback");
            // New in V3.x
            $this->setSetting($cmsType, "pagebreak_separator", "<!-- my page break -->"); // needs pagebreak plugin
            // Source formatting (ugh!)
            $this->setSetting($cmsType, "remove_linebreaks", false); // Remove linebreaks - GREAT idea...

            // Convert URLs and Relative URLs default
            $this->setSetting($cmsType, "convert_urls", false);
            $this->setSetting($cmsType, "relative_urls", false);

            // Editor language
            $aLangs = i18nGetAvailableLanguages();
            $this->setSetting($cmsType, "language", $aLangs[$belang][4]);
            unset($aLangs);

            // Set document base URL for all relative URLs
            // http://www.tinymce.com/wiki.php/Configuration:document_base_url
            $this->setSetting($cmsType, 'document_base_url', cRegistry::getFrontendUrl(), true);

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
            $this->setSetting($cmsType, "width", "100%");
            $this->setSetting($cmsType, "height", "480px");

            // Text direction (rtl = right to left)
            $sDirection = langGetTextDirection($lang);
            $this->setSetting($cmsType, "directionality", $sDirection);

            // Date and time formats
            $this->setSetting($cmsType, "plugin_insertdate_dateFormat", $this->convertFormat(getEffectiveSetting("dateformat", "date", "Y-m-d")));
            $this->setSetting($cmsType, "plugin_insertdate_timeFormat", $this->convertFormat(getEffectiveSetting("dateformat", "time", "H:i:s")));

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
                $this->setSetting($cmsType, 'valid_elements', '*[*]');
                $this->setSetting($cmsType, 'extended_valid_elements', '*[*]');
            }

            // default valid elements that tinymce is allowed to write
            // http://www.tinymce.com/wiki.php/Configuration:valid_elements
            $validElements = "a[name|href|target|title],strong/b[class],em/i[class],strike[class],u[class],p[dir|class|style],ol,ul,li[style],br,img[class|src|border=0|alt|title|hspace|vspace|width|height|style],sub,sup,blockquote[dir|style],table[border=0|cellspacing|cellpadding|width|height|class|style],tr[class|rowspan|width|height|valign|style],td[dir|class|colspan|rowspan|width|height|valign|style],div[dir|class|style],span[class|style],pre[class|style],address[class|style],h1[dir|class|style],h2[dir|class|style],h3[dir|class|style],h4[dir|class|style],h5[dir|class|style],h6[dir|class|style],hr";

            // media plugin
            $validElements .= "iframe[src|width|height],object[data|width|height|type],audio[controls|src],source[src|type],script[src],video[width|height|poster|controls]";

            // pass valid elements to tinymce
            $this->setSetting($cmsType, "valid_elements", $validElements);

            // Extended valid elements, for compatibility also accepts "tinymce-extended-valid-elements"
            if (!array_key_exists("extended_valid_elements", $this->_aSettings[$cmsType]) && array_key_exists("tinymce-extended-valid-elements", $this->_aSettings[$cmsType])) {
                $this->setSetting($cmsType, "extended_valid_elements", $this->_aSettings["tinymce-extended-valid-elements"]);
            }

            $this->setSetting($cmsType, "extended_valid_elements", "form[name|action|method],textarea[name|style|cols|rows],input[type|name|value|style|onclick],a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|style|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|style]");

            // Clean all possible URLs
            $this->cleanURLs($cmsType);

            // Remove CONTENIDO specific settings
            unset($this->_aSettings[$cmsType]["contenido_toolbar_mode"], $this->_aSettings[$cmsType]["contenido_lists"]);
            // Remove obsolete, deprecated values
            unset($this->_aSettings[$cmsType]["tinymce-stylesheet-file"], $this->_aSettings[$cmsType]["tinymce-valid-elements"], $this->_aSettings[$cmsType]["tinymce-extended-valid-elements"], $this->_aSettings[$cmsType]["tinymce-lists"], $this->_aSettings[$cmsType]["tinymce-styles"], $this->_aSettings[$cmsType]["tinymce-toolbar-mode"], $this->_aSettings[$cmsType]["tinymce-toolbar1"], $this->_aSettings[$cmsType]["tinymce-toolbar2"], $this->_aSettings[$cmsType]["tinymce-toolbar3"], $this->_aSettings[$cmsType]["tinymce4-plugins"]);
        }
    }

    /**
     *
     * @param string $sInput
     * @return string
     */
    public function convertFormat($sInput) {
        $aFormatCodes = array(
            "y" => "%y", "Y" => "%Y", "d" => "%d", "m" => "%m", "H" => "%H", "h" => "%I", "i" => "%M", "s" => "%S", "a" => "%P", "A" => "%P"
        );

        foreach ($aFormatCodes as $sFormatCode => $sReplacement) {
            $sInput = str_replace($sFormatCode, $sReplacement, $sInput);
        }

        return $sInput;
    }

    /**
     *
     * @param string $sType
     */
    public function setUserDefinedStyles($sType) {
        $sStyles = "";

        // convert tinymce's style formats from string to required JSON value
        // http://www.tinymce.com/wiki.php/Configuration:style_formats
        if(true === isset($this->_aSettings[$sType])
        && true === isset($this->_aSettings[$sType][$sType])) {
            if (array_key_exists('style_formats', $this->_aSettings[$sType][$sType])) {
                $sStyles = $this->_aSettings[$sType]["style_formats"];
                if (cString::getStringLength($sStyles) > 0) {
                    // if json can be decoded
                    if (null !== json_decode($sStyles)) {
                        $this->setSetting($sType, 'style_formats', json_decode($sStyles), true);
                    }
                }
            }
        }
    }

    /**
     * The special name "contenido_lists"
     *
     * @param string $sType
     *        CMS type where XHTML mode setting wil be applies
     */
    public function setLists($sType) {
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        $aLists = array();
        if (array_key_exists("contenido_lists", $this->_aSettings[$sType])) {
            $aLists = $this->_aSettings[$sType]["contenido_lists"];
        }

        // check if link list is activated
        if (true === isset($aLists['link'])) {
            $this->setSetting($sType, 'link_list', $this->_baseURL . 'contenido/ajax/class.tinymce_list.php?mode=link&lang=' . $lang . '&client=' . $client . '#', true);
        }
        // check if image list is activated
        if (true === isset($aLists['image'])) {
            $this->setSetting($sType, 'image_list', $this->_baseURL . 'contenido/ajax/class.tinymce_list.php?mode=image&lang=' . $lang . '&client=' . $client . '#', true);
        }
        // media list does not exist in tinymce 4, media plugin still available though
    }

    /**
     * Turn XHTML mode on or off
     *
     * @param string $sType
     *        CMS type where XHTML mode setting wil be applies
     * @param string
     *        $bEnabled Whether to turn on XHTML mode
     */
    public function setXHTMLMode($sType, $bEnabled = true) {
        if ($bEnabled) {
            $this->setSetting($sType, 'cleanup_callback', '', true);
        } else {
            $this->setSetting($sType, 'cleanup_callback', 'Con.Tiny.customCleanupCallback', true);
        }
    }

    /**
     * Set if editor should be loaded using tinymce4's gzip compression
     *
     * @param string $bEnabled
     */
    private function setGZIPMode($bEnabled = true) {
        if ($bEnabled) {
            $this->_useGZIP = true;
        } else {
            $this->_useGZIP = false;
        }
    }

    /**
     *
     * @return boolean
     *         if editor is loaded using gzip compression
     */
    public function getGZIPMode() {
        return (bool) $this->_useGZIP;
    }

    /**
     * For compatibility also accepts "tinymce-toolbar-mode",
     * "tinymce-toolbar1-3" and "tinymce4-plugins".
     *
     * @param string $cmsType
     * @param string $mode
     */
    public function setToolbar($cmsType, $mode = "") {

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

        switch ($mode) {
            case "full": // Show all options
                if ('CMS_HTMLHEAD' === $cmsType) {
                    $defaultToolbar1 = cTinymce4Configuration::get('undo redo | consave conclose', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar1');
                    $defaultToolbar2 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar2');
                    $defaultToolbar3 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar3');
                    $defaultPlugins = cTinymce4Configuration::get('conclose', 'tinymce4', $cmsType, 'tinymce4_full', 'plugins');
                    $this->setSetting($cmsType, 'menubar', false, true);
                } else {
                    $defaultToolbar1 = cTinymce4Configuration::get('cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar1');
                    $defaultToolbar2 = cTinymce4Configuration::get('link unlink anchor image media hr | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar2');
                    $defaultToolbar3 = cTinymce4Configuration::get('table | formatselect fontselect fontsizeselect | consave conclose', 'tinymce4', $cmsType, 'tinymce4_full', 'toolbar3');
                    $defaultPlugins = cTinymce4Configuration::get('charmap code conclose table conclose hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor', 'tinymce4', $cmsType, 'tinymce4_full', 'plugins');
                }
                $this->setSetting($cmsType, 'inline', false, true);
                $this->setSetting($cmsType, 'toolbar1', $defaultToolbar1, true);
                $this->setSetting($cmsType, 'toolbar2', $defaultToolbar2, true);
                $this->setSetting($cmsType, 'toolbar3', $defaultToolbar3, true);
                $this->setSetting($cmsType, 'plugins',  $defaultPlugins,  true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_full');
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting($cmsType, $key, $value, true);
                }
                break;

            case "fullscreen": // Show all options
                // fullscreen of inline-editor
                if ('CMS_HTMLHEAD' === $cmsType) {
                    $defaultToolbar1 = cTinymce4Configuration::get('undo redo | consave conclose', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar1');
                    $defaultToolbar2 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar2');
                    $defaultToolbar3 = cTinymce4Configuration::get('', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar3');
                    $defaultPlugins = cTinymce4Configuration::get('conclose', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'plugins');
                } else {
                    $defaultToolbar1 = cTinymce4Configuration::get('cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview | visualchars nonbreaking template pagebreak | help | fullscreen', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar1');
                    $defaultToolbar2 = cTinymce4Configuration::get('link unlink anchor image media hr | bullist numlist | outdent indent blockquote | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar2');
                    $defaultToolbar3 = cTinymce4Configuration::get('table | formatselect fontselect fontsizeselect | consave conclose', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'toolbar3');
                    $defaultPlugins = cTinymce4Configuration::get('charmap code table conclose hr image link pagebreak layer insertdatetime preview anchor media searchreplace print contextmenu paste directionality fullscreen visualchars nonbreaking template textcolor', 'tinymce4', $cmsType, 'tinymce4_fullscreen', 'plugins');
                }
                $this->setSetting($cmsType, 'inline', false, true);
                $this->setSetting($cmsType, 'menubar', true, true);
                $this->setSetting($cmsType, 'toolbar1', $defaultToolbar1, true);
                $this->setSetting($cmsType, 'toolbar2', $defaultToolbar2, true);
                $this->setSetting($cmsType, 'toolbar3', $defaultToolbar3, true);
                // load some plugins
                $this->setSetting($cmsType, 'plugins', $defaultPlugins, true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_fullscreen');

                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting($cmsType, $key, $value, true);
                }

                break;

            case "simple": // Does not show font and table options
                $this->setSetting($cmsType, "toolbar1", "cut copy paste pastetext | searchreplace | undo redo | bold italic underline strikethrough subscript superscript | insertdatetime preview", true);
                $this->setSetting($cmsType, "toolbar2", "link unlink anchor image | bullist numlist | outdent indent | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | ltr rtl | charmap | code", true);
                $this->setSetting($cmsType, "toolbar3", "", true);

                $this->setSetting($cmsType, "plugins", "anchor charmap code insertdatetime preview searchreplace print contextmenu paste directionality textcolor", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_simple');
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting($cmsType, $key, $value, true);
                }

                break;

            case "mini": // Minimal toolbar
                $this->setSetting($cmsType, "toolbar1", "undo redo | bold italic underline strikethrough | link", true);
                $this->setSetting($cmsType, "toolbar2", "", true);
                $this->setSetting($cmsType, "toolbar3", "", true);

                $this->setSetting($cmsType, "plugins", "contextmenu", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_mini');
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting($cmsType, $key, $value, true);
                }

                break;

            case "custom": // Custom toolbar
                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_custom');
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting($cmsType, $key, $value, true);
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
                $this->setSetting($cmsType, 'inline', true, true);
                $this->setSetting($cmsType, 'menubar', false, true);
                $this->setSetting($cmsType, 'toolbar1', $defaultToolbar1, true);
                $this->setSetting($cmsType, 'toolbar2', $defaultToolbar2, true);
                $this->setSetting($cmsType, 'toolbar3', $defaultToolbar3, true);


                $this->_unsetSetting($cmsType, "width");
                $this->setSetting($cmsType, "height", "210px", true);

                // use custom plugins
                // they are specified in con_tiny.js
                // close plugin: save and close button
                // confullscreen plugin: switches inline mode to off and adjusts toolbar in fullscreen mode
                $this->setSetting($cmsType, "plugins", $defaultPlugins, true);

                // fullscreen plugin does not work with inline turned on, custom plugin confullscreen required for this
                $this->setSetting($cmsType, 'inline', true);
                $this->setSetting($cmsType, 'menubar', false);
                $this->setSetting($cmsType, "content_css", $cfgClient[$client]["path"]["htmlpath"] . "css/style_tiny.css", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce4_inline');
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting($cmsType, $key, $value, true);
                }

                break;

            default: // Default options
                $this->setSetting($cmsType, 'toolbar1', 'undo redo | bold italic underline strikethrough | link unlink anchor image | table', true);
                $this->setSetting($cmsType, 'toolbar2', 'styleselect | bullist numlist | outdent indent | alignleft aligncenter alignright alignfull removeformat | forecolor backcolor | subscript superscript | code', true);
                $this->setSetting($cmsType, 'toolbar3', "", true);
                $this->setSetting($cmsType, 'plugins', "anchor code contextmenu media paste table searchreplace textcolor", true);

                $aCustSettings = cTinymce4Configuration::get(array(), 'tinymce4', $cmsType, 'tinymce_default');
                foreach ($aCustSettings as $key => $value) {
                    $this->setSetting($cmsType, $key, $value, true);
                }
        }
    }

    /**
     *
     * @param string $cmsType
     */
    public function cleanURLs($cmsType) {

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
            if (array_key_exists($sParameter, $this->_aSettings[$cmsType])) {
                $this->setSetting($cmsType, $sParameter, $this->addPath($this->_aSettings[$cmsType][$sParameter]), true);
            }
        }

        // Session for template and media support files that are written in PHP
        $aParameters = array(
            'media_external_list_url', //media plugin
            'template_external_list_url' //template plugin
        );

        foreach ($aParameters as $sParameter) {
            if (array_key_exists($sParameter, $this->_aSettings[$cmsType]) && preg_match('/\\.php$/i', $this->_aSettings[$cmsType][$sParameter])) {
                $this->setSetting($cmsType, $sParameter, $this->_aSettings[$cmsType][$sParameter] . '?contenido=' . $sess->id, true);
            }
        }
    }

    /**
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
                $file = $cfgClient[$client]["htmlpath"]["frontend"] . $file;
            }
        }

        return $file;
    }

    /**
     *
     * @param string $baseUrl
     */
    public function setBaseURL($baseUrl) {
        $this->_baseURL = $baseUrl;
    }

    /**
     *
     * @return string
     */
    public function getScripts() {
        if ($this->_useGZIP) {
            $return = "\n<!-- tinyMCE -->\n" . '<script language="javascript" type="text/javascript" src="' . $this->_baseURL . 'tinymce/js/tinymce/tinymce.gzip.js"></script>';
        } else {
            $return = "\n<!-- tinyMCE -->\n" . '<script language="javascript" type="text/javascript" src="' . $this->_baseURL . 'tinymce/js/tinymce/tinymce.min.js"></script>';
        }

        return $return;
    }

    /**
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

        // Set browser windows
        // Difference between file and image browser is with (file) or without categories/articles (image)
        $template = new cTemplate();

        $template->set('s', 'CONFIG', json_encode($this->_aSettings));

        $template->set('s', 'PATH_CONTENIDO_FULLHTML', cRegistry::getConfigValue('path', 'contenido_fullhtml'));
        $template->set('s', 'IMAGEBROWSER', $cfg["path"]["contenido_fullhtml"] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
        $template->set('s', 'FILEBROWSER', $cfg["path"]["contenido_fullhtml"] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
        $template->set('s', 'MEDIABROWSER', $cfg["path"]["contenido_fullhtml"] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
        $template->set('s', 'FRONTEND_PATH', $cfgClient[$client]["path"]["htmlpath"]);
        $template->set('s', 'CLOSE', html_entity_decode(i18n('Close editor'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
        $template->set('s', 'SAVE', html_entity_decode(i18n('Close editor and save changes'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
        $template->set('s', 'QUESTION', html_entity_decode(i18n('You have unsaved changes.'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
        $template->set('s', 'BACKEND_URL', cRegistry::getBackendUrl());

        $txtEditor = new cHTMLTextarea($this->_sEditorName, $this->_sEditorContent);
        $txtEditor->setId($this->_sEditorName);
        $txtEditor->setClass(htmlentities($this->_sEditorName));

        $txtEditor->setStyle("width: " . $this->_aSettings['width'] . "; height: " . $this->_aSettings['height'] . ";");

        $return = $template->generate($cfg['path']['all_wysiwyg'] . $this->_sEditor . "contenido/templates/template.tinymce_tpl.html", true);
        $return .= $txtEditor->render();

        return $return;
    }

    /**
     * Sets given setting if setting was not yet defined.
     * Overwriting defined setting can be achieved with
     * $bForceSetting = true.
     *
     * @param string $type
     *        CMS type where setting should apply
     * @param string $key
     *        of setting to set
     * @param string $value
     *        of setting to set
     * @param bool $forceSetting
     *      to overwrite defined setting
     */
    public function setSetting($type, $key, $value, $forceSetting = false) {
        if ($forceSetting || !array_key_exists($key, $this->_aSettings[$type])) {
            $this->_aSettings[$type][$key] = $value;
        }
    }

    /**
     * Variadic function to unset a setting using multiple key values.
     *
     * @param string $key Normaly unused (counterpart of cWYSIWYGEditor::_unsetSetting)
     */
    protected function _unsetSetting($key = '') {
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


    /**
     *
     * @return string
     */
    public function getConfigInlineEdit() {
        // Unused
        // $config = '';

        foreach ($this->_cmsTypes as $cmsType => $setting) {
            $this->setToolbar($cmsType, 'inline_edit');
        }
        return $this->_aSettings;

        /*
         * Unused
       foreach ($this->_aSettings as $key => $value) {
            if (is_bool($value)) {
                if ($value === true) {
                    $value = 'true';
                } else {
                    $value = 'false';
                }
            }

            if ($value == "true" || $value == "false" || $key == "oninit" || $key == "onpageload" || $key == 'style_formats') {
                $config .= "'$key': " . $value;
            } else {
                $config .= "'$key': '" . $value . "'";
            }
            $config .= ",\n\t";
        }

        $config = cString::getPartOfString($config, 0, -3);
        return $config;
        */
    }

    /**
     *
     * @return array
     */
    public function getConfigFullscreen() {

        foreach ($this->_cmsTypes as $cmsType => $setting) {
            $this->setToolbar($cmsType, 'fullscreen');
        }

        return $this->_aSettings;

    }

    /**
     * Function to obtain a comma separated list of plugins that are
     * tried to be loaded.
     *
     * @return string
     *        plugins the plugins
     */
    public function getPlugins() {
        return cSecurity::toString($this->_aSettings['plugins']);
    }

    /**
     * Function to obtain a comma separated list of themes that are
     * tried to be loaded.
     *
     * @return string
     *        themes the themes
     */
    function getThemes() {
        return cSecurity::toString($this->_aSettings['theme']);
    }

    /**
     * Saves configuration of WYSIWYG editor into a file.
     *
     * This function does not validate input! This has to be done by
     * classes that extend cWYSIWYGEditor because this class does not
     * know what each WYSIWYG editor expects.
     *
     * @param array $config
     *        Array with configuration values for the current WYSIWYG
     *        editor to save
     * @return array
     *        Array with values that were not accepted
     */
    public static function saveConfig($config) {
        parent::saveConfig($config['tinymce4']);
    }
}
