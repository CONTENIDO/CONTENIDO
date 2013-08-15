<?PHP

/**
 * description: content_download_manager
 *
 * The download manager is based on the con_upl and the con_upl_meta tables. Con_upl contains only informations
 * about existing files. For a complete nagivation structure through this files it is necessary to generate virtual
 * directories based on the path informations stored in con_upl.
 *

 *
 *
 * @package Module
 * @subpackage content_download_manager
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');
cInclude('classes', 'class.cziparchive.php');
cInclude('frontend', 'classes/class.sort_dl_manager.php');

$idClient = cRegistry::getClientId();
$uplPath = $cfgClient[cRegistry::getClientId()]['path']['frontend'] . 'upload/';
$path = $cfgClient[cRegistry::getClientId()]['htmlpath']['frontend'];

// check for download request
if (isset($_GET['files'])) {
    $param = '';
    foreach ($_GET['files'] as $key => $item) {

        if ($key == 0) {
            $param .= $item;
        } else {
            $param .= '&files[]=' . $item;
        }
    }
    // call firesecurity article to download file
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $path . "?idart=83&files[]=" . $param);
}


$uplContent = array();
$dirPath = array();
$folders = array();

// build directories
$dB = cRegistry::getDb();
$sql = 'SELECT * FROM con_upl WHERE dirname!="";';
$dB->query($sql);

while ($dB->next_record()) {

    // create directory object
    $dirName = $dB->f('dirname');
    $dir = new UplDir();
    $dir->setCurrFilePath($dirName);
    $dir->setType('Ordner');
    $dir->setHierarchie($dirName);
    $dir->setFolderDir($dirName);

    // push directory if not exists
    if (count($dirPath) == 0 || !isInArray($dirPath, $dirName)) {
        $uplContent[] = $dir;
        $dirPath[] = $dirName;
        $folders[] = $dir;
    }
}

// build virtual parent directories
foreach ($dirPath as $key => $folder) {

    $tmpFolder = $folder;
    $strCountSlashes = substr_count($folder, '/');

    if ($strCountSlashes > 1) {

        for ($i = 0; $i < $strCountSlashes - 1; $i++) {
            $WithoutLastSlash = substr($tmpFolder, 0, strlen($tmpFolder) - 1);
            // last path element
            $tmpFolder = substr($tmpFolder, 0, strrpos($WithoutLastSlash, '/') + 1);

            if (strlen($tmpFolder) > 0) {

                // if directory does not exists -> build it.
                if (!isInArray($dirPath, $tmpFolder)) {

                    $dirName = $tmpFolder;
                    $dir = new UplDir();
                    $dir->setCurrFilePath($dirName);
                    $dir->setType('Ordner');
                    $dir->setHierarchie($dirName);
                    $dir->setFolderDir($dirName);

                    if (count($dirPath) == 0 || !isInArray($dirPath, $dirName)) {
                        $uplContent[] = $dir;
                        $dirPath[] = $dirName;
                        $folders[] = $dir;
                    }
                }
            }
        }
    }
}

// build files
$sql = 'SELECT * FROM con_upl;';

$dB->query($sql);

$uplMeta = new cApiUploadMetaCollection();

while ($dB->next_record()) {

    $fileName = $dB->f('filename');
    $dir = new UplFile();
    $dir->setCurrFilePath($fileName);
    $dir->setType($dB->f('filetype'));
    $dir->setDlLink($dB->f('dirname') . $fileName);
    $dir->setDate($dB->f('created'));
    $dir->setSize(round(($dB->f('size') / 1000), 0) . ' KB');
    $dir->setHierarchie($dB->f('dirname'));
    $dir->setFolder($dB->f('dirname'));

    // meta information
    $des = $uplMeta->getFieldsByWhereClause(array(
        'description'
    ), 'idupl=' . $dB->f('idupl'));
    $dir->setDescription($des[0]['description']);

    $uplContent[] = $dir;
}

$variable = getRootDir();
$hierarchie = substr_count($variable, '/');

if (isset($_GET['file']) && isset($_GET['hierarchie'])) {

    // security => block reaching parent directories from dlmanager rootpath
    // with manipulation
    // of $_GET parameters.
    // if (substr_count($_GET['file'], $variable, 0) != 0) {
    // var_dump(substr('download/test',0,strpos('download/test', '/', 0)));

    $hierarchie = $_GET['hierarchie'];
    $variable = $_GET['file'];
    // } else {
    // $variable = getEffectiveSetting('dlmanager', rootpath, '');
    // $hierarchie = substr_count($variable, '/');
    // }
}

$test = $variable;

if (substr_count($test, '/') == 1 || substr_count($test, '/') == 0) {
    $sub = '';
} else {

    $testii = substr($test, 0, strlen($test) - 1);
    $sub = substr($test, 0, strrpos($testii, '/') + 1);
}

if ($variable != '') {
    $cUrl = cUri::getInstance();

    $url = $cUrl->build(array(
        'idart' => 85,
        'lang' => cRegistry::getLanguageId(),
        'file' => $sub,
        'hierarchie' => $hierarchie - 1
    ));
    $linkUp = '<a href="' . $url . '">' . 'Ebene höher' . '</a> ';
}


if (isset($_REQUEST['dirlist'])) {

    conSaveContentEntry(cRegistry::getArticleLanguageId(), "CMS_HTML", "66", cSecurity::escapeString($_REQUEST['dirlist']));
    $variable = getRootDir();
}
$idClient = cRegistry::getClientId();

// display backend configuration settings
if (cRegistry::isBackendEditMode()) {
    $uplPath = $cfgClient[cRegistry::getClientId()]['path']['htmlpath'];

    echo '<form action="' . $uplPath . 'deutsch/downloadbereich/index.html" method="">';
    echo '<select name="dirlist" id="dirlist">';
    foreach ($dirPath as $key => $dir) {
        echo '<option value="' . $dir . '">' . $dir . '</option>';
    }
    echo '</select>';
    echo '<button type="submit">Änderungen übernehmen</button>';
    echo '</form>';
    echo 'Aktueller Pfad: <br />';
    echo strip_tags("CMS_HTML[66]");
}

$container = array();
foreach ($uplContent as $key => $val) {
    // var_dump($variable);

    if ((!$val->isDir() && $val->getFolder() == $variable) || ($val->isDir() && $val->getHierarchie() == $hierarchie && $val->getFolder() == $variable)) {
        // && $val->getHierarchie() == $hierarchie ) {

        if ($val->isDir()) {

            $cUrl = cUri::getInstance();
            $url = $cUrl->build(array(
                'idart' => 85,
                'lang' => cRegistry::getLanguageId(),
                'file' => $val->getCurrFilePath(),
                'hierarchie' => $hierarchie + 1
            ));

            $val->setNavLink($url);
        } else {
            $val->setNavLink(NULL);
        }

        $val->setName($val->getCurrFilePath());
        $urlDownloadLink = $cfgClient[$client]['path']['htmlpath'] . 'upload/' . $variable . $val->getCurrFilePath();

        $val->setDownloadLink($urlDownloadLink);
        $val->setDownloadSecurity($path . '?idart=83&file=' . $variable . $val->getCurrFilePath());

        $conti[] = $val;
    }
}

// build sort links for user interaction
$sortLinkNameAsc = $cfgClient[$client]['path']['htmlpath'] . 'deutsch/downloadbereich/index.html?sort=name&style=asc';
$sortLinkNameDsc = $cfgClient[$client]['path']['htmlpath'] . 'deutsch/downloadbereich/index.html?sort=name&style=des';

$sortLinkDescriptionAsc = $cfgClient[$client]['path']['htmlpath'] . 'deutsch/downloadbereich/index.html?sort=description&style=asc';
$sortLinkDescriptionDsc = $cfgClient[$client]['path']['htmlpath'] . 'deutsch/downloadbereich/index.html?sort=description&style=des';

$sortLinkTypeAsc = $cfgClient[$client]['path']['htmlpath'] . 'deutsch/downloadbereich/index.html?sort=type&style=asc';
$sortLinkTypeDsc = $cfgClient[$client]['path']['htmlpath'] . 'deutsch/downloadbereich/index.html?sort=type&style=des';

$sortLinkDateAsc = $cfgClient[$client]['path']['htmlpath'] . 'deutsch/downloadbereich/index.html?sort=date&style=asc';
$sortLinkDateDsc = $cfgClient[$client]['path']['htmlpath'] . 'deutsch/downloadbereich/index.html?sort=date&style=des';

$sortLinkSizeAsc = $cfgClient[$client]['path']['htmlpath'] . 'deutsch/downloadbereich/index.html?sort=size&style=asc';
$sortLinkSizeDsc = $cfgClient[$client]['path']['htmlpath'] . 'deutsch/downloadbereich/index.html?sort=size&style=des';

// check for user interactions
if ((isset($_GET['sort']) && isset($_GET['sort']) != NULL) && (isset($_GET['style']) && isset($_GET['style']) != NULL)) {

    // select witch value should be sorted.
    switch ($_GET['sort']) {
        case 'name':
            {
                $conti = SortDlManager::sortName($_GET['style'], $conti);
                break;
            }
        case 'size':
            {
                $conti = SortDlManager::sortSize($_GET['style'], $conti);
                break;
            }
        case 'date':
            {
                $conti = SortDlManager::sortDate($_GET['style'], $conti);
                break;
            }
        case 'description':
            {
                $conti = SortDlManager::sortDescription($_GET['style'], $conti);
                break;
            }
        case 'type':
            {
                $conti = SortDlManager::sortType($_GET['style'], $conti);
                break;
            }
        default:
            {
                throw new Exception('Function not implemented');
            }
    }
}

function getRootDir() {
    $idartLang = cRegistry::getArticleLanguageId();
    $col = new cApiArticleLanguage($idartLang);
    return strip_tags($col->getContent("CMS_HTML", 66));
}

function isInArray(array $ar, $value) {
    foreach ($ar as $key => $index) {
        if ($index === $value) {
            return true;
        }
    }
    return false;
}

/**
 * Read file bytes
 *
 * @param string $filename
 * @return boolean
 */
function readfile_chunked($filename) {
    $chunksize = 1 * (1024 * 1024); // how many bytes per chunk
    $buffer = '';
    $handle = fopen($filename, 'rb');
    if ($handle === false) {
        return false;
    }
    while (!feof($handle)) {
        $buffer = fread($handle, $chunksize);
        print $buffer;
        ob_flush();
        flush();
    }
    return fclose($handle);
}

$breadcrumb = new UplPathBreadcrumb($variable);

// template
$tpl = cSmartyFrontend::getInstance();
$client = cRegistry::getClientId();
$tpl->assign('path', $cfgClient[$client]['path']['htmlpath']);
$tpl->assign('uplPath', $cfgClient[cRegistry::getClientId()]['upl']['htmlpath']);
$tpl->assign('breadcrumb', $breadcrumb->getContent());
$tpl->assign('linkup', $linkUp);
$tpl->assign('location', $variable);
$tpl->assign('uplContent', $conti);
$tpl->assign('container', $container);
$tpl->assign('label', 'sdf');
$tpl->assign('sortLinkNameAsc', $sortLinkNameAsc);
$tpl->assign('sortLinkNameDsc', $sortLinkNameDsc);
$tpl->assign('sortLinkDescriptionAsc', $sortLinkDescriptionAsc);
$tpl->assign('sortLinkDescriptionDsc', $sortLinkDescriptionDsc);
$tpl->assign('sortLinkTypeAsc', $sortLinkTypeAsc);
$tpl->assign('sortLinkTypeDsc', $sortLinkTypeDsc);
$tpl->assign('sortLinkDateDsc', $sortLinkDateDsc);
$tpl->assign('sortLinkDateAsc', $sortLinkDateAsc);
$tpl->assign('sortLinkNameAsc', $sortLinkNameAsc);
$tpl->assign('sortLinkSizeAsc', $sortLinkSizeAsc);
$tpl->assign('sortLinkSizeDsc', $sortLinkSizeDsc);

$tpl->display('get.tpl');

/**
 * This class represent elements of a breadcrumb navigation.
 *
 * @author claus.schunk
 *
 */
class uplBreadcrumbItem {

    private $_link;

    private $_text;

    public function __construct($link = NULL, $text = NULL) {
        $this->_link = $link;
        $this->_text = $text;
    }

    public function getLink() {
        return $this->_link;
    }

    public function getText() {
        return $this->_text;
    }

}

/**
 * This class is used to build a breadcrumb navigation.
 *
 * @author claus.schunk
 *
 */
class UplPathBreadcrumb {

    private $_content = array();

    private $_maxHierarchie = NULL;

    public function getContent() {
        return $this->_content;
    }

    public function __construct($str) {
        $this->_maxHierarchie = $this->setHierarchie($str);

        $this->generate($str);
    }

    public function setHierarchie($str) {
        if (substr_count($str, '/') == 0) {
            $this->_hierachie = 0;
        } else {
            $this->_hierachie = substr_count($str, '/') - 1;
        }
    }

    public function getLink() {
        return $this->_link;
    }

    public function getText() {
        return $this->_text;
    }

    public function Hierarchie() {
        return $this->_hierarchie;
    }

    public function generate($str) {
        $hierachie = 1;
        $strCountSlashes = substr_count($str, '/');
        $ar = array();

        for ($i = 0; $i < $strCountSlashes; $i++) {
            $file = '';

            $pos = strpos($str, '/');
            $tmp = substr($str, 0, $pos + 1);
            $ar[] = $tmp;

            $str = substr($str, strlen($tmp), strlen($str));

            foreach ($ar as $key => $path) {

                $file .= $path;
            }

            $cUrl = cUri::getInstance();
            $url = $cUrl->build(array(
                'idart' => 85,
                'lang' => cRegistry::getLanguageId(),
                'file' => $file,
                'hierarchie' => $hierachie
            ));

            $this->_content[] = new uplBreadcrumbItem($url, $tmp);

            $hierachie++;
        }
    }

}
/**
 * This class is used to store informations about files and directories created from the content of
 * the conUpl table.
 *
 * @author claus.schunk
 *
 */
class uplContent {

    private $_idupl;

    private $_currFilePath;

    private $_type;

    private $_date;

    private $_hierachie;

    private $_folder;

    private $_name;

    private $_dlLink;

    private $_link;

    private $_size;

    private $_navLink;

    private $_description;

    private $_downloadLink;

    private $_downloadSecurity;

    public function setDownloadSecurity($sec) {
        $this->_downloadSecurity = $sec;
    }

    public function getDownloadSecurity() {
        return $this->_downloadSecurity;
    }

    public function setDownloadLink($link) {
        $this->_downloadLink = $link;
    }

    public function getDownloadLink() {
        return $this->_downloadLink;
    }

    public function setDescription($des) {
        $this->_description = $des;
    }

    public function getDescription() {
        return $this->_description;
    }

    public function setNavLink($navLink) {
        $this->_navLink = $navLink;
    }

    public function getNaviLink() {
        return $this->_navLink;
    }

    public function setDlLink($link) {
        $this->_dlLink = $link;
    }

    public function getDLink() {
        return $this->_dlLink;
    }

    public function setLink($link) {
        $this->_link = $link;
    }

    public function getLink() {
        return $this->_link;
    }

    function __construct($idupl = 0, $currFilePath = 0, $type = 0, $date = 0) {
        $this->_idupl = $idupl;
        $this->_currFilePath = $currFilePath;
        $this->_type = $type;
        $this->_date = $date;
    }

    public function setName($name) {
        $this->_name = $name;
    }

    public function getName() {
        return $this->_name;
    }

    public function setSize($size) {
        $this->_size = $size;
    }

    public function getSize() {
        return $this->_size;
    }

    /**
     * This function builds the logical hierarchy for virtuell directories to offer the known navigation
     * used from filesystem based file managers.
     *
     *
     * @param string $pathname
     */
    public function setHierarchie($pathname) {
        if (substr_count($pathname, '/') == 0) {
            $this->_hierachie = 0;
        } else {
            $this->_hierachie = substr_count($pathname, '/') - 1;
        }
    }

    public function getHierarchie() {
        return $this->_hierachie;
    }

    public function getIdupl() {
        return $this->_idupl;
    }

    public function setIdupl($idupl) {
        $this->_idupl = $idupl;
    }

    public function getCurrFilePath() {
        return $this->_currFilePath;
    }

    public function setCurrFilePath($currFilePath) {
        $this->_currFilePath = $currFilePath;
    }

    public function getType() {
        return $this->_type;
    }

    public function setType($type) {
        $this->_type = $type;
    }

    public function getDate() {
        return $this->_date;
    }

    public function setDate($date) {
        $year = substr($date, 0, 4);
        $date = substr($date, 5, strlen($date));
        $month = substr($date, 0, 2);
        $date = substr($date, 3, strlen($date));
        $day = substr($date, 0, 2);
        $this->_date = $day . '.' . $month . '.' . $year;
        $this->_date = trim($this->_date);
    }

    public function setFolder($folder) {
        $this->_folder = $folder;
    }

    public function getFolder() {
        return $this->_folder;
    }

    public function cmpAscName($a, $b) {
        $cmp = strcmp($b->getName(), $a->getName());
        return $cmp;
    }

    public function cmpDesName($a, $b) {
        $cmp = strcmp($b->getName(), $a->getName());
        return ($cmp) * -1;
    }

    public function cmpAscType($a, $b) {
        $cmp = strcmp($b->getType(), $a->getType());
        return $cmp;
    }

    public function cmpDesType($a, $b) {
        $cmp = strcmp($b->getType(), $a->getType());
        return ($cmp) * -1;
    }

    public function cmpAscDescription($a, $b) {
        $cmp = strcmp($b->getDescription(), $a->getDescription());
        return $cmp;
    }

    public function cmpDesDescription($a, $b) {
        $cmp = strcmp($b->getDescription(), $a->getDescription());
        return ($cmp) * -1;
    }

    public function cmpAscSize($a, $b) {
        $cmp = strcmp($b->getSize(), $a->getSize());
        return $cmp;
    }

    public function cmpDesSize($a, $b) {
        $cmp = strcmp($b->getSize(), $a->getSize());
        return ($cmp) * -1;
    }

    public function cmpAscDate($a, $b) {
        $cmp = strcmp($b->getDate(), $a->getDate());
        return $cmp;
    }

    public function cmpDesDate($a, $b) {
        $cmp = strcmp($b->getDate(), $a->getDate());
        return ($cmp) * -1;
    }

}
class UplFile extends uplContent {

    public function __construct($idupl = 0, $currFilePath = 0, $type = 0, $date = 0) {
        parent::__construct($idupl, $currFilePath, $type, $date);
    }

    public function isDir() {
        return false;
    }

}
class UplDir extends uplContent {

    public function __construct($idupl = 0, $currFilePath = 0, $type = 0, $date = 0, $link = 0) {
        parent::__construct($idupl, $currFilePath, $type, $date);
        $this->_dirlink = $link;
    }

    private $_dirLink;

    public function isDir() {
        return true;
    }

    public function getDirLink() {
        return $this->_dirLink;
    }

    public function setDirLink($link) {
        $this->_dirLink = $link;
    }

    public function setFolderDir($folderName) {
        $pos = 0;

        for ($i = 0; $i < strlen($folderName); $i++) {

            if ($folderName[$i] === '/') {
                $pos++;
                if ($pos == $this->getHierarchie()) {
                    $pos = $i;
                }
            }
        }
        ($pos == 1)? $this->setFolder('') : $this->setFolder(substr($folderName, 0, $pos));
    }

}

?>