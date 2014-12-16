<?php
/**
 * This file contains the cContentTypeImgeditor class.
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
 * Content type CMS_IMGEDITOR which lets the editor select an image.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeImgeditor extends cContentTypeAbstractTabbed {

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
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *        plaintext
     * @param int $id ID of the content type, e.g. 3 if CMS_DATE[3] is
     *        used
     * @param array $contentTypes array containing the values of all content
     *        types
     */
    public function __construct($rawSettings, $id, array $contentTypes) {
		global $area;
	
        // change attributes from the parent class and call the parent
        // constructor
        $this->_type = 'CMS_IMGEDITOR';
        $this->_prefix = 'imgeditor';
        $this->_formFields = array(
            'image_filename',
            'image_medianame',
            'image_description',
            'image_keywords',
            'image_internal_notice',
            'image_copyright'
        );
        parent::__construct($rawSettings, $id, $contentTypes);

		// if form is submitted, store the current teaser settings
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        if (isset($_POST[$this->_prefix . '_action']) && $_POST[$this->_prefix . '_action'] === 'store' && isset($_POST[$this->_prefix . '_id']) && (int) $_POST[$this->_prefix . '_id'] == $this->_id) {
            $this->_storeSettings();
        }
		
        // get image information from con_upl from the database
        $upload = new cApiUpload($this->_rawSettings);
        $this->_filename = $upload->get('filename');
        $this->_dirname = $upload->get('dirname');
        $this->_imagePath = $this->_generateImagePath();
        $this->_fileType = $upload->get('filetype');
        $this->_fileSize = $upload->get('size');

        // get image information from con_upl_meta from the database
        $uploadMeta = new cApiUploadMeta();
        $uploadMeta->loadByMany(array(
            'idupl' => $this->_rawSettings,
            'idlang' => $this->_lang
        ));
        $this->_medianame = ($uploadMeta->get('medianame') !== false) ? $uploadMeta->get('medianame') : '';
        $this->_description = ($uploadMeta->get('description') !== false) ? $uploadMeta->get('description') : '';
        $this->_keywords = ($uploadMeta->get('keywords') !== false) ? $uploadMeta->get('keywords') : '';
        $this->_internalNotice = ($uploadMeta->get('internal_notice') !== false) ? $uploadMeta->get('internal_notice') : '';
        $this->_copyright = ($uploadMeta->get('copyright') !== false) ? $uploadMeta->get('copyright') : '';

    }

    /**
     * Return the absolute path of the image
     *
     * @return string
     */
    public function getAbsolutePath() {
        return $this->_cfgClient[$this->_client]['upl']['path'] . $this->_dirname . $this->_filename;
    }

    /**
     * Return the path of the image relative to the upload directory of the client
     *
     * @return string
     */
    public function getRelativePath() {
        return $this->_dirname . $this->_filename;
    }

    /**
     * Returns the absolute URL of the image
     *
     * @return string
     */
    public function getAbsoluteURL() {
        return $this->_generateImagePath();
    }

    /**
     * Returns the URL of the image relative to the client base URL
     *
     * @return string
     */
    public function getRelativeURL() {
        if (!empty($this->_filename)) {
            if (cApiDbfs::isDbfs($this->_dirname)) {
                return 'dbfs.php?file=' . urlencode($this->_dirname . $this->_filename);
            } else {
                return $this->_cfgClient[$this->_client]['upload'] . $this->_dirname . $this->_filename;
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
    public function getMetaData() {
        return array(
            'medianame' => $this->_medianame,
            'description' => $this->_description,
            'keywords' => $this->_keywords,
            'internalnotice' => $this->_internalNotice,
            'copyright' => $this->_copyright
        );
    }

    /**
     * Generates the link to the image for use in the src attribute.
     *
     * @return string the link to the image
     */
    private function _generateImagePath() {
        if (!empty($this->_filename)) {
            if (cApiDbfs::isDbfs($this->_dirname)) {
                return $this->_cfgClient[$this->_client]['path']['htmlpath'] . 'dbfs.php?file=' . urlencode($this->_dirname . $this->_filename);
            } else {
                return $this->_cfgClient[$this->_client]['path']['htmlpath'] . $this->_cfgClient[$this->_client]['upload'] . $this->_dirname . $this->_filename;
            }
        }

        return '';
    }

    /**
     * Stores all values from the $_POST array in the $_settings attribute
     * (associative array) and saves them in the database (XML).
     *
     */
    protected function _storeSettings() {
        // prepare the filename and dirname
        $filename = basename($_POST['image_filename']);
        $dirname = dirname($_POST['image_filename']);
        if ($dirname === '\\' || $dirname === '/') {
            $dirname = '';
        } else {
            $dirname .= '/';
        }

        // get the upload ID
        $upload = new cApiUpload();
        $upload->loadByMany(array(
            'filename' => $filename,
            'dirname' => $dirname,
            'idclient' => $this->_client
        ), false);

        $this->_rawSettings = $upload->get('idupl');

        // save the content types
        conSaveContentEntry($this->_idArtLang, 'CMS_IMGEDITOR', $this->_id, $this->_rawSettings);
        conMakeArticleIndex($this->_idArtLang, $this->_idArt);
        conGenerateCodeForArtInAllCategories($this->_idArt);

        // insert / update meta data
        $medianame = $_POST['image_medianame'];
        $description = $_POST['image_description'];
        $keywords = $_POST['image_keywords'];
        $internal_notice = $_POST['image_internal_notice'];
        $copyright = $_POST['image_copyright'];

        // load meta data object
        $uploadMeta = new cApiUploadMeta();
        $uploadMeta->loadByMany(array(
            'idupl' => $this->_rawSettings,
            'idlang' => $this->_lang
        ));
        // if meta data object already exists, update the values
        if ($uploadMeta->get('id_uplmeta') != false) {
            $uploadMeta->set('idupl', $this->_rawSettings);
            $uploadMeta->set('idlang', $this->_lang);
            $uploadMeta->set('medianame', $medianame);
            $uploadMeta->set('description', $description);
            $uploadMeta->set('keywords', $keywords);
            $uploadMeta->set('internal_notice', $internal_notice);
            $uploadMeta->set('copyright', $copyright);
            $uploadMeta->store();
        } else {
            // if meta data object does not exist yet, create a new one
            $uploadMetaCollection = new cApiUploadMetaCollection();
            $uploadMetaCollection->create($this->_rawSettings, $this->_lang, $medianame, $description, $keywords, $internal_notice, $copyright);
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
        $image = new cHTMLImage($this->_imagePath);
        $image->setAlt($this->_description);

        return $this->_encodeForOutput($image->render());
    }

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string escaped HTML code which should be shown if content type is
     *         edited
     */
    public function generateEditCode() {
        // construct the top code of the template
        $templateTop = new cTemplate();
        $templateTop->set('s', 'ICON', 'images/but_editimage.gif');
        $templateTop->set('s', 'ID', $this->_id);
        $templateTop->set('s', 'PREFIX', $this->_prefix);
        $templateTop->set('s', 'HEADLINE', i18n('Image settings'));
        $codeTop = $templateTop->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_top.html', true);

        $tabMenu = array(
            'directories' => i18n('Directories'),
            'meta' => i18n('Meta'),
            'upload' => i18n('Upload')
        );

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

        $codeTabs = $templateTabs->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_tabs.html', true);

        // construct the bottom code of the template
        $templateBottom = new cTemplate();
        $templateBottom->set('s', 'PATH_FRONTEND', $this->_cfgClient[$this->_client]['path']['htmlpath']);
        $templateBottom->set('s', 'ID', $this->_id);
        $templateBottom->set('s', 'PREFIX', $this->_prefix);
        $templateBottom->set('s', 'IDARTLANG', $this->_idArtLang);
        $templateBottom->set('s', 'FIELDS', "'" . implode("','", $this->_formFields) . "'");
        $templateBottom->set('s', 'SETTINGS', json_encode($this->_settings));
        $templateBottom->set('s', 'JS_CLASS_SCRIPT', $this->_cfg['path']['contenido_fullhtml'] . 'scripts/content_types/cmsImgeditor.js');
        $templateBottom->set('s', 'JS_CLASS_NAME', 'Con.cContentTypeImgeditor');
        $codeBottom = $templateBottom->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_bottom.html', true);

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
     * @return string - the code for the directories tab
     */
    private function _generateTabDirectories() {
        // define a wrapper which contains the whole content of the directories
        // tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        $directoryList = new cHTMLDiv('', 'directoryList', 'directoryList' . '_' . $this->_id);
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
        $liRoot->setContent(array(
            $div,
            $directoryListCode
        ));
        $conStrTree = new cHTMLList('ul', 'con_str_tree', 'con_str_tree', $liRoot);
        $directoryList->setContent($conStrTree);
        $wrapperContent[] = $directoryList;

        $wrapperContent[] = new cHTMLDiv($this->generateFileSelect($this->_dirname), 'directoryFile', 'directoryFile' . '_' . $this->_id);

        $directoryShow = new cHTMLDiv('', 'directoryShow', 'directoryShow_' . $this->_id);
        $imagePath = $this->_imagePath;
        $imageFilename = str_replace($this->_cfgClient[$this->_client]['path']['htmlpath'], $this->_cfgClient[$this->_client]['path']['frontend'], $imagePath);
        $imageFiletype = substr($imagePath, strlen($imagePath) - 4, 4);
        $imageExtensions = array(
            '.gif',
            '.png',
            '.jpg',
            'jpeg'
        );
        if (in_array($imageFiletype, $imageExtensions)) {
            $imagePath = cApiImgScale($imageFilename, 428, 210);
        }
        $image = new cHTMLImage($imagePath);
        $directoryShow->setContent($image);
        $wrapperContent[] = $directoryShow;

        $wrapper->setContent($wrapperContent);
        return $wrapper->render();
    }

    /**
     * Generates code for the meta tab in which the images's meta data can be
     * edited.
     *
     * @return string - the code for the meta tab
     */
    private function _generateTabMeta() {
        // define a wrapper which contains the whole content of the meta tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        $imageMetaUrl = new cHTMLSpan();
        $imageMetaUrl->setID('image_meta_url_' . $this->_id);
        $imageMetaUrl->setClass('image_meta_url');
        $wrapperContent[] = new cHTMLDiv(array(
            '<b>' . i18n('Selected file') . '</b>',
            $imageMetaUrl
        ));
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
     * @return string - the code for the upload tab
     */
    private function _generateTabUpload() {
        // define a wrapper which contains the whole content of the upload tab
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

        $wrapper->setContent($wrapperContent);
        return $wrapper->render();
    }

    /**
     * Generate a select box containing all files in the given directory.
     *
     * @param string $directoryPath directory of the files
     * @return string rendered cHTMLSelectElement
     */
    public function generateFileSelect($directoryPath = '') {
        // make sure the path ends with a slash
        if (substr($directoryPath, -1) != '/') {
            $directoryPath .= '/';
        }

        $htmlSelect = new cHTMLSelectElement('image_filename', '', 'image_filename_' . $this->_id);
        $htmlSelect->setSize(16);
        $htmlSelectOption = new cHTMLOptionElement('Kein', '', false);
        $htmlSelect->addOptionElement(0, $htmlSelectOption);

        $files = array();
        if (is_dir($this->_uploadPath . $directoryPath)) {
            if (false !== ($handle = cDirHandler::read($this->_uploadPath . $directoryPath, false, false, true))) {
                foreach ($handle as $entry) {
                    if (false === cFileHandler::fileNameBeginsWithDot($entry)) {
                        $file = array();
                        $file["name"] = $entry;
                        $file["path"] = $directoryPath . $entry;
                        $files[] = $file;
                    }
                }
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

        if ($i === 0) {
            $htmlSelectOption = new cHTMLOptionElement(i18n('No files found'), '', false);
            $htmlSelectOption->setAlt(i18n('No files found'));
            $htmlSelectOption->setDisabled(true);
            $htmlSelect->addOptionElement($i, $htmlSelectOption);
            $htmlSelect->setDisabled(true);
        }

        // set default value
        if (isset($this->_dirname)) {
            $htmlSelect->setDefault($this->_dirname . $this->_filename);
        } else {
            $htmlSelect->setDefault('');
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
        return $dirData['path'] . $dirData['name'] . '/' === $this->_dirname;
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

    /**
     * Gets the meta data of the image with the given filename/dirname.
     *
     * @param string $filename the filename of the image
     * @param string $dirname the dirname of the image
     * @return string JSON-encoded array with meta data
     */
    public function getImageMeta($filename, $dirname) {
        $upload = new cApiUpload();
        $upload->loadByMany(array(
            'filename' => $filename,
            'dirname' => $dirname,
            'idclient' => $this->_client
        ), false);
        $idupl = $upload->get('idupl');

        $uploadMeta = new cApiUploadMeta();
        $uploadMeta->loadByMany(array(
            'idupl' => $idupl,
            'idlang' => $this->_lang
        ));

        $imageMeta = array();
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
     * @param string $path Path to directory to create, either path from client
     *        upload
     *        directory or a dbfs path
     * @param string $name Name of directory to create
     * @return string void value of filemode as string ('0702') or nothing
     */
    public function uplmkdir($path, $name) {
        return uplmkdir($path, $name);
    }

    /**
     * Uploads the transmitted files saved in the $_FILES array.
     *
     * @param string $path the path to which the file should be uploaded
     * @return string the filename of the uploaded file
     */
    public function uplupload($path) {
        if (count($_FILES) === 1) {
            foreach ($_FILES['file']['name'] as $key => $value) {
                if (file_exists($_FILES['file']['tmp_name'][$key])) {
                    $friendlyName = uplCreateFriendlyName($_FILES['file']['name'][$key]);
                    move_uploaded_file($_FILES['file']['tmp_name'][$key], $this->_cfgClient[$this->_client]['upl']['path'] . $path . $friendlyName);

                    cDebug::out(":::" . $path);
                    uplSyncDirectory($path);

                    $upload = new cApiUpload();
                    $upload->loadByMany(array(
                        'dirname' => $path,
                        'filename' => $_FILES['file']['name'][$key]
                    ), false);
                    if ($upload->get('idupl') != false) {
                        $uplfilename = $this->_cfgClient[$this->_client]['upl']['htmlpath'] . $upload->get('dirname') . $upload->get('filename');
                    } else {
                        $uplfilename = 'error';
                    }
                }
            }
        }

        return $uplfilename;
    }

}