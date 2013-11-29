<?php
/**
 * This file contains the generic page GUI class.
 *
 * @package Core
 * @subpackage GUI
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Generic page GUI class.
 * Manages HTML pages and provides functions for rendering them.
 *
 * @package Core
 * @subpackage GUI
 */
class cGuiPage {

    /**
     * The name of the page.
     * This will be used to load the template, stylesheets and scripts.
     *
     * @var string
     */
    protected $_pageName;

    /**
     * The name of the plugin of the current webpage.
     *
     * @var string
     */
    protected $_pluginName;

    /**
     * The general page template.
     *
     * @var cTemplate
     */
    protected $_pageTemplate;

    /**
     * The template for everything that is inside the body.
     * (Usually template.PAGENAME.html)
     *
     * @var cTemplate
     */
    protected $_contentTemplate;

    /**
     * An array of script names (inside /scripts/) which will be included in the
     * final page.
     *
     * @var array
     */
    protected $_scripts;

    /**
     * An array of stylesheets (inside /styles/) which will be included in the
     * final page.
     *
     * @var array
     */
    protected $_styles;

    /**
     * The script to set the subnavigation.
     * This will be included in the final page.
     *
     * @var string
     */
    protected $_subnav;

    /**
     * The script to markup the current submenu item.
     * This will be included in the final page.
     *
     * @var string
     */
    protected $_markScript;

    /**
     * An error message which will be used to display an error with the help of
     * cGuiNotification
     *
     * @var string
     */
    protected $_error;

    /**
     * A warning which will be used to display an error with the help of
     * cGuiNotification
     *
     * @var string
     */
    protected $_warning;

    /**
     * An info which will be used to display an error with the help of
     * cGuiNotification
     *
     * @var unknown_type
     */
    protected $_info;

    /**
     * If true, just display the message and don't render the template
     *
     * @var bool
     */
    protected $_abort;

    /**
     * An array of cHTML objects which will be rendered instead of filling a
     * template.
     *
     * @var array
     */
    protected $_objects;

    /**
     * Array of arrays where each array contains information about a meta tag.
     *
     * @var array
     */
    protected $_metaTags;

    /**
     * Array of class attribute values for body tag.
     *
     * @var array
     */
    protected $_bodyClassNames;

    /**
     * The constructor initializes the class and tries to get the encoding from
     * the currently selected language.
     * It will also add every script in the form of /scripts/*.PAGENAME.js and
     * every stylesheet in the form
     * of/styles/*.PAGENAME.css to the page as well as /scripts/PAGENAME.js and
     * /styles/PAGENAME.css.
     *
     * @param string $pageName The name of the page which will be used to load
     *        corresponding stylehseets, templates and scripts.
     * @param string $pluginName The name of the plugin in which the site is run
     * @param string $subMenu The number of the submenu which should be
     *        highlighted when this page is shown.
     */
    /**
     * Scripts and styles subfolder for cGuiPage objects
     *
     * @var string
     */
    const filesDirectory = 'includes/';

    public function __construct($pageName, $pluginName = '', $subMenu = '') {
        global $lang, $cfg, $sess;

        $this->_pageName = $pageName;
        $this->_pluginName = $pluginName;
        $this->_pageTemplate = new cTemplate();
        $this->_contentTemplate = new cTemplate();
        $this->_scripts = array();
        $this->_styles = array();
        $this->_subnav = '';
        $this->_markScript = '';
        $this->_error = '';
        $this->_warning = '';
        $this->_info = '';
        $this->_abort = false;
        $this->_objects = array();
        $this->_metaTags = array();
        $this->_bodyClassNames = array();

        // Try to extract the current CONTENIDO language
        $clang = new cApiLanguage($lang);

        if ($clang->isLoaded()) {
            $this->setEncoding($clang->get('encoding'));
        }

        $this->_pageTemplate->set('s', 'SUBMENU', $subMenu);
        $this->_pageTemplate->set('s', 'PAGENAME', $pageName);
        $pageid = str_replace('.', '_', $pageName);
        $this->_pageTemplate->set('s', 'PAGENAME', $pageName);
        $this->_pageTemplate->set('s', 'PAGEID', $pageid);

        $this->addBodyClassName('page_generic');
        $this->addBodyClassName('page_' . $pageid);

        if (cFileHandler::exists($cfg['path']['styles_includes'] . $pageName . '.css')) {
            $this->addStyle(cGuiPage::filesDirectory . $pageName . '.css');
        }

        /**
         *
         * @var $stylefile SplFileInfo
         */
        foreach (new DirectoryIterator($cfg['path']['styles_includes']) as $stylefile) {
            if (cString::endsWith($stylefile->getFilename(), '.' . $pageName . '.css')) {
                $this->addStyle(cGuiPage::filesDirectory . $stylefile->getFilename());
            }
        }

        if (cFileHandler::exists($cfg['path']['scripts_includes'] . $pageName . '.js')) {
            $this->addScript(cGuiPage::filesDirectory . $pageName . '.js');
        }

        /**
         *
         * @var $scriptfile SplFileInfo
         */
        foreach (new DirectoryIterator($cfg['path']['scripts_includes']) as $scriptfile) {
            if (cString::endsWith($scriptfile->getFilename(), '.' . $pageName . '.js')) {
                $this->addScript(cGuiPage::filesDirectory . $scriptfile->getFilename());
            }
        }
    }

    /**
     * Adds a script to the website - path can be absolute, relative to the
     * plugin scripts folder and relative to the CONTENIDO scripts folder.
     * NOTE: This function will also add inline JavaScript in the form of
     * "<script...". However this shouldn't be used.
     *
     * If the page was constructed in a plugin and the plugin name was given
     * in the constructor it will find the JS script in
     * plugins/PLUGINNAME/scripts/
     * too.
     *
     * @param string $script The filename of the script. It has to reside in
     *        /scripts/ in order to be found.
     */
    public function addScript($script) {
        $script = trim($script);
        if (empty($script)) {
            return;
        }

        $cfg = cRegistry::getConfig();
        $backendUrl = cRegistry::getBackendUrl();
        $backendPath = cRegistry::getBackendPath();
        $filePathName = $this->_getRealFilePathName($script);

        if (strpos(trim($script), 'http') === 0 || strpos(trim($script), '<script') === 0 || strpos(trim($script), '//') === 0) {
            if (strpos(trim($script), '<script') === 0) {
                cDeprecated("You shouldn't use inline JS for backend pages");
            }
            // the given script path is absolute
            $this->_scripts[] = $script;
        } else if (!empty($this->_pluginName) && cFileHandler::exists($backendPath . $cfg['path']['plugins'] . $this->_pluginName . '/' . $cfg['path']['scripts'] . $filePathName)) {
            // the given script path is relative to the plugin scripts folder
            $fullpath = $backendUrl . $cfg['path']['plugins'] . $this->_pluginName . '/' . $cfg['path']['scripts'] . $script;
            $this->_scripts[] = $fullpath;
        } else if (cFileHandler::exists($backendPath . $cfg['path']['scripts'] . $filePathName)) {
            // the given script path is relative to the CONTENIDO scripts folder
            $fullpath = $backendUrl . $cfg['path']['scripts'] . $script;

            $this->_scripts[] = $fullpath;
        }
    }

    /**
     * Adds a stylesheet to the website - path can be absolute, relative to the
     * plugin stylesheets folder and relative to the CONTENIDO stylesheets
     * folder.
     *
     * @param string $stylesheet The filename of the stylesheet. It has to
     *        reside in /styles/ in order to be found.
     */
    public function addStyle($stylesheet) {
        $stylesheet = trim($stylesheet);
        if (empty($stylesheet)) {
            return;
        }

        $cfg = cRegistry::getConfig();
        $backendUrl = cRegistry::getBackendUrl();
        $backendPath = cRegistry::getBackendPath();
        $filePathName = $this->_getRealFilePathName($stylesheet);

        if (strpos($stylesheet, 'http') === 0 || strpos($stylesheet, '//') === 0) {
            // the given stylesheet path is absolute
            $this->_styles[] = $stylesheet;
        } else if (!empty($this->_pluginName) && cFileHandler::exists($backendPath . $cfg['path']['plugins'] . $this->_pluginName . '/' . $cfg['path']['styles'] . $filePathName)) {
            // the given stylesheet path is relative to the plugin stylesheets
            // folder
            $fullpath = $backendUrl . $cfg['path']['plugins'] . $this->_pluginName . '/' . $cfg['path']['styles'] . $stylesheet;
            $this->_styles[] = $fullpath;
        } else if (cFileHandler::exists($backendPath . $cfg['path']['styles'] . $filePathName)) {
            // the given stylesheet path is relative to the CONTENIDO
            // stylesheets folder
            $fullpath = $backendUrl . $cfg['path']['styles'] . $stylesheet;
            $this->_styles[] = $fullpath;
        }
    }

    /**
     * Adds a meta tag to the website.
     *
     * @param array $meta Associative array with the meta tag attributes
     * @throws cInvalidArgumentException if an invalid attribute for the meta
     *         tag has been given
     * @return void
     */
    public function addMeta(array $meta) {
        $allowedAttributes = array(
            'charset',
            'content',
            'http-equiv',
            'name',
            'itemprop'
        );
        foreach ($meta as $key => $value) {
            if (!in_array($key, $allowedAttributes)) {
                throw new cInvalidArgumentException('Unallowed attribute for meta tag given - meta tag will be ignored!');
            }
        }
        $this->_metaTags[] = $meta;
    }

    /**
     * Adds class attribute value to the body tag.
     *
     * @param string $className
     */
    public function addBodyClassName($className) {
        if (!in_array($className, $this->_bodyClassNames)) {
            $this->_bodyClassNames[] = $className;
        }
    }

    /**
     * Loads the subnavigation of the current area upon rendering.
     *
     * @param string $additional Additional parameters the subnavigation might
     *        need. These have to look like "key=value&key2=value2..."
     * @param string $aarea The area of the subnavigation. If none is given the
     *        current area will be loaded
     */
    public function setSubnav($additional = '', $aarea = '') {
        global $area, $sess;

        if ($aarea == '') {
            $aarea = $area;
        }

        $this->_subnav = '
        <script type="text/javascript">
        Con.getFrame("right_top").location.href = "' . $sess->url("main.php?area={$aarea}&frame=3&{$additional}") . '";
        </script>
        ';
    }

    /**
     * Adds the reload script for the left_bottom frame to the website
     */
    public function setReload() {
        $this->_scripts[] = 'reload.js';
    }

    /**
     * Sets the markscript
     *
     * @param string $item The number of the submenu which should be marked.
     */
    public function setMarkScript($item) {
        $this->_markScript = markSubMenuItem($item, true);
    }

    /**
     * Sets the encoding of the website
     *
     * @param string $encoding An encoding which should be valid to use in the
     *        meta tag
     */
    public function setEncoding($encoding) {
        if (empty($encoding)) {
            return;
        }
        $this->_metaTags[] = array(
            'http-equiv' => 'Content-type',
            'content' => 'text/html;charset=' . $encoding
        );
    }

    /**
     * Applies a value to a key in the content template.
     *
     * @param string $type Either "s" or "d" for "static" or "dynamic" values
     * @param string $key The key which should be replaced
     * @param string $value The value which should replace the key
     * @see cTemplate::set()
     */
    public function set($type, $key, $value) {
        $this->_contentTemplate->set($type, $key, $value);
    }

    /**
     * Calls the next() method on the content template.
     *
     * @see cTemplate::next()
     */
    public function next() {
        $this->_contentTemplate->next();
    }

    /**
     * After calling this the page will only display messages and not render the
     * content template.
     * NOTE: You still have to call render() to actually show any messages
     */
    public function abortRendering() {
        $this->_abort = true;
    }

    /**
     * Displays an error message and aborts rendering after that
     * NOTE: You still have to call render() to actually show any messages
     *
     * @param string $msg A message
     */
    public function displayCriticalError($msg) {
        $this->_error = $msg;
        $this->_abort = true;
    }

    /**
     * Displays an error but the rendering of the content template will
     * continue.
     *
     * @param string $msg A message
     */
    public function displayError($msg) {
        $this->_error .= $msg . '<br>';
    }

    /**
     * Displays a warning
     *
     * @param string $msg The warning
     */
    public function displayWarning($msg) {
        $this->_warning .= $msg . '<br>';
    }

    /**
     * Displays an info
     *
     * @param string $msg The info message
     */
    public function displayInfo($msg) {
        $this->_info .= $msg . '<br>';
    }

    /**
     * Sets an array (or a single object) of cHTML objects which build up the
     * site instead of a content template.
     * NOTE: All these objects must have a render() method or else they won't be
     * shown
     *
     * @param array|object $objects An array of objects
     */
    public function setContent($objects) {
        if (!is_array($objects)) {
            $objects = array(
                $objects
            );
        }
        $this->_objects = $objects;
    }

    /**
     * Appends all cHTML objects in an array (or a single object) which build up
     * the
     * site instead of a content template.
     * NOTE: All these objects must have a render() method or else they won't be
     * shown
     *
     * @param array|object $objects An array of objects or a single object
     */
    public function appendContent($objects) {
        if (!is_array($objects)) {
            $this->_objects[] = $objects;
        } else {
            $this->_objects = array_merge($this->_objects, $objects);
        }
    }

    /**
     * Example:
     * setPluginScript('content_allocation', 'complexlist.js');
     * $this->_scripts[] =
     * 'http://contenido.local/contenido/plugins/content_allocation/scripts/complexlist.js';
     *
     * @param unknown_type $plugin
     * @param unknown_type $filename
     * @return Ambigous <string, mixed>
     */
    public function setPluginScript($plugin, $filename) {
        global $cfg;
        $path = $cfg['pica']['script_complexlist'];
    }

    public function setStyleDirect($filepath) {
        global $cfg;
        $path = $cfg['pica']['style_complexlist'];
        $this->_pageTemplate->set('s', 'SCRIPTS', $path);

        $strstyle = '';

        $strstyle .= "<link href='styles/" . $path . "' type='text/css' rel='stylesheet'>\n";

        $this->_pageTemplate->set('s', 'STYLES', $strstyle);
        return $this->_pageTemplate->generate($cfg['path']['templates'] . $cfg['templates']['generic_page'], false);
    }

    /**
     * Renders the page and either prints it or returns it
     *
     * @param cTemplate|NULL $template If set, use this content template instead
     *        of the default one
     * @param bool $return If true, the page will be returned instead of echoed
     * @return string void either the webpage or nothing
     */
    public function render($template = NULL, $return = false) {
        global $cfg;

        if ($template == NULL) {
            $template = $this->_contentTemplate;
        }

        // Render some parts like meta tags, scripts, styles, etc...
        $this->_renderMetaTags();
        $this->_renderScripts();
        $this->_renderStyles();

        // Set body class attribute values
        $this->_pageTemplate->set('s', 'PAGECLASS', implode(' ', $this->_bodyClassNames));

        // Get all messages for the content
        $text = $this->_renderContentMessages();

        if (!$this->_abort) {
            if (count($this->_objects) == 0) {
                $output = $this->_renderTemplate($template);
            } else {
                $output = $this->_renderObjects();
            }
            $this->_pageTemplate->set('s', 'CONTENT', $text . $output);
        } else {
            $this->_pageTemplate->set('s', 'CONTENT', $text);
        }

        return $this->_pageTemplate->generate($cfg['path']['templates'] . $cfg['templates']['generic_page'], $return);
    }

    /**
     * Renders set meta tags and adds them to _pageTemplate property.
     */
    protected function _renderMetaTags() {
        // render the meta tags
        // NB! We don't produce xhtml in the backend
        // $produceXhtml = getEffectiveSetting('generator', 'xhtml', 'false');
        $produceXhtml = false;
        $meta = '';
        foreach ($this->_metaTags as $metaTag) {
            $tag = '<meta';
            foreach ($metaTag as $key => $value) {
                $tag .= ' ' . $key . '="' . $value . '"';
            }
            if ($produceXhtml) {
                $tag .= ' /';
            }
            $tag .= ">\n";
            $meta .= $tag;
        }
        if (!empty($meta)) {
            $this->_pageTemplate->set('s', 'META', $meta);
        } else {
            $this->_pageTemplate->set('s', 'META', '');
        }
    }

    /**
     * Renders set scripts and adds them to _pageTemplate property.
     */
    protected function _renderScripts() {
        $strscript = $this->_subnav . "\n" . $this->_markScript . "\n";
        foreach ($this->_scripts as $script) {
            if (strpos($script, 'http') === 0 || strpos($script, '//') === 0) {
                $strscript .= '<script type="text/javascript" src="' . $script . '"></script>' . "\n";
            } else if (strpos($script, '<script') === false) {
                $strscript .= '<script type="text/javascript" src="scripts/' . $script . '"></script>' . "\n";
            } else {
                $strscript .= $script;
            }
        }
        $this->_pageTemplate->set('s', 'SCRIPTS', $strscript);
    }

    /**
     * Renders set styles and adds them to _pageTemplate property.
     */
    protected function _renderStyles() {
        $strstyle = '';
        foreach ($this->_styles as $style) {
            if (strpos($style, 'http') === 0 || strpos($style, '//') === 0) {
                $strstyle .= '<link href="' . $style . '" type="text/css" rel="stylesheet">' . "\n";
            } else {
                $strstyle .= '<link href="styles/' . $style . '" type="text/css" rel="stylesheet">' . "\n";
            }
        }
        $this->_pageTemplate->set('s', 'STYLES', $strstyle);
    }

    /**
     * Renders text for all available content messages and returns the assembled
     * message
     * string.
     *
     * @return string
     */
    protected function _renderContentMessages() {
        global $notification;

        // Get messages from cRegistry
        $infoMessages = cRegistry::getInfoMessages();
        foreach ($infoMessages as $message) {
            $this->displayInfo($message);
        }

        $errorMessages = cRegistry::getErrorMessages();
        foreach ($errorMessages as $message) {
            $this->displayError($message);
        }

        $warningMessages = cRegistry::getWarningMessages();
        foreach ($warningMessages as $message) {
            $this->displayWarning($message);
        }

        $text = '';
        if ($this->_info != '') {
            $text .= $notification->returnNotification('info', $this->_info) . '<br>';
        }
        if ($this->_warning != '') {
            $text .= $notification->returnNotification('warning', $this->_warning) . '<br>';
        }
        if ($this->_error != '') {
            $text .= $notification->returnNotification('error', $this->_error) . '<br>';
        }

        return $text;
    }

    /**
     * Loops through all defined objects, calls their render function,
     * collects the output of the objects and returns it back.
     *
     * @return string
     */
    protected function _renderObjects() {
        $output = '';

        foreach ($this->_objects as $obj) {
            if (!method_exists($obj, 'render')) {
                continue;
            }

            // Ridiculous workaround because some objects return
            // code if the parameter is true and some return the
            // code if the parameter is false.
            $oldOutput = $output;
            ob_start(); // We don't want any code outside the body
                        // (in case the object outputs directly we
                        // will catch this output)
            $output .= $obj->render(false);
            // We get the code either directly or via the output
            $output .= ob_get_contents();
            if ($oldOutput == $output) {
                cWarning(__FILE__, __LINE__, "Rendering this object (" . print_r($obj, true) . ") doesn't seem to have any effect.");
            }
            ob_end_clean();
        }

        return $output;
    }

    /**
     * Renders template of a page or of a plugin and returns the output back
     *
     * @param cTemplate $template
     * @return string
     */
    protected function _renderTemplate($template) {
        $cfg = cRegistry::getConfig();

        $file = '';
        if ($this->_pluginName == '') {
            $file = $cfg['path']['templates'] . 'template.' . $this->_pageName . '.html';
        } else {
            $file = $cfg['path']['plugins'] . $this->_pluginName . '/templates/template.' . $this->_pageName . '.html';
        }

        if (cFileHandler::exists($file)) {
            $output = $template->generate($file, true);
        } else {
            $output = '';
        }

        return $output;
    }

    /**
     * Returns only the path and name of the given file.
     * Some JS or CSS file URLs may contain a query part, like
     * "/path/to/file.js.php?contenido=12234"
     * and this function returns only the path part "/path/to/file.js.php" of
     * it.
     *
     * @param string $file
     * @return string
     */
    protected function _getRealFilePathName($file) {
        $tmp = explode('?', $file);
        return $tmp[0];
    }

}