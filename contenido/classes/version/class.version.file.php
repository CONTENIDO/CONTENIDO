<?php

/**
 * This file contains the file version class.
 *
 * @package    Core
 * @subpackage Versioning
 * @version    SVN Revision $Rev:$
 *
 * @author     Bilal Arslan, Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class of File System
 * We use super class Version to create a new Version.
 *
 * @package    Core
 * @subpackage Versioning
 */
class cVersionFile extends cVersion {

    /**
     * Content code of current file.
     *
     * @var unknown_type
     */
    public $sCode;

    /**
     * Description folder of history sub nav.
     * Its not required to use it.
     *
     * @var unknown_type
     */
    public $sDescripion;

    /**
     * The path of style file.
     *
     * @var unknown_type
     */
    public $sPath;

    /**
     * The id of Type.
     *
     * @var unknown_type
     */
    public $sFileName;

    /**
     * The class versionStyle object constructor, initializes class variables
     *
     * @param string $iIdOfType
     *         The name of style file
     * @param array $aFileInfo
     *         Get FileInformation from table file_information
     * @param string $sFileName
     * @param string $sTypeContent
     * @param array $aCfg
     * @param array $aCfgClient
     * @param object $oDB
     * @param int $iClient
     * @param string $sArea
     * @param int $iFrame
     * @param string $sVersionFileName [optional]
     */
    public function __construct(
        $iIdOfType, $aFileInfo, $sFileName, $sTypeContent, $aCfg, $aCfgClient,
        $oDB, $iClient, $sArea, $iFrame, $sVersionFileName = ''
    ) {

        // Set globals in super class constructer
        parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);

        // Folder name is css or js ...
        $this->sType = $sTypeContent;

        // File Name for xml node
        $this->sFileName = $sFileName;

        // File Information, set for class Version to generate head xml nodes
        $this->sDescripion = $aFileInfo["description"];
        $this->sAuthor = $aFileInfo["author"];
        $this->dLastModified = $aFileInfo["lastmodified"];
        $this->dCreated = $aFileInfo["created"];

        // Frontendpath to files
        if ($sTypeContent == "templates") {
            $sTypeContent = "tpl";
        }

        $this->sPath = $this->aCfgClient[$this->iClient][$sTypeContent]["path"];

        // Identity the Id of Content Type
        $this->iIdentity = $iIdOfType;

        // This function looks if maximum number of stored versions is achieved
        $this->prune();

        // Take revision files if exists
        $this->initRevisions();

        // Get code of style
        $this->initFileContent();

        // Set Layout Table Iformation, currently not in use!
        // this->setLayoutTable();

        if ($sVersionFileName == '') {
            $sVersionFileName = $this->sFileName;
        }

        // Create Body Node of Xml File
        $this->setData("name", $sVersionFileName);
        $this->setData("code", $this->sCode);
        $this->setData("description", $this->sDescripion);
    }

    /**
     * This function init the class member sCode with current file content
     */
    protected function initFileContent() {
        if (cFileHandler::exists($this->sPath . $this->sFileName)) {
            $this->sCode = cFileHandler::read($this->sPath . $this->sFileName);
        } else {
            echo "<br>File not exists " . $this->sPath . $this->sFileName;
        }
    }

    /**
     * This function read an xml file nodes
     *
     * @param string $sPath
     *         Path to file
     * @return array
     *         returns array width nodes
     */
    public function initXmlReader($sPath) {
        $aResult = array();
        if ($sPath != "") {
            $xml = new cXmlReader();
            $xml->load($sPath);

            $aResult['name'] = $xml->getXpathValue('/version/body/name');
            $aResult['desc'] = $xml->getXpathValue('/version/body/description');
            $aResult['code'] = $xml->getXpathValue('/version/body/code');
        }

        return $aResult;
    }

    /**
     * This function reads the path of file
     *
     * @return string
     *         the path of file
     */
    public function getPathFile() {
        return $this->sPath;
    }

    /**
     * Function returns javascript which refreshes CONTENIDO frames for file
     * list an subnavigation.
     * This is necessary, if filenames where changed, when a history entry is
     * restored
     *
     * @param string $sArea
     *         name of CONTENIDO area in which this procedure should be done
     * @param string $sFilename
     *         new filename of file which should be updated in other frames
     * @param object $sess
     *         CONTENIDO session object
     * @return string
     *         Javascript for refrehing frames
     */
    public function renderReloadScript($sArea, $sFilename, $sess) {
        $urlRightTop = $sess->url("main.php?area=$sArea&frame=3&file=$sFilename&history=true");
        $urlLeftBottom = $sess->url("main.php?area=$sArea&frame=2&file=$sFilename");
        $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var right_top = Con.getFrame('right_top'),
        left_bottom = Con.getFrame('left_bottom');

    if (right_top) {
        right_top.location.href = '{$urlRightTop}';
    }

    if (left_bottom) {
        left_bottom.location.href = '{$urlLeftBottom}';
    }
})(Con, Con.$);
</script>
JS;
        return $sReloadScript;
    }

}
