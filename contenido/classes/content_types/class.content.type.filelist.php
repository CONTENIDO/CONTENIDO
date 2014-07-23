<?php
/**
 * This file contains the cContentTypeFilelist class.
 *
 * @package Core
 * @subpackage ContentType
 * @version SVN Revision $Rev:$
 *
 * @author Dominik Ziegler, Timo Trautmann, Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.upl.php');

/**
 * Content type CMS_FILELIST which lets the editor select some folders or files.
 * The corresponding files are then shown in the frontend.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeFilelist extends cContentTypeAbstractTabbed {

    /**
     * Default file extensions.
     *
     * @var array
     */
    private $_fileExtensions = array(
        'gif',
        'jpeg',
        'jpg',
        'png',
        'doc',
        'xls',
        'pdf',
        'txt',
        'zip',
        'ppt'
    );

    /**
     * Meta data identifiers.
     *
     * @var array
     */
    private $_metaDataIdents = array(
        'description' => 'Description',
        'medianame' => 'Media name',
        'copyright' => 'Copyright',
        'keywords' => 'Keywords',
        'internal_notice' => 'Internal notes'
    );

    /**
     * Date fields.
     *
     * @var array
     */
    private $_dateFields = array(
        'ctime' => 'creationdate',
        'mtime' => 'modifydate'
    );

    /**
     * Placeholders for labels in frontend.
     * Important: This must be a static array!
     *
     * @var array
     */
    protected static $_translations = array(
        "LABEL_FILESIZE",
        "LABEL_UPLOAD_DATE"
    );

    /**
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *        plaintext
     * @param int $id ID of the content type, e.g. 3 if CMS_TEASER[3] is
     *        used
     * @param array $contentTypes array containing the values of all content
     *        types
     */
    function __construct($rawSettings, $id, array $contentTypes) {
        // set attributes of the parent class and call the parent constructor
        $this->_type = 'CMS_FILELIST';
        $this->_prefix = 'filelist';
        $this->_settingsType = 'xml';
        $this->_formFields = array(
            'filelist_title',
            'filelist_style',
            'filelist_directories',
            'filelist_incl_subdirectories',
            'filelist_manual',
            'filelist_sort',
            'filelist_incl_metadata',
            'filelist_extensions',
            'filelist_sortorder',
            'filelist_filesizefilter_from',
            'filelist_filesizefilter_to',
            'filelist_ignore_extensions',
            'filelist_manual_files',
            'filelist_filecount'
        );

        parent::__construct($rawSettings, $id, $contentTypes);

        // dynamically add form fields based on the meta data identifiers
        foreach ($this->_metaDataIdents as $identName => $translation) {
            $this->_formFields[] = 'filelist_md_' . $identName . '_limit';
        }

        // dynamically add form fields based on the date fields
        $dateFormFields = array();
        foreach ($this->_dateFields as $dateField) {
            $this->_formFields[] = 'filelist_' . $dateField . 'filter_from';
            $dateFormFields[] = 'filelist_' . $dateField . 'filter_from';
            $this->_formFields[] = 'filelist_' . $dateField . 'filter_to';
            $dateFormFields[] = 'filelist_' . $dateField . 'filter_to';
        }

        // if form is submitted, store the current file list settings
        // notice: there is also a need, that filelist_id is the same (case:
        // more than one cms file list is used on the same page
        if (isset($_POST['filelist_action']) && $_POST['filelist_action'] === 'store' && isset($_POST['filelist_id']) && (int) $_POST['filelist_id'] == $this->_id) {
            // convert the date form fields to timestamps
            foreach ($dateFormFields as $dateFormField) {
                $value = $_POST[$dateFormField];
                if ($value != '' && $value != 'DD.MM.YYYY' && strlen($value) == 10) {
                    $valueSplit = explode('.', $value);
                    $timestamp = mktime(0, 0, 0, $valueSplit[1], $valueSplit[0], $valueSplit[2]);
                } else {
                    $timestamp = 0;
                }
                $_POST[$dateFormField] = $timestamp;
            }
            $this->_storeSettings();
        }
    }

    /**
     * Returns all translation strings for mi18n.
     *
     * @param array $translationStrings translation strings
     * @return array updated translation string
     */
    public static function addModuleTranslations(array $translationStrings) {
        foreach (self::$_translations as $value) {
            $translationStrings[] = $value;
        }

        return $translationStrings;
    }

    /**
     * Reads all settings from the $_rawSettings attribute (XML or plaintext)
     * and stores them in the $_settings attribute (associative array or
     * plaintext).
     */
    protected function _readSettings() {
        parent::_readSettings();
        // convert the timestamps to dates
        $dateFormFields = array();
        foreach ($this->_dateFields as $dateField) {
            $dateFormFields[] = 'filelist_' . $dateField . 'filter_from';
            $dateFormFields[] = 'filelist_' . $dateField . 'filter_to';
        }
        foreach ($dateFormFields as $dateFormField) {
            $value = $this->_settings[$dateFormField];
            if ($dateFormField == 0) {
                $value = 'DD.MM.YYYY';
            } else {
                $value = date('d.m.Y', $dateFormField);
            }
            $this->_settings[$dateFormField] = $value;
        }
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string escaped HTML code which sould be shown if content type is
     *         shown in frontend
     */
    public function generateViewCode() {
        $code = '";?><?php
                    $fileList = new cContentTypeFilelist(\'%s\', %s, %s);

                    echo $fileList->generateFileListCode();
                 ?><?php echo "';
        $code = sprintf($code, $this->_rawSettings, $this->_id, 'array()');

        return $code;
    }

    /**
     *
     * @return void string array
     * @todo unify return values
     */
    public function getConfiguredFiles() {
        $files = array();

        if ($this->_settings['filelist_manual'] === 'true' && count($this->_settings['filelist_manual_files']) > 0) {
            $fileList = $this->_settings['filelist_manual_files'];
        } else if (count($this->_settings['filelist_directories']) > 0) {
            $directories = $this->_settings['filelist_directories'];

            if ($this->_settings['filelist_incl_subdirectories'] === 'true') {
                foreach ($directories as $directoryName) {
                    $directories = $this->_getAllSubdirectories($directoryName, $directories);
                }
            }

            // strip duplicate directories to save performance
            $directories = array_unique($directories);

            if (count($directories) < 1) {
                return;
            }

            foreach ($directories as $directoryName) {
                if (is_dir($this->_uploadPath . $directoryName)) {
                    if (false !== $handle = opendir($this->_uploadPath . $directoryName)) {
                        while (($entry = readdir($handle)) !== false) {
                            // checking if entry is file and is not a directory
                            if (is_file($this->_uploadPath . $directoryName . '/' . $entry)) {
                                $fileList[] = $directoryName . '/' . $entry;
                            }
                        }
                    }
                    closedir($handle);
                }
            }
        } else {
            return '';
        }

        if (is_array($fileList)) {
            $files = $this->_applyFileFilters($fileList);
        } else {
            $files = $this->_applyFileFilters((array) $fileList);
        }
        unset($fileList);

        if (count($files) > 0) {
            // sort the files
            if ($this->_settings['filelist_sortorder'] === 'desc') {
                krsort($files);
            } else {
                ksort($files);
            }

            $i = 1;
            foreach ($files as $key => $filenameData) {
                if (($this->_settings['filelist_filecount'] != 0 && $i <= $this->_settings['filelist_filecount']) || $this->_settings['filelist_filecount'] == 0) {
                    if ($this->_settings['filelist_incl_metadata'] === 'true') {
                        $metaData = array();
                        // load upload and upload meta object
                        $upload = new cApiUpload();
                        $upload->loadByMany(array(
                            'filename' => $filenameData['filename'],
                            'dirname' => $filenameData['path'] . '/',
                            'idclient' => $this->_client
                        ));
                        $uploadMeta = new cApiUploadMeta();
                        $uploadMeta->loadByMany(array(
                            'idupl' => $upload->get('idupl'),
                            'idlang' => $this->_lang
                        ));

                        foreach ($this->_metaDataIdents as $identName => $translation) {
                            $string = $uploadMeta->get($identName);

                            // Cut the string only, when the limit for identName
                            // is active and the string length is more than the
                            // setting
                            if ($this->_settings['filelist_md_' . $identName . '_limit'] > 0 && strlen($string) > $this->_settings['filelist_md_' . $identName . '_limit']) {
                                $metaData[$identName] = cApiStrTrimAfterWord(cSecurity::unFilter($string), $this->_settings['filelist_md_' . $identName . '_limit']) . '...';
                            } else {
                                $metaData[$identName] = cSecurity::unFilter($string);
                            }
                        }

                        $filenameData['metadata'] = $metaData;
                    } else {
                        $filenameData['metadata'] = array();
                    }

                    // Define new array for files
                    // If filelist_filecount is defined, this array has the same
                    // size as "filelist_filecount" setting value (0 = no limit)
                    $limitedfiles[$key] = $filenameData;
                    $i++;
                }
            }

            return $limitedfiles;
        }
    }

    /**
     * Function is called in edit- and viewmode in order to generate code for
     * output.
     *
     * @return string generated code
     */
    public function generateFileListCode() {
        if ($this->_settings['filelist_style'] === '') {
            return '';
        }
        $template = new cTemplate();
        $fileList = array();

        $template->set('s', 'TITLE', $this->_settings['filelist_title']);

        $files = $this->getConfiguredFiles();

        if (is_array($files) && count($files) > 0) {
            foreach ($files as $filenameData) {
                $this->_fillFileListTemplateEntry($filenameData, $template);
            }

            // generate template
            $code = $template->generate($this->_cfgClient[$this->_client]['path']['frontend'] . 'templates/' . $this->_settings['filelist_style'], true);
        }

        return $code;
    }

    /**
     * Gets all subdirectories recursively.
     *
     * @param string $directoryPath path to directory
     * @param array $directories already found directories
     * @return array containing all subdirectories and the initial directories
     */
    private function _getAllSubdirectories($directoryPath, array $directories) {
        $handle = opendir($this->_uploadPath . $directoryPath);
        while (($entry = readdir($handle)) !== false) {
            if ($entry !== '.svn' && $entry !== '.' && $entry !== '..' && is_dir($this->_uploadPath . $directoryPath . '/' . $entry)) {
                $directories[] = $directoryPath . '/' . $entry;
                $directories = $this->_getAllSubdirectories($directoryPath . '/' . $entry, $directories);
            }
        }
        closedir($handle);

        return $directories;
    }

    /**
     * Removes all files not matching the filter criterias.
     *
     * @param array $fileList files which should be filtered
     * @return array with filtered files
     */
    private function _applyFileFilters(array $fileList) {
        foreach ($fileList as $fullname) {
            $filename = basename($fullname);
            $directoryName = str_replace('/' . $filename, '', $fullname);

            // checking the extension stuff
            $extensionName = uplGetFileExtension($filename);
            $extensions = $this->_settings['filelist_extensions'];
            if(!is_array($extensions)) {
                $extensions = array($extensions);
            }
            if ($this->_settings['filelist_ignore_extensions'] === 'true' || count($extensions) == 0 || ($this->_settings['filelist_ignore_extensions'] === 'false' && in_array($extensionName, $extensions)) || ($this->_settings['filelist_ignore_extensions'] === 'false' && $extensionName == $extensions)) {

            	// Prevent errors with not existing files
            	if (!cFileHandler::exists($this->_uploadPath . $directoryName . '/' . $filename)) {
            		return;
            	}

                // checking filesize filter
                $fileStats = stat($this->_uploadPath . $directoryName . '/' . $filename);
                $filesize = $fileStats['size'];

                $filesizeMib = $filesize / 1024 / 1024;
                if (($this->_settings['filelist_filesizefilter_from'] == 0 && $this->_settings['filelist_filesizefilter_to'] == 0) || ($this->_settings['filelist_filesizefilter_from'] <= $filesizeMib && $this->_settings['filelist_filesizefilter_to'] >= $filesizeMib)) {

                    if ($this->_applyDateFilters($fileStats)) {
                        $creationDate = $fileStats['ctime'];
                        $modifyDate = $fileStats['mtime'];
                        // conditional stuff is completed, start sorting
                        switch ($this->_settings['filelist_sort']) {
                            case 'filesize':
                                $indexName = $filesize;
                                break;
                            case 'createdate':
                                $indexName = $creationDate;
                                break;
                            case 'modifydate':
                                $indexName = $modifyDate;
                                break;
                            case 'filename':
                            default:
                                $indexName = strtolower($filename);
                        }

                        $files[$indexName] = array();
                        $files[$indexName]['filename'] = $filename;
                        $files[$indexName]['path'] = $directoryName;
                        $files[$indexName]['extension'] = $extensionName;
                        $files[$indexName]['filesize'] = $filesize;
                        $files[$indexName]['filemodifydate'] = $modifyDate;
                        $files[$indexName]['filecreationdate'] = $creationDate;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Checks whether the file passes the date filters.
     *
     * @param array $fileStats file information
     * @return boolean whether the file passes the date filters
     */
    private function _applyDateFilters(array $fileStats) {
        foreach ($this->_dateFields as $index => $dateField) {
            $date = $fileStats[$index];
            if ($this->_settings['filelist_' . $dateField . 'filter_from'] == 0 && $this->_settings['filelist_' . $dateField . 'filter_from'] == 0 || $this->_settings['filelist_' . $dateField . 'filter_to'] == 0 && $date >= $this->_settings['filelist_' . $dateField . 'filter_from'] || $this->_settings['filelist_' . $dateField . 'filter_from'] == 0 && $date <= $this->_settings['filelist_' . $dateField . 'filter_to'] || $this->_settings['filelist_' . $dateField . 'filter_from'] != 0 && $this->_settings['filelist_' . $dateField . 'filter_to'] != 0 && $date >= $this->_settings['filelist_' . $dateField . 'filter_from'] && $date <= $this->_settings['filelist_' . $dateField . 'filter_to']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Method to fill single entry (file) of the file list.
     *
     * @param array $fileData information about the file
     * @param cTemplate $template reference to the used template object
     */
    private function _fillFileListTemplateEntry(array $fileData, cTemplate &$template) {
        $filename = $fileData['filename'];
        $directoryName = $fileData['path'];

        if (empty($filename) || empty($directoryName)) {
            cWarning(__FILE__, __LINE__, "Empty directory '$directoryName' and or filename '$filename'");
            return;
        }

        $fileLink = $this->_cfgClient[$this->_client]['upl']['htmlpath'] . $directoryName . '/' . $filename;
        $filePath = $this->_cfgClient[$this->_client]['upl']['path'] . $directoryName . '/' . $filename;

        $info = exif_imagetype($filePath);

        // If file is an image (extensions gif, jpg, jpeg, png) scale it
        // otherwise use default png image
        switch ($fileData['extension']) {
            case 'gif':
            case 'jpg':
            case 'jpeg':
            case 'png':
                $imgSrc = cApiImgScale($filePath, 148, 74);
                break;
            default:
                $imgSrc = $this->_cfgClient[$this->_client]['path']['htmlpath'] . 'images/misc/download_misc.png';
                break;
        }

        $filesize = $fileData['filesize'];
        $metaData = $fileData['metadata'];

        if ($this->_settings['filelist_incl_metadata'] === 'true' && count($metaData) != 0) {
            $template->set('d', 'FILEMETA_DESCRIPTION', $metaData['description']);
            $template->set('d', 'FILEMETA_MEDIANAME', $metaData['medianame']);
            $template->set('d', 'FILEMETA_KEYWORDS', $metaData['keywords']);
            $template->set('d', 'FILEMETA_INTERNAL_NOTICE', $metaData['internal_notice']);
            $template->set('d', 'FILEMETA_COPYRIGHT', $metaData['copyright']);
        } else {
            $template->set('d', 'FILEMETA_DESCRIPTION', '');
            $template->set('d', 'FILEMETA_MEDIANAME', '');
            $template->set('d', 'FILEMETA_KEYWORDS', '');
            $template->set('d', 'FILEMETA_INTERNAL_NOTICE', '');
            $template->set('d', 'FILEMETA_COPYRIGHT', '');
        }

        $template->set('d', 'FILETHUMB', $imgSrc);
        $template->set('d', 'FILENAME', $filename);
        $template->set('d', 'FILESIZE', humanReadableSize($filesize));
        $template->set('d', 'FILEEXTENSION', $fileData['extension']);
        $template->set('d', 'FILECREATIONDATE', date('d.m.Y', $fileData['filecreationdate']));
        $template->set('d', 'FILEMODIFYDATE', date('d.m.Y', $fileData['filemodifydate']));
        $template->set('d', 'FILEDIRECTORY', $directoryName);
        $template->set('d', 'FILELINK', $fileLink);

        foreach (self::$_translations as $translationString) {
            $template->set('d', $translationString, mi18n($translationString));
        }

        $template->next();
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
        $templateTabs->set('d', 'TAB_ID', 'directories');
        $templateTabs->set('d', 'TAB_CLASS', 'directories');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabDirectories());
        $templateTabs->next();

        // create code for internal tab
        $templateTabs->set('d', 'TAB_ID', 'general');
        $templateTabs->set('d', 'TAB_CLASS', 'general');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabGeneral());
        $templateTabs->next();

        // create code for file tab
        $templateTabs->set('d', 'TAB_ID', 'filter');
        $templateTabs->set('d', 'TAB_CLASS', 'filter');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabFilter());
        $templateTabs->next();

        // create code for manual tab
        $templateTabs->set('d', 'TAB_ID', 'manual');
        $templateTabs->set('d', 'TAB_CLASS', 'manual');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabManual());
        $templateTabs->next();

        $codeTabs = $templateTabs->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_tabs.html', true);

        // construct the top code of the template
        $templateTop = new cTemplate();
        $templateTop->set('s', 'ICON', 'images/but_editlink.gif');
        $templateTop->set('s', 'ID', $this->_id);
        $templateTop->set('s', 'PREFIX', $this->_prefix);
        $templateTop->set('s', 'HEADLINE', i18n('File list settings'));
        $codeTop = $templateTop->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_top.html', true);

        // define the available tabs
        $tabMenu = array(
            'directories' => i18n('Directories'),
            'general' => i18n('General'),
            'filter' => i18n('Filter'),
            'manual' => i18n('Manual')
        );

        // construct the bottom code of the template
        $templateBottom = new cTemplate();
        $templateBottom->set('s', 'PATH_FRONTEND', $this->_cfgClient[$this->_client]['path']['htmlpath']);
        $templateBottom->set('s', 'ID', $this->_id);
        $templateBottom->set('s', 'PREFIX', $this->_prefix);
        $templateBottom->set('s', 'IDARTLANG', $this->_idArtLang);
        $templateBottom->set('s', 'FIELDS', "'" . implode("','", $this->_formFields) . "'");
        $templateBottom->set('s', 'SETTINGS', json_encode($this->_settings));
        $templateBottom->set('s', 'JS_CLASS_SCRIPT', $this->_cfg['path']['contenido_fullhtml'] . 'scripts/content_types/cmsFilelist.js');
        $templateBottom->set('s', 'JS_CLASS_NAME', 'Con.cContentTypeFilelist');
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
     * Generates code for the directories tab.
     *
     * @return string - the code for the directories tab
     */
    private function _generateTabDirectories() {
        // define a wrapper which contains the whole content of the directories
        // tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        $wrapperContent[] = new cHTMLParagraph(i18n('Source directory'), 'head_sub');

        $directoryList = new cHTMLDiv('', 'directoryList', 'directoryList' . '_' . $this->_id);
        $liRoot = new cHTMLListItem('root', 'last');
        $directoryListCode = $this->generateDirectoryList($this->buildDirectoryList());
        $liRoot->setContent(array(
            '<em>Uploads</em>',
            $directoryListCode
        ));
        $conStrTree = new cHTMLList('ul', 'con_str_tree', 'con_str_tree', $liRoot);
        $directoryList->setContent($conStrTree);
        $wrapperContent[] = $directoryList;

        $wrapper->setContent($wrapperContent);

        return $wrapper->render();
    }

    /**
     * Generates code for the general tab.
     *
     * @return string - the code for the general link tab
     */
    private function _generateTabGeneral() {
        // define a wrapper which contains the whole content of the general tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        $wrapperContent[] = new cHTMLParagraph(i18n('General settings'), 'head_sub');

        $wrapperContent[] = new cHTMLLabel(i18n('File list title'), 'filelist_title_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('filelist_title_' . $this->_id, $this->_settings['filelist_title'], '', '', 'filelist_title_' . $this->_id);
        $wrapperContent[] = new cHTMLLabel(i18n('File list style'), 'filelist_style_' . $this->_id);
        $wrapperContent[] = $this->_generateStyleSelect();
        $wrapperContent[] = new cHTMLLabel(i18n('File list sort'), 'filelist_sort_' . $this->_id);
        $wrapperContent[] = $this->_generateSortSelect();
        $wrapperContent[] = new cHTMLLabel(i18n('Sort order'), 'filelist_sortorder_' . $this->_id);
        $wrapperContent[] = $this->_generateSortOrderSelect();
        $wrapperContent[] = new cHTMLLabel(i18n('Include subdirectories?'), 'filelist_incl_subdirectories_' . $this->_id);
        $wrapperContent[] = new cHTMLCheckbox('filelist_incl_subdirectories_' . $this->_id, '', 'filelist_incl_subdirectories_' . $this->_id, ($this->_settings['filelist_incl_subdirectories'] === 'true'));
        $wrapperContent[] = new cHTMLLabel(i18n('Include meta data?'), 'filelist_incl_metadata_' . $this->_id);
        $wrapperContent[] = new cHTMLCheckbox('filelist_incl_metadata_' . $this->_id, '', 'filelist_incl_metadata_' . $this->_id, ($this->_settings['filelist_incl_metadata'] === 'true'));
        $div = new cHTMLDiv($this->_generateMetaDataList());
        $div->setID('metaDataList');
        $wrapperContent[] = $div;

        $wrapper->setContent($wrapperContent);

        return $wrapper->render();
    }

    /**
     * Generates a select box containing the filelist styles.
     *
     * @return string rendered cHTMLSelectElement
     */
    private function _generateStyleSelect() {
        $htmlSelect = new cHTMLSelectElement('filelist_style_' . $this->_id, '', 'filelist_style_' . $this->_id);

        $htmlSelectOption = new cHTMLOptionElement(i18n('Default style'), 'cms_filelist_style_default.html', true);
        $htmlSelect->appendOptionElement($htmlSelectOption);
        $additionalOptions = getEffectiveSettingsByType('cms_filelist_style');
        $options = array();
        foreach ($additionalOptions as $key => $value) {
            $options[$value] = $key;
        }
        $htmlSelect->autoFill($options);
        $htmlSelect->setDefault($this->_settings['filelist_style']);
        return $htmlSelect->render();
    }

    /**
     * Generates a select box containing the sort options.
     *
     * @return string rendered cHTMLSelectElement
     */
    private function _generateSortSelect() {
        $htmlSelect = new cHTMLSelectElement('filelist_sort_' . $this->_id, '', 'filelist_sort_' . $this->_id);

        $htmlSelectOption = new cHTMLOptionElement(i18n('File name'), 'filename', true);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n('File size'), 'filesize', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n('Date created'), 'createdate', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n('Date modified'), 'modifydate', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelect->setDefault($this->_settings['filelist_sort']);

        return $htmlSelect->render();
    }

    /**
     * Generates a select box containing the sort order options (asc/desc).
     *
     * @return string rendered cHTMLSelectElement
     */
    private function _generateSortOrderSelect() {
        $htmlSelect = new cHTMLSelectElement('filelist_sortorder_' . $this->_id, '', 'filelist_sortorder_' . $this->_id);

        $htmlSelectOption = new cHTMLOptionElement(i18n('Ascending'), 'asc', true);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n('Descending'), 'desc', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        // set default value
        $htmlSelect->setDefault($this->_settings['filelist_sortorder']);

        return $htmlSelect->render();
    }

    /**
     * Generates a list of meta data.
     *
     * @return string HTML code showing a list of meta data
     */
    private function _generateMetaDataList() {
        $template = new cTemplate();

        foreach ($this->_metaDataIdents as $identName => $translation) {
            $metaDataLimit = $this->_settings['filelist_md_' . $identName . '_limit'];
            if (!isset($metaDataLimit) || $metaDataLimit === '') {
                $metaDataLimit = 0;
            }

            $template->set('d', 'METADATA_NAME', $identName);
            $template->set('d', 'METADATA_DISPLAYNAME', i18n($translation));
            $template->set('d', 'METADATA_LIMIT', $metaDataLimit);
            $template->set('d', 'ID', $this->_id);

            $template->next();
        }

        return $template->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_filelist_metadata_limititem.html', true);
    }

    /**
     * Generates code for the filter tab.
     *
     * @return string - the code for the filter link tab
     */
    private function _generateTabFilter() {
        // define a wrapper which contains the whole content of the filter tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        $wrapperContent[] = new cHTMLParagraph(i18n('Filter settings'), 'head_sub');

        $wrapperContent[] = new cHTMLLabel(i18n('Displayed file extensions'), 'filelist_extensions_' . $this->_id);
        $wrapperContent[] = $this->_generateExtensionSelect();
        $wrapperContent[] = '<br>';
        $link = new cHTMLLink('#');
        $link->setID('filelist_all_extensions');
        $link->setContent(i18n('Select all entries'));
        $wrapperContent[] = $link;
        $wrapperContent[] = new cHTMLLabel(i18n('Ignore selection (use all)'), 'filelist_ignore_extensions_' . $this->_id, 'filelist_ignore_extensions');
        $wrapperContent[] = new cHTMLCheckbox('filelist_ignore_extensions_' . $this->_id, '', 'filelist_ignore_extensions_' . $this->_id, ($this->_settings['filelist_ignore_extensions'] !== 'false'));

        $wrapperContent[] = new cHTMLLabel(i18n('File size limit (in MiB)'), 'filelist_filesizefilter_from_' . $this->_id);
        $default = (!empty($this->_settings['filelist_filesizefilter_from'])) ? $this->_settings['filelist_filesizefilter_from'] : '0';
        $wrapperContent[] = new cHTMLTextbox('filelist_filesizefilter_from_' . $this->_id, $default, '', '', 'filelist_filesizefilter_from_' . $this->_id);
        $wrapperContent[] = new cHTMLSpan('&nbsp;-&nbsp;');
        $default = (!empty($this->_settings['filelist_filesizefilter_to'])) ? $this->_settings['filelist_filesizefilter_to'] : '0';
        $wrapperContent[] = new cHTMLTextbox('filelist_filesizefilter_to_' . $this->_id, $default, '', '', 'filelist_filesizefilter_to_' . $this->_id);

        $wrapperContent[] = new cHTMLLabel(i18n('Creation date limit'), 'filelist_creationdatefilter_from_' . $this->_id);
        $default = (!empty($this->_settings['filelist_creationdatefilter_from'])) ? $this->_settings['filelist_creationdatefilter_from'] : 'DD.MM.YYYY';
        $wrapperContent[] = new cHTMLTextbox('filelist_creationdatefilter_from_' . $this->_id, $default, '', '', 'filelist_creationdatefilter_from_' . $this->_id);
        $wrapperContent[] = new cHTMLSpan('&nbsp;-&nbsp;');
        $default = (!empty($this->_settings['filelist_creationdatefilter_to'])) ? $this->_settings['filelist_creationdatefilter_to'] : 'DD.MM.YYYY';
        $wrapperContent[] = new cHTMLTextbox('filelist_creationdatefilter_to_' . $this->_id, $default, '', '', 'filelist_creationdatefilter_to_' . $this->_id);

        $wrapperContent[] = new cHTMLLabel(i18n('Modify date limit'), 'filelist_modifydatefilter_from_' . $this->_id);
        $default = (!empty($this->_settings['filelist_modifydatefilter_from'])) ? $this->_settings['filelist_modifydatefilter_from'] : 'DD.MM.YYYY';
        $wrapperContent[] = new cHTMLTextbox('filelist_modifydatefilter_from_' . $this->_id, $default, '', '', 'filelist_modifydatefilter_from_' . $this->_id);
        $wrapperContent[] = new cHTMLSpan('&nbsp;-&nbsp;');
        $default = (!empty($this->_settings['filelist_modifydatefilter_to'])) ? $this->_settings['filelist_modifydatefilter_to'] : 'DD.MM.YYYY';
        $wrapperContent[] = new cHTMLTextbox('filelist_modifydatefilter_to_' . $this->_id, $default, '', '', 'filelist_modifydatefilter_to_' . $this->_id);

        $wrapperContent[] = new cHTMLLabel(i18n('File count'), 'filelist_filecount_' . $this->_id);
        $default = (!empty($this->_settings['filelist_filecount'])) ? $this->_settings['filelist_filecount'] : '0';
        $wrapperContent[] = new cHTMLTextbox('filelist_filecount_' . $this->_id, $default, '', '', 'filelist_filecount_' . $this->_id);

        $wrapper->setContent($wrapperContent);

        return $wrapper->render();
    }

    /**
     * Generates a select box containing the file extensions.
     *
     * @return string rendered cHTMLSelectElement
     */
    private function _generateExtensionSelect() {
        $htmlSelect = new cHTMLSelectElement('filelist_extensions_' . $this->_id, '', 'filelist_extensions_' . $this->_id, ($this->_settings['filelist_ignore_extensions'] !== 'false'), '', '', 'manual');

        // set other avariable options manually
        foreach ($this->_fileExtensions as $fileExtension) {
            $htmlSelectOption = new cHTMLOptionElement(uplGetFileTypeDescription($fileExtension) . ' (.' . $fileExtension . ')', $fileExtension, false);
            $htmlSelectOption->setAlt(uplGetFileTypeDescription($fileExtension) . ' (.' . $fileExtension . ')');
            $htmlSelect->appendOptionElement($htmlSelectOption);
        }

        $additionalOptions = getEffectiveSettingsByType('cms_filelist_extensions');
        foreach ($additionalOptions as $label => $extension) {
            $htmlSelectOption = new cHTMLOptionElement($label . ' (.' . $extension . ')', $extension);
            $htmlSelectOption->setAlt($label . ' (.' . $extension . ')');
            $htmlSelect->appendOptionElement($htmlSelectOption);
        }

        // set default values
        $extensions = (is_array($this->_settings['filelist_extensions'])) ? $this->_settings['filelist_extensions'] : array(
            $this->_settings['filelist_extensions']
        );
        $htmlSelect->setSelected($extensions);
        $htmlSelect->setMultiselect();
        $htmlSelect->setSize(5);

        return $htmlSelect->render();
    }

    /**
     * Checks whether the directory defined by the given directory
     * information is the currently active directory.
     *
     * @param array $dirData directory information
     * @return boolean whether the directory is the currently active directory
     */
    protected function _isActiveDirectory(array $dirData) {
        return is_array($this->_settings['filelist_directories']) && in_array($dirData['path'] . $dirData['name'], $this->_settings['filelist_directories']);
    }

    /**
     * Checks whether the directory defined by the given directory information
     * should be shown expanded.
     *
     * @param array $dirData directory information
     * @return boolean whether the directory should be shown expanded
     */
    protected function _shouldDirectoryBeExpanded(array $dirData) {
        if (is_array($this->_settings['filelist_directories'])) {
            foreach ($this->_settings['filelist_directories'] as $directoryName) {
                if (preg_match('#^' . $dirData['path'] . $dirData['name'] . '/.*#', $directoryName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generates code for the manual tab.
     *
     * @return string - the code for the manual link tab
     */
    private function _generateTabManual() {
        // define a wrapper which contains the whole content of the manual tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        $wrapperContent[] = new cHTMLParagraph(i18n('Manual settings'), 'head_sub');

        $wrapperContent[] = new cHTMLLabel(i18n('Use manual file list?'), 'filelist_manual_' . $this->_id);
        $wrapperContent[] = new cHTMLCheckbox('filelist_manual_' . $this->_id, '', 'filelist_manual_' . $this->_id, ($this->_settings['filelist_manual'] === 'true'));

        $manualDiv = new cHTMLDiv();
        $manualDiv->setID('manual_filelist_setting');
        $manualDiv->appendStyleDefinition('display', 'none');
        $divContent = array();
        $divContent[] = new cHTMLParagraph(i18n('Existing files'), 'head_sub');
        $divContent[] = $this->_generateExistingFileSelect();
        $divContent[] = new cHTMLSpan(i18n('Already configured entries can be deleted by using double click'), 'filelist_manual_' . $this->_id);
        $divContent[] = new CHTMLSpan('<br><br>', 'filelist_manual_' . $this->_id);
        $divContent[] = new cHTMLParagraph(i18n('Add file'), 'head_sub');
        $divContent[] = new cHTMLLabel(i18n('Directory'), '');

        // directory navigation
        $directoryList = new cHTMLDiv('', 'directoryList', 'directoryList_' . $this->_id . '_manual');
        $liRoot = new cHTMLListItem('root', 'last');
        $directoryListCode = $this->generateDirectoryList($this->buildDirectoryList());
        $liRoot->setContent(array(
            '<em>Uploads</em>',
            $directoryListCode
        ));
        $conStrTree = new cHTMLList('ul', 'con_str_tree', 'con_str_tree', $liRoot);
        $directoryList->setContent($conStrTree);
        $divContent[] = $directoryList;

        $divContent[] = new cHTMLLabel(i18n('File'), 'filelist_filename_' . $this->_id, 'filelist_filename');
        $divContent[] = $this->generateFileSelect();
        $image = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . 'images/but_art_new.gif');
        $image->setAttribute('id', 'add_file');
        $image->appendStyleDefinition('cursor', 'pointer');
        $divContent[] = $image;

        $manualDiv->setContent($divContent);
        $wrapperContent[] = $manualDiv;

        $wrapper->setContent($wrapperContent);

        return $wrapper->render();
    }

    /**
     * Generate a select box containing the already existing files in the manual
     * tab.
     *
     * @return string rendered cHTMLSelectElement
     */
    private function _generateExistingFileSelect() {
        $selectedFiles = $this->_settings['filelist_manual_files'];
        $htmlSelect = new cHTMLSelectElement('filelist_manual_files_' . $this->_id, '', 'filelist_manual_files_' . $this->_id, false, '', '', 'manual');

        if (is_array($selectedFiles)) { // More than one entry
            foreach ($selectedFiles as $selectedFile) {
                $splits = explode('/', $selectedFile);
                $splitCount = count($splits);
                $fileName = $splits[$splitCount - 1];
                $htmlSelectOption = new cHTMLOptionElement($fileName, $selectedFile, true);
                $htmlSelectOption->setAlt($fileName);
                $htmlSelect->appendOptionElement($htmlSelectOption);
            }
        } elseif (!empty($selectedFiles)) { // Only one entry
            $splits = explode('/', $selectedFiles);
            $splitCount = count($splits);
            $fileName = $splits[$splitCount - 1];
            $htmlSelectOption = new cHTMLOptionElement($fileName, $selectedFiles, true);
            $htmlSelectOption->setAlt($fileName);
            $htmlSelect->appendOptionElement($htmlSelectOption);
        }

        // set default values
        $htmlSelect->setMultiselect();
        $htmlSelect->setSize(5);

        return $htmlSelect->render();
    }

    /**
     * Generate a select box containing all files for the manual tab.
     *
     * @param string $directoryPath Path to directory of the files
     * @return string rendered cHTMLSelectElement
     */
    public function generateFileSelect($directoryPath = '') {
        $htmlSelect = new cHTMLSelectElement('filelist_filename_' . $this->_id, '', 'filelist_filename_' . $this->_id, false, '', '', 'filelist_filename');

        $i = 0;
        if ($directoryPath != '') {
            $handle = opendir($this->_uploadPath . $directoryPath);
            while (($entry = readdir($handle)) !== false) {
                if (is_file($this->_uploadPath . $directoryPath . '/' . $entry)) {
                    $htmlSelectOption = new cHTMLOptionElement($entry, $directoryPath . '/' . $entry);
                    $htmlSelect->addOptionElement($i, $htmlSelectOption);
                    $i++;
                }
            }
            closedir($handle);
        }

        if ($i === 0) {
            $htmlSelectOption = new cHTMLOptionElement(i18n('No files found'), '');
            $htmlSelectOption->setAlt(i18n('No files found'));
            $htmlSelectOption->setDisabled(true);
            $htmlSelect->addOptionElement($i, $htmlSelectOption);
            $htmlSelect->setDisabled(true);
            $htmlSelect->setDefault('');
        }

        return $htmlSelect->render();
    }

}