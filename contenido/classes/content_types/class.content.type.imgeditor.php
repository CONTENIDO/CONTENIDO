<?php

/**
 * This file contains the cContentTypeImgeditor class.
 *
 * @package    Core
 * @subpackage ContentType
 * @author     Fulai Zhang
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.upl.php');

/**
 * Content type CMS_IMGEDITOR which lets the editor select an image.
 *
 * @package    Core
 * @subpackage ContentType
 */
class cContentTypeImgeditor extends cContentTypeAbstractTabbed
{

    /**
     * The name of the directory where the image is stored.
     *
     * @var string
     */
    private $_dirname;

    /**
     * The name of the image file.
     *
     * @var string
     */
    private $_filename;

    /**
     * The full path to the image file.
     *
     * @var string
     */
    protected $_imagePath;

    /**
     * The file type of the image file.
     *
     * @var string
     */
    private $_fileType;

    /**
     * The size of the image file.
     *
     * @var string
     */
    private $_fileSize;

    /**
     * The medianame of the image.
     *
     * @var string
     */
    private $_medianame;

    /**
     * The description of the image.
     *
     * @var string
     */
    protected $_description;

    /**
     * The keywords of the image.
     *
     * @var string
     */
    private $_keywords;

    /**
     * The internal notice of the image.
     *
     * @var string
     */
    private $_internalNotice;

    /**
     * The copyright of the image.
     *
     * @var string
     */
    private $_copyright;

    /**
     * Upload file item instance
     * @var cApiUpload
     */
    private $_upload;

    /**
     * Constructor to create an instance of this class.
     *
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings
     *         the raw settings in an XML structure or as plaintext
     * @param int    $id
     *         ID of the content type, e.g. 3 if CMS_DATE[3] is used
     * @param array  $contentTypes
     *         array containing the values of all content types
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($rawSettings, $id, array $contentTypes)
    {
        // set props
        $this->_type = 'CMS_IMGEDITOR';
        $this->_prefix = 'imgeditor';
        $this->_formFields = [
            'image_filename',
            'image_medianame',
            'image_description',
            'image_keywords',
            'image_internal_notice',
            'image_copyright'
        ];

        // call parent constructor
        parent::__construct($rawSettings, $id, $contentTypes);

        // if form is submitted, store the current teaser settings
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        $postAction = $_POST[$this->_prefix . '_action'] ?? '';
        $postId = cSecurity::toInteger($_POST[$this->_prefix . '_id'] ?? '0');
        if ($postAction === 'store' && $postId == $this->_id) {
            $this->_storeSettings();
        }

        $this->_medianame = '';
        $this->_description = '';
        $this->_keywords = '';
        $this->_internalNotice = '';
        $this->_copyright = '';
        $this->_dirname = '';
        $this->_filename = '';
        if ($this->_rawSettings) {
            // get image information from con_upl from the database
            $upload = new cApiUpload($this->_rawSettings);
            $this->_filename = $upload->get('filename');
            $this->_dirname = empty($upload->get('dirname')) ? '' : $upload->get('dirname');
            $this->_imagePath = $this->_generateImagePath();
            $this->_fileType = $upload->get('filetype');
            $this->_fileSize = $upload->get('size');

            // get image information from con_upl_meta from the database
            $uploadMeta = new cApiUploadMeta();

            $uploadMeta->loadByMany([
                'idupl' => $this->_rawSettings,
                'idlang' => $this->_lang
            ]);
            $this->_medianame = ($uploadMeta->get('medianame') !== false) ? $uploadMeta->get('medianame') : '';
            $this->_description = ($uploadMeta->get('description') !== false) ? $uploadMeta->get('description') : '';
            $this->_keywords = ($uploadMeta->get('keywords') !== false) ? $uploadMeta->get('keywords') : '';
            $this->_internalNotice = ($uploadMeta->get('internal_notice') !== false) ? $uploadMeta->get('internal_notice') : '';
            $this->_copyright = ($uploadMeta->get('copyright') !== false) ? $uploadMeta->get('copyright') : '';
        }

        // Define settings for this content type as an array
        $this->_settings = [];
    }

    /**
     * Return the raw settings of a content type
     *
     * @param string $contentTypeName
     *                         Content type name
     * @param int    $id
     *                         ID of the content type
     * @param array  $contentTypes
     *                         Content type array
     * @param bool   $editable [optional]
     * @return string The raw setting or an empty string
     * @throws cDbException
     * @throws cException
     */
    protected function _getRawSettings($contentTypeName, $id, array $contentTypes, $editable = false): string
    {
        $id = cSecurity::toInteger($id);
        if (!isset($contentTypes[$contentTypeName][$id])) {
            $idArtLang = cSecurity::toInteger(cRegistry::getArticleLanguageId());
            // Get the idtype of the content type and then the settings
            $typeItem = new cApiType();
            $typeItem->loadByType($contentTypeName);
            $idtype = cSecurity::toInteger($typeItem->get('idtype'));
            if (!$editable) {
                return $this->_getRawSettingsFromContent($idArtLang, $idtype, cSecurity::toInteger($id));
            } else {
                return $this->_getRawSettingsFromContentVersion($idArtLang, $idtype, cSecurity::toInteger($id));
            }
        } else {
            return cSecurity::toString($contentTypes[$contentTypeName][$id]);
        }
    }

    /**
     * Return the absolute path of the image
     *
     * @return string
     */
    public function getAbsolutePath(): string
    {
        return $this->_cfgClient[$this->_client]['upl']['path'] . $this->getRelativePath();
    }

    /**
     * Return the path of the image relative to the upload directory of the client
     *
     * @return string
     */
    public function getRelativePath(): string
    {
        return $this->_dirname . $this->_filename;
    }

    /**
     * Returns the absolute URL of the image
     *
     * @return string
     */
    public function getAbsoluteURL(): string
    {
        return $this->_generateImagePath();
    }

    /**
     * Returns the URL of the image relative to the client base URL
     *
     * @return string
     */
    public function getRelativeURL(): string
    {
        if (!empty($this->_filename)) {
            if (cApiDbfs::isDbfs($this->_dirname)) {
                return 'dbfs.php?file=' . urlencode($this->getRelativePath());
            } else {
                return $this->_cfgClient[$this->_client]['upload'] . $this->getRelativePath();
            }
        }

        return '';
    }

    /**
     * Returns an associative array containing the meta information of the image
     *
     * The array contains the following keys:
     * 'medianame'
     * 'description'
     * 'keywords'
     * 'internalnotice'
     * 'copyright'
     *
     * @return array
     */
    public function getMetaData(): array
    {
        return [
            'medianame' => $this->_medianame,
            'description' => $this->_description,
            'keywords' => $this->_keywords,
            'internalnotice' => $this->_internalNotice,
            'copyright' => $this->_copyright
        ];
    }

    /**
     * Generates the link to the image for use in the src attribute.
     *
     * @return string
     *         the link to the image
     */
    private function _generateImagePath(): string
    {
        if (!empty($this->_filename)) {
            if (cApiDbfs::isDbfs($this->_dirname)) {
                return cRegistry::getFrontendUrl()
                    . 'dbfs.php?file=' . urlencode($this->getRelativePath());
            } else {
                return cRegistry::getFrontendUrl()
                    . $this->_cfgClient[$this->_client]['upload'] . $this->getRelativePath();
            }
        }

        return '';
    }

    /**
     * Stores all values from the $_POST array in the $_settings attribute
     * (associative array) and saves them in the database (XML).
     *
     * @throws cDbException|cException
     */
    protected function _storeSettings()
    {
        $postFilename = $_POST['image_filename'] ?? '';

        // prepare the filename and dirname
        $filename = basename($postFilename);
        $dirname = ltrim(dirname($postFilename), '.');
        if (in_array($dirname, ['\\', '/'])) {
            $dirname = '';
        } elseif (!empty($dirname)) {
            $dirname .= '/';
        }

        if (empty($filename)) {
            $this->_rawSettings = '';
        } else {
            // get the upload ID
            $this->_upload = new cApiUpload();
            $this->_upload->loadByMany([
                'filename' => $filename,
                'dirname' => $dirname,
                'idclient' => $this->_client
            ], false);

            $this->_rawSettings = $this->_upload->get('idupl');
        }

        // save the content types
        conSaveContentEntry($this->_idArtLang, 'CMS_IMGEDITOR', $this->_id, $this->_rawSettings);
        $versioning = new cContentVersioning();
        if ($versioning->getState() != 'advanced') {
            conMakeArticleIndex($this->_idArtLang, $this->_idArt);
        }
        conGenerateCodeForArtInAllCategories($this->_idArt);

        $this->_updateUploadMeta();
    }

    /**
     * Updates the meta data of a selected image file.
     *
     * @return void
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    protected function _updateUploadMeta()
    {
        if (!$this->_upload instanceof cApiUpload || $this->_upload->isLoaded()) {
            return;
        }

        $idupl = cSecurity::toInteger($this->_upload->get('idupl'));
        if ($idupl < 1) {
            return;
        }

        // insert / update meta data
        $medianame = $_POST['image_medianame'] ?? '';
        $description = $_POST['image_description'] ?? '';
        $keywords = $_POST['image_keywords'] ?? '';
        $internal_notice = $_POST['image_internal_notice'] ?? '';
        $copyright = $_POST['image_copyright'] ?? '';

        $uploadMeta = new cApiUploadMeta();
        $uploadMeta->loadByMany([
            'idupl' => $idupl,
            'idlang' => $this->_lang
        ]);

        // if meta data object already exists, update the values
        if ($uploadMeta->get('id_uplmeta')) {
            $uploadMeta->set('idupl', $idupl);
            $uploadMeta->set('idlang', $this->_lang);
            $uploadMeta->set('medianame', $medianame);
            $uploadMeta->set('description', $description);
            $uploadMeta->set('keywords', $keywords);
            $uploadMeta->set('internal_notice', $internal_notice);
            $uploadMeta->set('copyright', $copyright);
            $uploadMeta->store();
        } else {
            // if metadata object does not exist yet, create a new one
            $uploadMetaCollection = new cApiUploadMetaCollection();
            $uploadMetaCollection->create($idupl, $this->_lang, $medianame, $description, $keywords, $internal_notice, $copyright);
        }
    }

    /**
     * @inheritDoc
     */
    public function generateViewCode()
    {
        $image = new cHTMLImage($this->_imagePath);
        $image->setAlt($this->_description);

        return $this->_encodeForOutput($image->render());
    }

    /**
     * @inheritDoc
     */
    public function generateEditCode()
    {
        // construct the top code of the template
        $templateTop = new cTemplate();
        $templateTop->set('s', 'ICON', 'images/but_editimage.gif');
        $templateTop->set('s', 'ID', $this->_id);
        $templateTop->set('s', 'PREFIX', $this->_prefix);
        $templateTop->set('s', 'HEADLINE', i18n('Image settings'));
        $codeTop = $templateTop->generate(
            $this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_top.html',
            true
        );

        $tabMenu = [
            'directories' => i18n('Directories'),
            'meta' => i18n('Meta'),
            'upload' => i18n('Upload')
        ];

        $templateTabs = new cTemplate();

        // create code for upload tab
        $templateTabs->set('d', 'TAB_ID', 'upload');
        $templateTabs->set('d', 'TAB_CLASS', 'upload');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabUpload());
        $templateTabs->set('s', 'PREFIX', $this->_prefix);
        $templateTabs->next();

        // create code for directories tab
        $templateTabs->set('d', 'TAB_ID', 'directories');
        $templateTabs->set('d', 'TAB_CLASS', 'directories');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabDirectories());
        $templateTabs->next();

        // create code for meta tab
        $templateTabs->set('d', 'TAB_ID', 'meta');
        $templateTabs->set('d', 'TAB_CLASS', 'meta');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabMeta());
        $templateTabs->next();

        $codeTabs = $templateTabs->generate(
            $this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_tabs.html',
            true
        );

        // Write setting dirname (without backslash at the end)
        if (!empty($this->_dirname) && cString::endsWith($this->_dirname, '/')) {
            $this->setSetting('dirname', cString::getPartOfString($this->_dirname, 0, -1));
        } else {
            $this->setSetting('dirname', $this->_dirname);
        }

        // construct the bottom code of the template
        $templateBottom = new cTemplate();
        $templateBottom->set('s', 'PATH_FRONTEND', cRegistry::getFrontendUrl());
        $templateBottom->set('s', 'ID', $this->_id);
        $templateBottom->set('s', 'PREFIX', $this->_prefix);
        $templateBottom->set('s', 'IDARTLANG', $this->_idArtLang);
        $templateBottom->set('s', 'FIELDS', "'" . implode("','", $this->_formFields) . "'");
        $templateBottom->set('s', 'SETTINGS', json_encode($this->getSettings()));
        $templateBottom->set(
            's', 'JS_CLASS_SCRIPT',
            cRegistry::getBackendUrl() . cAsset::backend('scripts/content_types/cmsImgeditor.js')
        );
        $templateBottom->set('s', 'JS_CLASS_NAME', 'Con.cContentTypeImgeditor');
        $codeBottom = $templateBottom->generate(
            $this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_bottom.html',
            true
        );

        // construct the whole template code
        $code = $this->_encodeForOutput($codeTop);
        $code .= $this->_generateTabMenuCode($tabMenu);
        $code .= $this->_encodeForOutput($codeTabs);
        $code .= $this->_generateActionCode();
        $code .= $this->_encodeForOutput($codeBottom);

        return $code;
    }

    /**
     * Generates code for the directories tab in which various settings can be
     * made.
     *
     * @return string
     *         the code for the directories tab
     */
    private function _generateTabDirectories(): string
    {
        // define a wrapper which contains the whole content of the directories tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = [];

        $directoryList = new cHTMLDiv('', 'directoryList', 'directoryList' . '_' . $this->_id);
        $liRoot = new cHTMLListItem('root', 'root');
        $aUpload = new cHTMLLink('#');
        $aUpload->setClass('on');
        $aUpload->setAttribute('title', 'upload');
        $aUpload->setContent('Uploads');

        $div = new cHTMLDiv([
            '<em><a href="#"></a></em>',
            $aUpload
        ]);

        $liRoot->setContent($div);
        $conStrTree = new cHTMLList('ul', 'con_str_tree', 'con_str_tree', $liRoot);
        $directoryList->setContent($conStrTree);
        $wrapperContent[] = $directoryList;
        $wrapperContent[] = new cHTMLDiv('', 'directoryFile', 'directoryFile' . '_' . $this->_id);
        $wrapperContent[] = new cHTMLDiv('', 'directoryShow', 'directoryShow_' . $this->_id);

        $wrapper->setContent($wrapperContent);
        return $wrapper->render();
    }

    /**
     * Generates code for the meta tab in which the image's metadata can be
     * edited.
     *
     * @return string
     *         the code for the meta tab
     * @throws cException
     */
    private function _generateTabMeta(): string
    {
        // define a wrapper which contains the whole content of the meta tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = [];

        $imageMetaUrl = new cHTMLSpan();
        $imageMetaUrl->setID('image_meta_url_' . $this->_id);
        $imageMetaUrl->setClass('image_meta_url');
        $wrapperContent[] = new cHTMLDiv([
            '<b>' . i18n('Selected file') . '</b>',
            $imageMetaUrl
        ]);
        $wrapperContent[] = new cHTMLLabel(i18n('Title'), 'image_medianame_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('image_medianame', $this->_medianame, '', '', 'image_medianame_' . $this->_id);
        $wrapperContent[] = new cHTMLLabel(i18n('Description'), 'image_description_' . $this->_id);
        $wrapperContent[] = new cHTMLTextarea('image_description', $this->_description, '', '', 'image_description_' . $this->_id);
        $wrapperContent[] = new cHTMLLabel(i18n('Keywords'), 'image_keywords_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('image_keywords', $this->_keywords, '', '', 'image_keywords_' . $this->_id);
        $wrapperContent[] = new cHTMLLabel(i18n('Internal notes'), 'image_internal_notice_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('image_internal_notice', $this->_internalNotice, '', '', 'image_internal_notice_' . $this->_id);
        $wrapperContent[] = new cHTMLLabel(i18n('Copyright'), 'image_copyright_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('image_copyright', $this->_copyright, '', '', 'image_copyright_' . $this->_id);

        $wrapper->setContent($wrapperContent);
        return $wrapper->render();
    }

    /**
     * Generates code for the upload tab in which new images can be uploaded.
     *
     * @return string
     *         the code for the upload tab
     * @throws cException
     */
    private function _generateTabUpload(): string
    {
        // define a wrapper which contains the whole content of the upload tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = [];

        // create a new directory form
        $newDirForm = new cHTMLForm();
        $newDirForm->setAttribute('name', 'newdir');
        $newDirForm->setAttribute('method', 'post');
        $newDirForm->setAttribute('action', cRegistry::getBackendUrl() . 'main.php');
        $caption1Span = new cHTMLSpan();
        $caption1Span->setID('caption1');
        $newDirHead = new cHTMLDiv([
            '<b>' . i18n('Create a directory in') . '</b>',
            $caption1Span
        ]);
        $area = new cHTMLHiddenField('area', 'upl');
        $action = new cHTMLHiddenField('action', 'upl_mkdir');
        $frame = new cHTMLHiddenField('frame', '2');
        $appendparameters = new cHTMLHiddenField('appendparameters');
        $contenido = new cHTMLHiddenField('contenido', cRegistry::getBackendSessionId());
        $path = new cHTMLHiddenField('path');
        $foldername = new cHTMLTextbox('foldername');
        $button = new cHTMLButton('', '', '', false, null, '', 'image');
        $button->setAttribute('src', cRegistry::getBackendUrl() . 'images/submit.gif');
        $newDirContent = new cHTMLDiv([
            $area,
            $action,
            $frame,
            $appendparameters,
            $contenido,
            $path,
            $foldername,
            $button
        ]);
        $newDirForm->setContent([
            $newDirHead,
            $newDirContent
        ]);
        $wrapperContent[] = $newDirForm;

        // upload a new file form
        $propertiesForm = new cHTMLForm();
        $propertiesForm->setID('properties' . $this->_id);
        $propertiesForm->setAttribute('name', 'properties');
        $propertiesForm->setAttribute('method', 'post');
        $propertiesForm->setAttribute('action', cRegistry::getBackendUrl() . 'main.php');
        $propertiesForm->setAttribute('enctype', 'multipart/form-data');
        $frame = new cHTMLHiddenField('frame', '4');
        $area = new cHTMLHiddenField('area', 'upl');
        $path = new cHTMLHiddenField('path');
        $file = new cHTMLHiddenField('file');
        $action = new cHTMLHiddenField('action', 'upl_upload');
        $appendparameters = new cHTMLHiddenField('appendparameters');
        $contenido = new cHTMLHiddenField('contenido', cRegistry::getBackendSessionId());
        $caption2Span = new cHTMLSpan();
        $caption2Span->setID('caption2');
        $propertiesHead = new cHTMLDiv([
            '<b>' . i18n('Path') . '</b>',
            $caption2Span
        ]);
        $imageUpload = new cHTMLUpload('file[]', '', '', 'cms_image_m' . $this->_id, false, null, '', 'file');
        $imageUpload->setClass('jqueryAjaxUpload');
        $propertiesForm->setContent([
            $frame,
            $area,
            $path,
            $file,
            $action,
            $appendparameters,
            $contenido,
            $propertiesHead,
            $imageUpload
        ]);
        $wrapperContent[] = $propertiesForm;

        $wrapperContent[] = new cHTMLImage(cRegistry::getBackendUrl() . 'images/ajax-loader.gif', 'loading');

        $wrapper->setContent($wrapperContent);
        return $wrapper->render();
    }

    /**
     * Generate a select box containing all files in the given directory.
     *
     * @SuppressWarnings docBlocks
     * @param string $directoryPath [optional]
     *         directory of the files
     * @return string
     *         rendered cHTMLSelectElement
     * @throws cException
     */
    public function generateFileSelect(string $directoryPath = ''): string
    {
        // make sure the path ends with a slash but does not start with a slash
        if ($directoryPath === '/') {
            $directoryPath = '';
        } elseif (!empty($directoryPath) && cString::getPartOfString($directoryPath, -1) != '/') {
            $directoryPath .= '/';
        }

        $htmlSelect = new cHTMLSelectElement('image_filename', '', 'image_filename_' . $this->_id);
        $htmlSelect->setSize(16);
        $htmlSelectOption = new cHTMLOptionElement('Kein', '', false);
        $htmlSelect->addOptionElement(0, $htmlSelectOption);

        $files = [];
        if (cDirHandler::exists($this->_uploadPath . $directoryPath)) {
            if (false !== ($handle = cDirHandler::read($this->_uploadPath . $directoryPath, false, false, true))) {
                foreach ($handle as $entry) {
                    if (false === cFileHandler::fileNameBeginsWithDot($entry)) {
                        $file = [];
                        $file["name"] = $entry;
                        $file["path"] = $directoryPath . $entry;
                        $files[] = $file;
                    }
                }
            }
        }

        usort($files, function($a, $b) {
            $a = cString::toLowerCase($a["name"]);
            $b = cString::toLowerCase($b["name"]);
            if ($a < $b) {
                return -1;
            } elseif ($a > $b) {
                return 1;
            } else {
                return 0;
            }
        });

        $i = 1;
        foreach ($files as $file) {
            $htmlSelectOption = new cHTMLOptionElement($file["name"], $file["path"]);
            $htmlSelect->addOptionElement($i, $htmlSelectOption);
            $i++;
        }

        if ($i === 0) {
            $htmlSelectOption = new cHTMLOptionElement(i18n('No files found'), '', false);
            $htmlSelectOption->setAlt(i18n('No files found'));
            $htmlSelectOption->setDisabled(true);
            $htmlSelect->addOptionElement($i, $htmlSelectOption);
            $htmlSelect->setDisabled(true);
        }

        // set default value
        $htmlSelect->setDefault($this->getRelativePath());

        return $htmlSelect->render();
    }

    /**
     * Checks whether the directory defined by the given directory
     * information is the currently active directory.
     * Overwrite in subclasses if you use getDirectoryList!
     *
     * @param array $dirData
     *         directory information
     * @return bool
     *         whether the directory is the currently active directory
     */
    protected function _isActiveDirectory(array $dirData): bool
    {
        return $dirData['path'] . $dirData['name'] . '/' === $this->_dirname;
    }

    /**
     * Checks whether the directory defined by the given directory information
     * should be shown expanded.
     * Overwrite in subclasses if you use getDirectoryList!
     *
     * @param array $dirData
     *         directory information
     * @return bool
     *         whether the directory should be shown expanded
     */
    protected function _shouldDirectoryBeExpanded(array $dirData): bool
    {
        return $this->_isSubdirectory($dirData['path'] . $dirData['name'], $this->_dirname);
    }

    /**
     * Gets the metadata of the image with the given filename/dirname.
     *
     * @param string $filename
     *         the filename of the image
     * @param string $dirname
     *         the dirname of the image
     * @return string|false
     *         JSON-encoded array with metadata
     * @throws cDbException
     * @throws cException
     */
    public function getImageMeta($filename, $dirname)
    {
        $upload = new cApiUpload();
        $upload->loadByMany([
            'filename' => $filename,
            'dirname' => $dirname,
            'idclient' => $this->_client
        ], false);
        $idupl = $upload->get('idupl');

        $uploadMeta = new cApiUploadMeta();
        $uploadMeta->loadByMany([
            'idupl' => $idupl,
            'idlang' => $this->_lang
        ]);

        $imageMeta = [];
        $imageMeta['medianame'] = ($uploadMeta->get('medianame') !== false) ? $uploadMeta->get('medianame') : '';
        $imageMeta['description'] = ($uploadMeta->get('description') !== false) ? $uploadMeta->get('description') : '';
        $imageMeta['keywords'] = ($uploadMeta->get('keywords') !== false) ? $uploadMeta->get('keywords') : '';
        $imageMeta['internal_notice'] = ($uploadMeta->get('internal_notice') !== false) ? $uploadMeta->get('internal_notice') : '';
        $imageMeta['copyright'] = ($uploadMeta->get('copyright') !== false) ? $uploadMeta->get('copyright') : '';

        return json_encode($imageMeta);
    }

    /**
     * Creates an upload directory.
     * Wrapper function for uplmkdir in functions.upl.php.
     *
     * @param string $path
     *         Path to directory to create, either path from client upload
     *         directory or a dbfs path
     * @param string $name
     *         Name of directory to create
     * @return string|void
     *         value of filemode as string ('0702') or nothing
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function uplmkdir(string $path, string $name)
    {
        return uplmkdir($path, $name);
    }

    /**
     * Uploads the transmitted files saved in the $_FILES array.
     *
     * @param string $path
     *         the path to which the file should be uploaded
     * @return string
     *         the filename of the uploaded file
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function uplupload(string $path): string
    {
        $uplFilename = '';
        if (count($_FILES) === 1) {
            foreach ($_FILES['file']['name'] as $key => $value) {
                if (file_exists($_FILES['file']['tmp_name'][$key])) {
                    $friendlyName = uplCreateFriendlyName($_FILES['file']['name'][$key]);
                    move_uploaded_file($_FILES['file']['tmp_name'][$key], $this->_cfgClient[$this->_client]['upl']['path'] . $path . $friendlyName);

                    cDebug::out(":::" . $path);
                    uplSyncDirectory($path);

                    $upload = new cApiUpload();
                    $upload->loadByMany([
                        'dirname' => $path,
                        'filename' => $_FILES['file']['name'][$key]
                    ], false);
                    if ($upload->get('idupl')) {
                        $uplFilename = $this->_cfgClient[$this->_client]['upl']['htmlpath'] . $upload->get('dirname') . $upload->get('filename');
                    } else {
                        $uplFilename = 'error';
                    }
                }
            }
        }

        return $uplFilename;
    }

    /**
     * Returns the raw settings from content version by article language id,
     * content type id, and content id.
     *
     * @since CONTENIDO 4.10.2
     * @param int $idArtLang Article language id
     * @param int $idType Content type id (e.g. id of `CONTENT_TYPE`)
     * @param int $typeId Content id (e.g. the ID in `CONTENT_TYPE[ID]`)
     * @return string
     * @throws cDbException|cException
     */
    protected function _getRawSettingsFromContentVersion(
        int $idArtLang, int $idType, int $typeId
    ): string
    {
        $contentVersionColl = new cApiContentVersionCollection();
        $idContentVersion = $contentVersionColl->getMaximumVersionByArticleLanguageId($idArtLang, $idType, $typeId);
        $contentVersion = new cApiContentVersion($idContentVersion);
        if ($contentVersion->get('deleted') != 1) {
            return cSecurity::toString($contentVersion->get('value'));
        } else {
            return '';
        }
    }

}
