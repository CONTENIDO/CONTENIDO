<?php
/**
 * This file contains the cContentTypeLinkeditor class.
 *
 * @package Core
 * @subpackage ContentType
 * @version SVN Revision $Rev:$
 *
 * @author Fulai Zhang, Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.upl.php');

/**
 * Content type CMS_LINKEDITOR which lets the editor select a link.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeLinkeditor extends cContentTypeAbstractTabbed {

    /**
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *        plaintext
     * @param int $id ID of the content type, e.g. 3 if CMS_DATE[3] is
     *        used
     * @param array $contentTypes array containing the values of all content
     *        types
     */
    function __construct($rawSettings, $id, array $contentTypes) {
        // set attributes of the parent class and call the parent constructor
        $this->_type = 'CMS_LINKEDITOR';
        $this->_prefix = 'linkeditor';
        $this->_settingsType = self::SETTINGS_TYPE_XML;
        $this->_formFields = array(
            'linkeditor_type',
            'linkeditor_externallink',
            'linkeditor_title',
            'linkeditor_newwindow',
            'linkeditor_idart',
            'linkeditor_filename'
        );

        // encoding conversions to avoid problems with umlauts
        $rawSettings = conHtmlEntityDecode($rawSettings);
        $rawSettings = utf8_encode($rawSettings);
        parent::__construct($rawSettings, $id, $contentTypes);
        $this->_settings['linkeditor_title'] = utf8_decode($this->_settings['linkeditor_title']);
        $this->_settings['linkeditor_title'] = conHtmlentities($this->_settings['linkeditor_title']);

        // if form is submitted, store the current teaser settings
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        if (isset($_POST['linkeditor_action']) && $_POST['linkeditor_action'] === 'store' && isset($_POST['linkeditor_id']) && (int) $_POST['linkeditor_id'] == $this->_id) {
            // use htmlentities for the title
            // otherwise umlauts will crash the XML parsing
            $_POST['linkeditor_title'] = conHtmlentities(conHtmlEntityDecode($_POST['linkeditor_title']));
            $this->_storeSettings();
        }
    }

    /**
     * Returns the link type ('external', 'internal' or 'file')
     *
     * @return string
     */
    public function getLinkType() {
        return $this->_settings['linkeditor_type'];
    }

    /**
     * Returns the link title
     *
     * @return string
     */
    public function getTitle() {
        return $this->_settings['linkeditor_title'];
    }

    /**
     * Returns the link target (e.g. "_blank")
     *
     * @return string
     */
    public function getTarget() {
        return ($this->_settings['linkeditor_newwindow'] === 'true') ? '_blank' : '';
    }

    /**
     * Returns the href of the link
     *
     * @return string
     */
    public function getLink() {
        return $this->_generateHref();
    }

    /**
     * Returns array with configured data (keys: type, externallink, title,
     * newwindow, idart, filename).
     * Additionally the key href contains the actual hyperreference.
     *
     * @return array
     */
    public function getConfiguredData() {
        $data = array(
            'type' => $this->_settings['linkeditor_type'],
            'externallink' => $this->_settings['linkeditor_externallink'],
            'title' => $this->_settings['linkeditor_title'],
            'newwindow' => $this->_settings['linkeditor_newwindow'],
            'idart' => $this->_settings['linkeditor_idart'],
            'filename' => $this->_settings['linkeditor_filename'],
            'href' => $this->_generateHref()
        );

        return $data;
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string escaped HTML code which sould be shown if content type is
     *         shown in frontend
     */
    public function generateViewCode() {
        // generate the needed attributes
        $href = $this->_generateHref();
        if (empty($href)) {
            return '';
        }
        $linktext = $this->_settings['linkeditor_title'];
        $alt = $linktext;
        // if the linktext is empty, use the link as the link text
        if (empty($linktext)) {
            $linktext = $href;
        }
        $target = ($this->_settings['linkeditor_newwindow'] === 'true') ? '_blank' : '';

        $link = new cHTMLLink($href);
        $link->setAlt($alt);
        $link->setTargetFrame($target);
        $link->setContent($linktext);

        return $this->_encodeForOutput($link->render());
    }

    /**
     * Generates the actual link depending on the link type.
     *
     * @return string the generated link
     */
    protected function _generateHref() {
        switch ($this->_settings['linkeditor_type']) {
            case 'external':
                // make sure that link starts with http://
                $link = $this->_settings['linkeditor_externallink'];
                if (strpos($link, 'http://') !== 0 && strpos($link, 'www.') === 0) {
                    $link = 'http://' . $link;
                }
                return $link;
                break;
            case 'internal':
                if ($this->_settings['linkeditor_idart'] != "") {

                    $oUri       = cUri::getInstance();
                    $uriBuilder = $oUri->getUriBuilder();
                    $uriParams  = array(
                        'idart' => $this->_settings['linkeditor_idart']
                    );
                    $uriBuilder->buildUrl($uriParams, true);

                    return $uriBuilder->getUrl();

                }
                break;
            case 'file':
                return $this->_cfgClient[$this->_client]['upl']['htmlpath'] . $this->_settings['linkeditor_filename'];
                break;
            default:
                // invalid link type, output nothing
                return '';
        }
    }

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string escaped HTML code which should be shown if content type is
     *         edited
     */
    public function generateEditCode() {
        $template = new cTemplate();
        $template->set('s', 'ID', $this->_id);
        $template->set('s', 'IDARTLANG', $this->_idArtLang);
        $template->set('s', 'FIELDS', "'" . implode("','", $this->_formFields) . "'");

        $templateTabs = new cTemplate();
        $templateTabs->set('s', 'PREFIX', $this->_prefix);

        // create code for external tab
        $templateTabs->set('d', 'TAB_ID', 'external');
        $templateTabs->set('d', 'TAB_CLASS', 'external');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabExternal());
        $templateTabs->next();

        // create code for internal tab
        $templateTabs->set('d', 'TAB_ID', 'internal');
        $templateTabs->set('d', 'TAB_CLASS', 'internal');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabInternal());
        $templateTabs->next();

        // create code for file tab
        $templateTabs->set('d', 'TAB_ID', 'file');
        $templateTabs->set('d', 'TAB_CLASS', 'file');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabFile());
        $templateTabs->next();

        // create code for basic settings "tab" - these settings are actually
        // visible any tim
        $templateTabs->set('d', 'TAB_ID', 'basic-settings');
        $templateTabs->set('d', 'TAB_CLASS', 'basic-settings');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateBasicSettings());
        $templateTabs->next();

        $codeTabs = $templateTabs->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_tabs.html', true);

        // construct the top code of the template
        $templateTop = new cTemplate();
        $templateTop->set('s', 'ICON', 'images/but_editlink.gif');
        $templateTop->set('s', 'ID', $this->_id);
        $templateTop->set('s', 'PREFIX', $this->_prefix);
        $templateTop->set('s', 'HEADLINE', i18n('Link settings'));
        $codeTop = $templateTop->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_top.html', true);

        // define the available tabs
        $tabMenu = array(
            'external' => i18n('External link'),
            'internal' => i18n('Internal link'),
            'file' => i18n('Link to a file')
        );

        // construct the bottom code of the template
        $templateBottom = new cTemplate();
        $templateBottom->set('s', 'PATH_FRONTEND', $this->_cfgClient[$this->_client]['path']['htmlpath']);
        $templateBottom->set('s', 'ID', $this->_id);
        $templateBottom->set('s', 'PREFIX', $this->_prefix);
        $templateBottom->set('s', 'IDARTLANG', $this->_idArtLang);
        $templateBottom->set('s', 'FIELDS', "'" . implode("','", $this->_formFields) . "'");
        $templateBottom->set('s', 'SETTINGS', json_encode($this->_settings));
        $templateBottom->set('s', 'JS_CLASS_SCRIPT', $this->_cfg['path']['contenido_fullhtml'] . 'scripts/content_types/cmsLinkeditor.js');
        $templateBottom->set('s', 'JS_CLASS_NAME', 'Con.cContentTypeLinkeditor');
        $codeBottom = $templateBottom->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_bottom.html', true);

        // construct the whole template code
        $code = $this->generateViewCode();
        $code .= $this->_encodeForOutput($codeTop);
        $code .= $this->_generateTabMenuCode($tabMenu);
        $code .= $this->_encodeForOutput($codeTabs);
        $code .= $this->_generateActionCode();
        $code .= $this->_encodeForOutput($codeBottom);

        return $code;
    }

    /**
     * Generates code for the external link tab in which links to external sites
     * can be specified.
     *
     * @return string - the code for the external link tab
     */
    private function _generateTabExternal() {
        // define a wrapper which contains the whole content of the general tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        $wrapperContent[] = new cHTMLLabel(i18n('Href'), 'linkeditor_externallink_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('linkeditor_externallink_' . $this->_id, $this->_settings['linkeditor_externallink'], '', '', 'linkeditor_externallink_' . $this->_id);

        $wrapper->setContent($wrapperContent);

        return $wrapper->render();
    }

    /**
     * Generates code for the basic settings "tab" in which the link title and
     * target can be specified.
     * This tab is always shown.
     *
     * @return string - the code for the basic settings tab
     */
    private function _generateBasicSettings() {
        // define a wrapper which contains the whole content of the basic
        // settings section
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        $wrapperContent[] = new cHTMLLabel(i18n('Title'), 'linkeditor_title_' . $this->_id);
        $title = conHtmlEntityDecode($this->_settings['linkeditor_title']);
        $wrapperContent[] = new cHTMLTextbox('linkeditor_title_' . $this->_id, $title, '', '', 'linkeditor_title_' . $this->_id);
        $wrapperContent[] = new cHTMLCheckbox('linkeditor_newwindow_' . $this->_id, '', 'linkeditor_newwindow_' . $this->_id, ($this->_settings['linkeditor_newwindow'] === 'true'));
        $wrapperContent[] = new cHTMLLabel(i18n('Open in a new window'), 'linkeditor_newwindow_' . $this->_id);

        $wrapper->setContent($wrapperContent);

        return $wrapper->render();
    }

    /**
     * Generates code for the internal link tab in which links to internal sites
     * can be specified.
     *
     * @return string - the code for the internal link tab
     */
    private function _generateTabInternal() {
        // define a wrapper which contains the whole content of the general tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        $directoryList = new cHTMLDiv('', 'directoryList', 'directoryList' . '_' . $this->_id);
        $liRoot = new cHTMLListItem('root', 'last');
        $aUpload = new cHTMLLink('#');
        $aUpload->setClass('on');
        $aUpload->setAttribute('title', '0');
        $aUpload->setContent('Root');
        $directoryListCode = $this->getCategoryList($this->buildCategoryArray());
        $div = new cHTMLDiv(array(
            '<em><a href="#"></a></em>',
            $aUpload
        ));
        $liRoot->setContent(array(
            $div,
            $directoryListCode
        ));
        $conStrTree = new cHTMLList('ul', 'con_str_tree', 'con_str_tree', $liRoot);
        $directoryList->setContent($conStrTree);
        $wrapperContent[] = $directoryList;

        $activeIdcats = $this->_getActiveIdcats();
        $wrapperContent[] = new cHTMLDiv($this->generateArticleSelect($activeIdcats[0]), 'directoryFile', 'directoryFile' . '_' . $this->_id);

        $wrapper->setContent($wrapperContent);

        return $wrapper->render();
    }

    /**
     * Builds an array with category information.
     *
     * @param int $level
     * @param int $parentid
     * @return array with directory information
     */
    public function buildCategoryArray($level = 0, $parentid = 0) {
        $db = cRegistry::getDb();
        $directories = array();
        $sql = 'SELECT distinct
                    *
                FROM
                    ' . $this->_cfg['tab']['cat_tree'] . ' AS a,
                    ' . $this->_cfg['tab']['cat'] . ' AS c,
                    ' . $this->_cfg['tab']['cat_lang'] . ' AS d
                WHERE
                    a.level = ' . $level . ' AND
                    c.parentid = ' . $parentid . ' AND
                    a.idcat = d.idcat AND
                    c.idcat = a.idcat AND
                    d.idlang = ' . cSecurity::toInteger($this->_lang) . ' AND
                    c.idclient = ' . cSecurity::toInteger($this->_client) . '
                ORDER BY
                    a.idtree';

        $db->query($sql);
        while ($db->nextRecord()) {
            $directory = array();
            $directory['idcat'] = $db->f('idcat');
            $directory['name'] = $db->f('name');
            $directory['sub'] = $this->buildCategoryArray($level + 1, $directory['idcat']);
            $directories[] = $directory;
        }

        return $directories;
    }

    /**
     * Generates a category list from the given category information (which is
     * typically built by {@link cContentTypeLinkeditor::buildCategoryArray}).
     *
     * @param array $dirs directory information
     * @return string HTML code showing a directory list
     */
    public function getCategoryList(array $categories) {
        $template = new cTemplate();
        $i = 1;

        foreach ($categories as $category) {
            $activeIdcats = $this->_getActiveIdcats();
            // set the active class if this is the chosen directory
            $divClass = (isset($activeIdcats[0]) && $category['idcat'] == $activeIdcats[0]) ? 'active' : '';
            $template->set('d', 'DIVCLASS', $divClass);

            $template->set('d', 'TITLE', $category['idcat']);
            $template->set('d', 'DIRNAME', $category['name']);

            $liClasses = array();
            // check if the category should be shown expanded or collapsed
            if (in_array($category['idcat'], $activeIdcats) && $category['sub'] != '') {
                $template->set('d', 'SUBDIRLIST', $this->getCategoryList($category['sub']));
            } else if ($category['sub'] != '' && count($category['sub']) > 0) {
                $liClasses[] = 'collapsed';
                $template->set('d', 'SUBDIRLIST', '');
            } else {
                $template->set('d', 'SUBDIRLIST', '');
            }

            if ($i === count($categories)) {
                $liClasses[] = 'last';
            }

            $template->set('d', 'LICLASS', implode(' ', $liClasses));

            $i++;
            $template->next();
        }

        return $template->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_filelist_dirlistitem.html', true);
    }

    /**
     * Computes all active idcats.
     *
     * @return array containing all active idcats
     */
    private function _getActiveIdcats() {
        $activeIdcats = array();
        if ($this->_settings['linkeditor_type'] === 'internal') {
            $sql = 'SELECT distinct
                        *
                    FROM
                        ' . $this->_cfg['tab']['cat_tree'] . ' AS a,
                        ' . $this->_cfg['tab']['cat_art'] . ' AS b,
                        ' . $this->_cfg['tab']['cat'] . ' AS c,
                        ' . $this->_cfg['tab']['cat_lang'] . ' AS d
                    WHERE
                        b.idart = ' . cSecurity::toInteger($this->_settings['linkeditor_idart']) . ' AND
                        a.idcat = d.idcat AND
                        b.idcat = c.idcat AND
                        c.idcat = a.idcat AND
                        d.idlang = ' . cSecurity::toInteger($this->_lang) . ' AND
                        c.idclient = ' . cSecurity::toInteger($this->_client) . '
                    ORDER BY
                        a.idtree';
            $db = cRegistry::getDb();
            $db->query($sql);
            while ($db->nextRecord()) {
                $activeIdcats = $this->_getParentIdcats($db->f('idcat'));
            }
        }

        return $activeIdcats;
    }

    /**
     * Computes all parent idcats of the given idcat and returns them.
     *
     * @param int $idcat the current idcat
     * @param array $idcats the array of idcats to which all idcats should be
     *        added
     * @return array the given idcats array with the given idcat and all parent
     *         idcats
     */
    private function _getParentIdcats($idcat, array $idcats = array()) {
        // add the current idcat to the result idcats
        $idcats[] = $idcat;

        // get the cat entries with the given idcat
        $category = new cApiCategory($idcat);
        $parentId = $category->get('parentid');
        if ($parentId != 0) {
            $idcats = $this->_getParentIdcats($parentId, $idcats);
        }

        return $idcats;
    }

    /**
     * Generate a select box for all articles of the given idcat.
     *
     * @param int $idCat idcat of the category from which all articles
     *        should be shown
     * @return string rendered cHTMLSelectElement
     */
    public function generateArticleSelect($idCat = 0) {
        $htmlSelect = new cHTMLSelectElement('linkeditor_idart', '', 'linkeditor_idart_' . $this->_id);
        $htmlSelect->setSize(16);
        $htmlSelectOption = new cHTMLOptionElement('Kein', '', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);
        // if no idcat has been given, do not search for articles
        if (empty($idCat)) {
            return $htmlSelect->render();
        }

        // get all articles from the category with the given idcat and add them
        // to the select element
        $sql = 'SELECT distinct
                    e.*
                FROM
                    ' . $this->_cfg['tab']['cat_tree'] . ' AS a,
                    ' . $this->_cfg['tab']['cat_art'] . ' AS b,
                    ' . $this->_cfg['tab']['cat'] . ' AS c,
                    ' . $this->_cfg['tab']['cat_lang'] . ' AS d,
                    ' . $this->_cfg['tab']['art_lang'] . ' AS e
                WHERE
                    c.idcat = ' . $idCat . ' AND
                    e.online = 1 AND
                    a.idcat = b.idcat AND
                    b.idcat = d.idcat AND
                    d.idlang = ' . cSecurity::toInteger($this->_lang) . ' AND
                    b.idart  = e.idart AND
                    c.idcat = a.idcat AND
                    c.idclient = ' . cSecurity::toInteger($this->_client) . ' AND
                    e.idlang = ' . cSecurity::toInteger($this->_lang) . '
                ORDER BY
                    e.title';
        $db = cRegistry::getDb();
        $db->query($sql);
        while ($db->nextRecord()) {
            $htmlSelectOption = new cHTMLOptionElement($db->f('title'), $db->f('idart'), ($db->f('idart') == $this->_settings['linkeditor_idart']));
            $htmlSelect->appendOptionElement($htmlSelectOption);
        }

        return $htmlSelect->render();
    }

    /**
     * Generates code for the link to file tab in which links to files can be
     * specified.
     *
     * @return string - the code for the link to file tab
     */
    private function _generateTabFile() {
        // define a wrapper which contains the whole content of the general tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        // create a new directory form
        $newDirForm = new cHTMLForm();
        $newDirForm->setAttribute('name', 'newdir');
        $newDirForm->setAttribute('method', 'post');
        $newDirForm->setAttribute('action', $this->_cfg['path']['contenido_fullhtml'] . 'main.php');
        $caption1Span = new cHTMLSpan();
        $caption1Span->setID('caption1');
        $newDirHead = new cHTMLDiv(array(
            '<b>' . i18n('Create a directory in') . '</b>',
            $caption1Span
        ));
        $area = new cHTMLHiddenField('area', 'upl');
        $action = new cHTMLHiddenField('action', 'upl_mkdir');
        $frame = new cHTMLHiddenField('frame', '2');
        $appendparameters = new cHTMLHiddenField('appendparameters');
        $contenido = new cHTMLHiddenField('contenido', $_REQUEST['contenido']);
        $path = new cHTMLHiddenField('path');
        $foldername = new cHTMLTextbox('foldername');
        $button = new cHTMLButton('', '', '', false, NULL, '', 'image');
        $button->setAttribute('src', $this->_cfg['path']['contenido_fullhtml'] . 'images/submit.gif');
        $newDirContent = new cHTMLDiv(array(
            $area,
            $action,
            $frame,
            $appendparameters,
            $contenido,
            $path,
            $foldername,
            $button
        ));
        $newDirForm->setContent(array(
            $newDirHead,
            $newDirContent
        ));
        $wrapperContent[] = $newDirForm;

        // upload a new file form
        $propertiesForm = new cHTMLForm();
        $propertiesForm->setID('properties' . $this->_id);
        $propertiesForm->setAttribute('name', 'properties');
        $propertiesForm->setAttribute('method', 'post');
        $propertiesForm->setAttribute('action', $this->_cfg['path']['contenido_fullhtml'] . 'main.php');
        $propertiesForm->setAttribute('enctype', 'multipart/form-data');
        $frame = new cHTMLHiddenField('frame', '4');
        $area = new cHTMLHiddenField('area', 'upl');
        $path = new cHTMLHiddenField('path');
        $file = new cHTMLHiddenField('file');
        $action = new cHTMLHiddenField('action', 'upl_upload');
        $appendparameters = new cHTMLHiddenField('appendparameters');
        $contenido = new cHTMLHiddenField('contenido', $_REQUEST['contenido']);
        $caption2Span = new cHTMLSpan();
        $caption2Span->setID('caption2');
        $propertiesHead = new cHTMLDiv(array(
            '<b>' . i18n('Path') . '</b>',
            $caption2Span
        ));
        $imageUpload = new cHTMLUpload('file[]', '', '', 'cms_image_m' . $this->_id, false, '', '', 'file');
        $imageUpload->setClass('jqueryAjaxUpload');
        $propertiesForm->setContent(array(
            $frame,
            $area,
            $path,
            $file,
            $action,
            $appendparameters,
            $contenido,
            $propertiesHead,
            $imageUpload
        ));
        $wrapperContent[] = $propertiesForm;

        $wrapperContent[] = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . 'images/ajax-loader.gif', 'loading');

        // directory navigation
        $directoryList = new cHTMLDiv('', 'directoryList', 'directoryList_' . $this->_id);
        $liRoot = new cHTMLListItem('root', 'last');
        $aUpload = new cHTMLLink('#');
        $aUpload->setClass('on');
        $aUpload->setAttribute('title', 'upload');
        $aUpload->setContent('Uploads');
        $directoryListCode = $this->generateDirectoryList($this->buildDirectoryList());
        $div = new cHTMLDiv(array(
            '<em><a href="#"></a></em>',
            $aUpload
        ));
        // set the active class if the root directory has been chosen
        if (dirname($this->_settings['linkeditor_filename']) === '\\') {
            $div->setClass('active');
        }
        $liRoot->setContent(array(
            $div,
            $directoryListCode
        ));
        $conStrTree = new cHTMLList('ul', 'con_str_tree', 'con_str_tree', $liRoot);
        $directoryList->setContent($conStrTree);
        $wrapperContent[] = $directoryList;

        $wrapperContent[] = new cHTMLDiv($this->getUploadFileSelect(dirname($this->_settings['linkeditor_filename'])), 'directoryFile', 'directoryFile' . '_' . $this->_id);

        $wrapper->setContent($wrapperContent);

        return $wrapper->render();
    }

    /**
     * Generates a select box for the manual files.
     *
     * @param string $directoryPath to directory of the files
     * @return string cHTMLSelectElement
     */
    public function getUploadFileSelect($directoryPath = '') {
        // replace all backslashes with slashes
        $directoryPath = str_replace('\\', '/', $directoryPath);
        // if the directory path only contains a slash, leave it empty
        // otherwise there will be two slashes in the end
        if ($directoryPath === '/') {
            $directoryPath = '';
        }
        // make sure the path ends with a slash if it is not empty
        if ($directoryPath !== '' && substr($directoryPath, -1) != '/') {
            $directoryPath .= '/';
        }

        $htmlSelect = new cHTMLSelectElement('linkeditor_filename', '', 'linkeditor_filename_' . $this->_id);
        $htmlSelect->setSize(16);
        $htmlSelectOption = new cHTMLOptionElement('Kein', '', false);
        $htmlSelect->addOptionElement(0, $htmlSelectOption);

        $files = array();
        if (is_dir($this->_uploadPath . $directoryPath)) {
            if ($handle = opendir($this->_uploadPath . $directoryPath)) {
                while (($entry = readdir($handle)) !== false) {
                    if (is_file($this->_uploadPath . $directoryPath . $entry) && !(strpos($entry, ".") === 0)) {
                        $file = array();
                        $file["name"] = $entry;
                        $file["path"] = $directoryPath . $entry;
                        $files[] = $file;
                    }
                }
                closedir($handle);
            }
        }

        usort($files, function($a, $b) {
            $a = mb_strtolower($a["name"]);
            $b = mb_strtolower($b["name"]);
            if($a < $b) {
                return -1;
            } else if($a > $b) {
                return 1;
            } else {
                return 0;
            }
        });

        $i = 1;
        foreach($files as $file) {
            $htmlSelectOption = new cHTMLOptionElement($file["name"], $file["path"]);
            $htmlSelect->addOptionElement($i, $htmlSelectOption);
            $i++;
        }


        // set default value
        if ($this->_settings['linkeditor_type'] === 'file') {
            $htmlSelect->setDefault($this->_settings['linkeditor_filename']);
        }

        return $htmlSelect->render();
    }

    /**
     * Checks whether the directory defined by the given directory
     * information is the currently active directory.
     * Overwrite in subclasses if you use getDirectoryList!
     *
     * @param array $dirData directory information
     * @return boolean whether the directory is the currently active directory
     */
    protected function _isActiveDirectory(array $dirData) {
        return $dirData['path'] . $dirData['name'] === dirname($this->_settings['linkeditor_filename']);
    }

    /**
     * Checks whether the directory defined by the given directory information
     * should be shown expanded.
     * Overwrite in subclasses if you use getDirectoryList!
     *
     * @param array $dirData directory information
     * @return boolean whether the directory should be shown expanded
     */
    protected function _shouldDirectoryBeExpanded(array $dirData) {
        return $this->_isSubdirectory($dirData['path'] . $dirData['name'], $this->_dirname);
    }

}