<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class for handling CMS Type Image
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Content Types
 * @version    1.0.0
 * @author     Fulai Zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.13
 *
 * {@internal
 *   created 2009-10-26
 *   $Id: class.cms_image.php 2422 2012-06-27 00:25:38Z xmurrix $:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.con.php');
cInclude("includes", "functions.upl.php");

/**
 * This class provides all methods for the content type CMS_IMAGE. All properties of the file list are
 * stored as a xml document in the database.
 */
class Cms_Image {

    /**
     * CONTENIDO configuration array
     * @var     array
     */
    private $aCfg = array();

    /**
     * Current id of content type CMS_IMAGE[3] -> 3
     * @var     integer
     */
    private $iId = 0;

    /**
     * CONTENIDO database object
     * @var     object
     */
    private $oDb = null;

    /**
     * Idartlang of article, which is currently in edit- or viewmode
     * @var     integer
     */
    private $iIdArtLang = 0;

    /**
     * List of fieldnames in frontend (properties) which the file list has
     * and which were also described in the config xml document
     * @var     array
     */
    private $aImageData = array();

    /**
     * String contains value of stored content in database
     * in this case this is the config xml document which is
     * later parsed and its settings were stored in $aSettings
     * @var        string
     */
    private $sContent = "";

    /**
     * Array which contains current file list settings
     * @var     array
     */
    private $aSettings = array();

    /**
     * Current CONTENIDO client id
     * @var     integer
     */
    private $iClient = 0;

    /**
     * Current CONTENIDO language id
     * @var     integer
     */
    private $iLang = 0;

    /**
     * CONTENIDO session object
     * @var     object
     */
    private $oSess = null;

    /**
     * CONTENIDO configuration array for current active client
     * @var     array
     */
    private $aCfgClient = array();

    /**
     * CONTENIDO upload path for current client
     * @var        string
     */
    private $sUploadPath = "";

    /**
     * Constructor of class inits some important class variables and
     * gets some CONTENIDO global vars, so this class has no need to
     * use ugly and buggy global commands
     *
     * @param string $sContent - xml document from database containing the settings
     * @param integer $iNumberOfCms - CMS_IMAGE[4] => 4
     * @param integer $iIdArtLang - Idartlang of current article
     * @param array $sEditLink - sEditlink for editbuttons, not currently used
     * @param array $aCfg - CONTENIDO configuration array
     * @param array $oDB - CONTENIDO database object
     * @param string $sContenidoLang - CONTENIDO Backend language string
     * @param integer $iClient - CONTENIDO client id
     * @param integer $iLang - CONTENIDO frontend language id
     * @param array $aCfgClient - CONTENIDO Client configuration array
     * @param object $oSess - CONTENIDO session object
     */
    public function __construct($sContent, $iNumberOfCms, $iIdArtLang, $sEditLink, $aCfg, $oDB, $sContenidoLang, $iClient, $iLang, $aCfgClient, $oSess, $idart = -1, $idcat = -1) {
        //set arguments to class variables directly
        $this->aCfg = $aCfg;
        $this->iId = $iNumberOfCms;
        $this->iIdArtLang = $iIdArtLang;
        $this->sContent = $sContent;
        $this->iClient = $iClient;
        $this->iLang = $iLang;
        $this->aCfgClient = $aCfgClient;
        $this->oSess = $oSess;

        //init other variables with default values
        $this->oDb = cRegistry::getDb();
        $this->sUploadPath = $this->aCfgClient[$this->iClient]['upl']['path'];

        //define class array which contains all names of the image properties. They were also base for generating dynamic javascripts for
        //retrival this properties out of html forms and retriving their values to screen
        $this->aImageData = array('image_filename', 'image_medianame', 'image_description', 'image_keywords', 'image_internal_notice', 'image_copyright');

        //if form is submitted there is a need to store current file list settings
        //notice: there is also a need, that filelist_id is the same (case: more than one cms file list is used on the same page
        if (isset($_POST['image_action']) && $_POST['image_action'] == 'store' && isset($_POST['image_id']) && (int) $_POST['image_id'] == $this->iId) {
            $this->storeImage($idart);

            if (isset($_REQUEST['area']) && $_REQUEST['area'] == 'con_content_list') {
                $path = $this->aCfg['path']['contenido_fullhtml'] . "main.php?area=con_content_list&action=con_content&changeview=edit&idart=$idart&idartlang=$iIdArtLang&idcat=$idcat&client=$iClient&lang=$iLang&frame=4&contenido=" . $_REQUEST['contenido'];
            } else {
                $path = $this->aCfg['path']['contenido_fullhtml'] . "external/backendedit/front_content.php?area=con_editcontent&idart=$idart&idcat=$idcat&changeview=edit&client=$this->iClient";
            }
            header('location:' . $this->oSess->url($path));
        }

        //in sContent XML Document is stored, which contains files settings, call function which parses this document and store
        //properties as easy accessible array into $aSettings
    }

    /**
     * Function gets all submitted values for new file list properties from
     * $_POST array, generates new corresponding config XML Document and
     * stores it as content, using CONTENIDO conSaveContentEntry() function
     *
     * @return    void
     */
    private function storeImage($idart = -1) {
        global $auth;
        $aFilenameData['filename'] = basename($_REQUEST['image_filename']);
        $aFilenameData['dirname'] = dirname($_REQUEST['image_filename']);

        //if one pictures selected from upload directory
        if ($aFilenameData['dirname'] == "\\" || $aFilenameData['dirname'] == '/' || $aFilenameData['dirname'] == '.') {
            $aFilenameData['dirname'] = '';
        } else {
            $aFilenameData['dirname'] .= '/';
        }
        $query = 'SELECT idupl FROM ' . $this->aCfg['tab']['upl'] . ' WHERE filename=\'' . $aFilenameData['filename'] . '\' AND dirname=\'' . $aFilenameData['dirname'] . '\' AND idclient=\'' . $this->iClient . '\'';

        $this->oDb->query($query);
        if ($this->oDb->next_record()) {
            $this->iUplId = $this->oDb->f('idupl');
        }
        $this->sContent = $this->iUplId;
        conSaveContentEntry($this->iIdArtLang, 'CMS_IMAGE', $this->iId, $this->iUplId, true);
        conSaveContentEntry($this->iIdArtLang, 'CMS_IMG', $this->iId, $this->iUplId, true);
        if ($idart != -1) {
            conMakeArticleIndex($this->iIdArtLang, $idart);
            conGenerateCodeForArtInAllCategories($idart);
        }
        //Insert auf metadatentabelle
        $idupl = $this->iUplId;
        $idlang = $this->iLang;
        $medianame = $_REQUEST['image_medianame'];
        $description = $_REQUEST['image_description'];
        $keywords = $_REQUEST['image_keywords'];
        $internal_notice = $_REQUEST['image_internal_notice'];
        $copyright = $_REQUEST['image_copyright'];
        $query = "SELECT id_uplmeta FROM " . $this->aCfg['tab']['upl_meta'] . " WHERE idupl='" . $idupl . "' AND idlang='" . $idlang . "'";
        $this->oDb->query($query);
        //echo '1'.$this->oDb->Error;
        if ($this->oDb->next_record()) {
            $id_uplmeta = $this->oDb->f('id_uplmeta');
        }
        $time = date("Y-m-d H:i:s");
        if (!isset($id_uplmeta)) {
            //$id = $this->oDb->nextid($this->aCfg['tab']['upl_meta']);
            $query = "INSERT INTO " . $this->aCfg['tab']['upl_meta'] . "( idupl, idlang, medianame, description, keywords, internal_notice, author, created, modified, modifiedby, copyright)
            VALUES('" . $idupl . "', '" . $idlang . "', '" . $medianame . "', '" . $description . "', '" . $keywords . "', '" . $internal_notice . "', '" . $auth->auth['uid'] . "', '" . $time . "', '" . $time . "', '" . $auth->auth['uid'] . "', '" . $copyright . "')";
            $this->oDb->query($query);
            //echo '2'.$this->oDb->Error;
        } else {
            $query = "UPDATE " . $this->aCfg['tab']['upl_meta'] .
                    " SET idupl='" . $idupl . "', idlang='" . $idlang . "', medianame='" . $medianame . "', description='" . $description . "', keywords='" . $keywords . "', internal_notice='" . $internal_notice . "', modified='" . $time . "', modifiedby='" . $auth->auth['uid'] . "', copyright='" . $copyright . "'
                    WHERE id_uplmeta='" . $id_uplmeta . "'";
            $this->oDb->query($query);
            //echo '3'.$this->oDb->Error;
        }
    }

    /**
     * Function which generate a select box for the manual files.
     *
     * @param     array     $sDirectoryPath    Path to directory of the files
     * @return     string    rendered cHTMLSelectElement
     */
    public function getFileSelect($sDirectoryPath = "", $iImageId = "") {
        $oHtmlSelect = new cHTMLSelectElement('image_filename', "", 'image_filename_' . $iImageId);
        $oHtmlSelect->setSize(16);

        $oHtmlSelectOption = new cHTMLOptionElement('Kein', '', false);
        $oHtmlSelect->addOptionElement(0, $oHtmlSelectOption);

        $i = 1;
        //if ($sDirectoryPath != "" && $sDirectoryPath!='upload') {
        $sUploadPath = $this->aCfgClient[$this->iClient]['upl']['path'];
        $oHandle = opendir($sUploadPath . $sDirectoryPath);
        while ($sEntry = readdir($oHandle)) {
            if ($sEntry != "." && $sEntry != ".." && cFileHandler::exists($sUploadPath . $sDirectoryPath . "/" . $sEntry) && !is_dir($sUploadPath . $sDirectoryPath . "/" . $sEntry)) {
                if ($sDirectoryPath == '/' || $sDirectoryPath == '') {
                    $oHtmlSelectOption = new cHTMLOptionElement($sEntry, $sEntry);
                } else {
                    $oHtmlSelectOption = new cHTMLOptionElement($sEntry, $sDirectoryPath . "/" . $sEntry);
                }
                $oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
                $i++;
            }
        }
        closedir($oHandle);
        //}

        if ($i == 0) {
            $oHtmlSelectOption = new cHTMLOptionElement(i18n('No files found'), '', false);
            $oHtmlSelectOption->setAlt(i18n('No files found'));
            $oHtmlSelectOption->setDisabled(true);
            $oHtmlSelect->addOptionElement($i, $oHtmlSelectOption);
            $oHtmlSelect->setDisabled(true);
        }
        //set default value
        if (isset($this->filename)) {
            if (isset($this->activeFilename)) {
                $oHtmlSelect->setDefault($this->activeFilename . "/" . $this->filename);
            } else {
                $oHtmlSelect->setDefault($this->filename);
            }
        } else {
            $oHtmlSelect->setDefault('');
        }


        return $oHtmlSelect->render();
    }

    /**
     * Returns the directory list of an given directory array (by buildDirectoryList()).
     *
     * @param     array     $aDirs    Array with directory information
     * @return    string    html of the directory list
     */
    public function getDirectoryList($aDirs) {
        $oTpl = new Template();
        $i = 1;
        foreach ($aDirs as $aDirData) {
            $sRelativePath = str_replace($this->sUploadPath, '', $aDirData['path']) . $aDirData['name'];
            $sLiClasses = '';
            if ($sRelativePath . "/" == $this->dirname) {
                $this->activeFilename = $sRelativePath;
                $oTpl->set('d', 'DIVCLASS', ' class="active"');
            } else {
                $oTpl->set('d', 'DIVCLASS', '');
            }
            $oTpl->set('d', 'TITLE', $sRelativePath);
            $oTpl->set('d', 'DIRNAME', $aDirData['name']);

            $bGo = false;
            if (isset($this->dirname)) {
                $this->image_directories = explode('/', $this->dirname);
                if ($this->fileIsOrNotInPath($sRelativePath)) {
                    $bGo = true;
                }
            }
            if ($bGo == true) {
                $oTpl->set('d', 'SUBDIRLIST', $this->getDirectoryList($aDirData['sub']));
            } else if (isset($aDirData['sub']) && count($aDirData['sub']) > 0) {
                $sLiClasses .= " collapsed";
                $oTpl->set('d', 'SUBDIRLIST', '');
            } else {
                $oTpl->set('d', 'SUBDIRLIST', '');
            }

            if ($i == count($aDirs)) {
                $sLiClasses .= " last";
            }

            if ($sLiClasses != "") {
                $oTpl->set('d', 'LICLASS', ' class="' . substr($sLiClasses, 1) . '"');
            } else {
                $oTpl->set('d', 'LICLASS', '');
            }

            $i++;
            $oTpl->next();
        }

        return $oTpl->generate($this->aCfg['path']['contenido'] . 'templates/standard/template.cms_filelist_dirlistitem.html', 1);
    }

    private function fileIsOrNotInPath($activeFile) {
        $aLevelPath = explode('/', $this->dirname);
        $error = false;
        foreach ($aLevelPath as $levelPath) {
            $sLevelPath .= '/' . $levelPath;
            if ($sLevelPath == '/' . $activeFile) {
                $error = true;
            }
        }
        return $error;
    }

    /**
     * Builds a directory list by a given upload directory path.
     *
     * @param     string     $sUploadPath    Path to directory (per default the root upload path of client)
     * @return    array    Array with directory information
     */
    public function buildDirectoryList($sUploadPath = "") {
        if ($sUploadPath == "") {
            $sUploadPath = $this->sUploadPath;
        }

        if (substr($sUploadPath, -1) != "/") {
            $sUploadPath = $sUploadPath . "/";
        }

        $aDirectories = array();
        $oHandle = opendir($sUploadPath);
        $i = 0;
        while ($sEntry = readdir($oHandle)) {
            if ($sEntry != ".svn" && $sEntry != "." && $sEntry != ".." && is_dir($sUploadPath . $sEntry)) {
                $aDirectories[$i]['name'] = $sEntry;
                $aDirectories[$i]['path'] = $sUploadPath;
                $aDirectories[$i]['sub'] = $this->buildDirectoryList($sUploadPath . $sEntry);
                $i++;
            }
        }
        closedir($oHandle);
        return $aDirectories;
    }

    /**
     * Function is called in editmode of CONTENIDO an returns image view and editbutton
     *
     * @return    string    code for the backend edit view
     */
    public function getAllWidgetEdit() {
        $oTpl = new Template();
        //set meta
        /* Set some values into javascript for a better handling */
        $oTpl->set('s', 'CON_PATH', $this->aCfg['path']['contenido_fullhtml']);
        $oTpl->set('s', 'ID', $this->iId);

        $oTpl->set('s', 'IDARTLANG', $this->iIdArtLang);
        $oTpl->set('s', 'CONTENIDO', $_REQUEST['contenido']);
        $oTpl->set('s', 'FIELDS', "'" . implode("','", $this->aImageData) . "'");

        /* Start set a lot of translations */
        $oTpl->set('s', 'LABEL_IMAGE_SETTINGS', i18n("Image settings"));

        $oTpl->set('s', 'DIRECTORIES', i18n("Directories"));
        $oTpl->set('s', 'META', i18n("Meta"));
        $oTpl->set('s', 'UPLOAD', i18n("Upload"));

        $oTpl->set('s', 'META_URL_TEXT', i18n("Selected file"));
        $oTpl->set('s', 'LABEL_IMAGE_TITLE', i18n("Title"));
        $oTpl->set('s', 'LABEL_IMAGE_DESC', i18n("Description"));
        $oTpl->set('s', 'LABEL_IMAGE_KEYWORDS', i18n("Keywords"));
        $oTpl->set('s', 'LABEL_IMAGE_INTERNAL_NOTICE', i18n("Internal notes"));
        $oTpl->set('s', 'LABEL_IMAGE_COPYRIGHT', i18n("Copyright"));

        $oTpl->set('s', 'INDEX', i18n("Create a directory in"));
        $oTpl->set('s', 'PFAD', i18n("Path"));
        $oTpl->set('s', 'CONTENIDO', $contenido);

        $oTpl->set('s', 'sUploadPath', $this->sUploadPath);

        $idupl = $this->sContent;
        $idlang = $this->iLang;
        $this->oDb->query('SELECT filename,dirname FROM ' . $this->aCfg['tab']['upl'] . ' WHERE idupl=\'' . $idupl . '\' AND idclient=\'' . $this->iClient . '\'');
        if ($this->oDb->next_record()) {
            $this->filename = $this->oDb->f('filename');
            $this->dirname = $this->oDb->f('dirname');
        }
        $filename_src = $this->aCfgClient[$this->iClient]['upl']['htmlpath'] . $this->dirname . $this->filename;
        $filename = str_replace($this->aCfgClient[$this->iClient]['path']['htmlpath'], $this->aCfgClient[$this->iClient]['path']['frontend'], $filename_src);
        $filetype = substr($filename, strlen($filename) - 4, 4);
        switch (strtolower($filetype)) {
            case ".gif": $sString = cApiImgScale($filename, 428, 210);
                break;
            case ".png": $sString = cApiImgScale($filename, 428, 210);
                break;
            case ".jpg": $sString = cApiImgScale($filename, 428, 210);
                break;
            case "jpeg": $sString = cApiImgScale($filename, 428, 210);
                break;
            default: $sString = $filename_src;
                break;
        }
        //if can not scale, so $sString is null, then show the original image.
        if ($sString == '') {
            $sString = $filename_src;
        }
        $oTpl->set('s', 'DIRECTORY_SRC', $sString);
        $oTpl->set('s', 'sContent', $this->aCfgClient[$this->iClient]['upl']['htmlpath'] . $this->dirname . $this->filename);
        $oTpl->set('s', 'DIRECTORY_LIST', $this->getDirectoryList($this->buildDirectoryList()));

        $query = "SELECT * FROM " . $this->aCfg['tab']['upl_meta'] . " WHERE idupl='" . $idupl . "' AND idlang='" . $idlang . "'";
        $this->oDb->query($query);

        if ($this->oDb->next_record() && $idupl != '') {
            $id_uplmeta = $this->oDb->f('id_uplmeta');

            $oTpl->set('s', 'DIRECTORY_FILE', $this->getFileSelect($this->activeFilename, $this->iId));
            $oTpl->set('s', 'IMAGE_TITLE', $this->oDb->f('medianame'));
            $oTpl->set('s', 'IMAGE_DESC', $this->oDb->f('description'));
            $oTpl->set('s', 'IMAGE_KEYWORDS', $this->oDb->f('keywords'));
            $oTpl->set('s', 'IMAGE_INTERNAL_NOTICE', $this->oDb->f('internal_notice'));
            $oTpl->set('s', 'IMAGE_COPYRIGHT', $this->oDb->f('copyright'));
        } else {
            $oTpl->set('s', 'DIRECTORY_FILE', $this->getFileSelect($this->activeFilename, $this->iId));
            $oTpl->set('s', 'IMAGE_TITLE', '');
            $oTpl->set('s', 'IMAGE_DESC', '');
            $oTpl->set('s', 'IMAGE_KEYWORDS', '');
            $oTpl->set('s', 'IMAGE_INTERNAL_NOTICE', '');
            $oTpl->set('s', 'IMAGE_COPYRIGHT', '');
        }

        //generate template
        $sCode .= $oTpl->generate($this->aCfg['path']['contenido'] . 'templates/standard/template.cms_image_edit.html', 1);

        return $this->encodeForOutput($sCode);
    }

    /**
     * In CONTENIDO content type code is evaled by php. To make this possible,
     * this function prepares code for evaluation
     *
     * @param     string     $sCode     code to escape
     * @return     string    escaped code
     */
    private function encodeForOutput($sCode) {
        $sCode = (string) $sCode;

        $sCode = addslashes($sCode);
        $sCode = str_replace("\\'", "'", $sCode);
        $sCode = str_replace("\$", '\\$', $sCode);

        return $sCode;
    }

    /**
     * Function is called in edit- and viewmode in order to generate code for output.
     *
     * @return    string    generated code
     */
    public function getAllWidgetView() {
        $oTpl = new Template();
        $sCode = '';
        //select metadaten
        $this->oDb->query('SELECT * FROM ' . $this->aCfg['tab']['upl'] . ' WHERE idupl=\'' . $this->sContent . '\' AND idclient=\'' . $this->iClient . '\'');
        if ($this->oDb->next_record()) {
            //set title of teaser
            $oTpl->set('s', 'TITLE', $this->oDb->f('filename'));
            if ($this->oDb->f('dirname') != '' && $this->oDb->f('filename') != '') {
                $oTpl->set('s', 'SRC', $this->aCfgClient[$this->iClient]['upl']['htmlpath'] . $this->oDb->f('dirname') . $this->oDb->f('filename'));
                $sCode = $this->aCfgClient[$this->iClient]['upl']['htmlpath'] . $this->oDb->f('dirname') . $this->oDb->f('filename');
            } else {
                $oTpl->set('s', 'SRC', '');
                $sCode = "";
            }
            $oTpl->set('s', 'DESC', $this->oDb->f('filename'));

            //generate template
            //$sCode = $oTpl->generate($this->aCfgClient[$this->iClient]['path']['frontend'].'templates/cms_image_style_default.html', 1);
        }
        return $sCode;
    }

    public function getImageMeta($filename, $dirname, $iImageId) {
        $query = "SELECT * FROM " . $this->aCfg['tab']['upl'] . " a," . $this->aCfg['tab']['upl_meta'] . " b WHERE a.filename='" . $filename . "' AND a.dirname='" . $dirname . "' AND a.idclient=" . $this->iClient . " AND a.idupl=b.idupl AND b.idlang=" . $this->iLang;
        $this->oDb->query($query);
        $array = array();
        if ($this->oDb->next_record()) {
            echo $array[$iImageId]['medianame'] = $this->oDb->f('medianame');
            echo '+++';
            echo $array[$iImageId]['description'] = $this->oDb->f('description');
            echo '+++';
            echo $array[$iImageId]['keywords'] = $this->oDb->f('keywords');
            echo '+++';
            echo $array[$iImageId]['internal_notice'] = $this->oDb->f('internal_notice');
            echo '+++';
            echo $array[$iImageId]['copyright'] = $this->oDb->f('copyright');
            echo '+++';
        } else {
            echo '';
            echo '+++';
            echo '';
            echo '+++';
            echo '';
            echo '+++';
            echo '';
            echo '+++';
            echo '';
            echo '+++';
        }
    }

    public function uplmkdir($sPath, $sName) {
        return uplmkdir($sPath, $sName);
    }

    public function uplupload($sPath) {
        global $cfgClient, $client;

        $uplfilename = 'error';
        $rootpath = $this->aCfgClient[$this->iClient]['upl']['htmlpath'];

        if (count($_FILES) == 1) {
            foreach ($_FILES['file']['name'] as $key => $value) {
                if (cFileHandler::exists($_FILES['file']['tmp_name'][$key])) {
                    $friendlyName = uplCreateFriendlyName($_FILES['file']['name'][$key]);
                    move_uploaded_file($_FILES['file']['tmp_name'][$key], $cfgClient[$client]['upl']['path'] . $sPath . $friendlyName);

                    uplSyncDirectory($sPath);

                    $sql = "SELECT * FROM " . $this->aCfg["tab"]["upl"] . " WHERE dirname='" . $sPath . "' AND filename='" . $_FILES['file']['name'][$key] . "'";
                    $this->oDb->query($sql);
                    if ($this->oDb->next_record()) {
                        $uplfilename = $rootpath . $this->oDb->f('dirname') . $this->oDb->f('filename');
                    } else {
                        $uplfilename = 'error';
                    }
                }
            }
        }
        return $uplfilename;
    }

}

?>