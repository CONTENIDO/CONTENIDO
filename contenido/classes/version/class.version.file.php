<?php

/**
 * This file contains the file version class.
 *
 * @package    Core
 * @subpackage Versioning
 * @author     Bilal Arslan
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class of File System
 * We use super class Version to create a new Version.
 *
 * @package    Core
 * @subpackage Versioning
 */
class cVersionFile extends cVersion
{

    /**
     * @var string Content code of current file.
     */
    public $sCode;

    /**
     * @var string Description folder of history sub nav. It's not required to use it.
     */
    public $sDescription;

    /**
     * @var string The path of style file.
     */
    public $sPath;

    /**
     * @var string The id of Type.
     */
    public $sFileName;

    /**
     * Constructor to create an instance of this class.
     *
     * Initializes class variables.
     *
     * @param string $fileTypeId File type id
     * @param array $fileInfo Get FileInformation from table file_information
     * @param string $filename The name of the file
     * @param string $sTypeContent
     * @param array $cfg
     * @param array $cfgClient
     * @param cDb $db CONTENIDO database object
     * @param int $clientId
     * @param string $area
     * @param int $frame
     * @param string $sVersionFileName [optional]
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function __construct(
        $fileTypeId,
        array $fileInfo,
        $filename,
        $sTypeContent,
        array $cfg,
        array $cfgClient,
        cDb $db,
        $clientId,
        $area,
        $frame,
        $sVersionFileName = ''
    )
    {
        // Set globals in super class constructor
        parent::__construct($cfg, $cfgClient, $db, $clientId, $area, $frame);

        // Folder name is css or js ...
        $this->sType = $sTypeContent;

        // File Name for xml node
        $this->sFileName = $filename;

        // File Information, set for class Version to generate head xml nodes
        $this->sDescription = $fileInfo['description'] ?? '';
        $this->sAuthor = $fileInfo['author'] ?? '';
        $this->dLastModified = $fileInfo['lastmodified'] ?? '';
        $this->dCreated = $fileInfo['created'] ?? '';

        // Frontendpath to files
        if ($sTypeContent == 'templates') {
            $sTypeContent = 'tpl';
        }

        $this->sPath = $this->aCfgClient[$this->clientId][$sTypeContent]['path'];

        // Identity the Id of Content Type
        $this->entityId = $fileTypeId;

        // This function looks if maximum number of stored versions is achieved
        $this->prune();

        // Take revision files if exists
        $this->initRevisions();

        // Get code of style
        $this->initFileContent();

        // Set Layout Table Information, currently not in use!
        // this->setLayoutTable();

        if ($sVersionFileName == '') {
            $sVersionFileName = $this->sFileName;
        }

        // Create Body Node of Xml File
        $this->setData('name', $sVersionFileName);
        $this->setData('code', $this->sCode);
        $this->setData('description', $this->sDescription);
    }

    /**
     * This function init the class member sCode with current file content
     *
     * @throws cInvalidArgumentException
     */
    protected function initFileContent()
    {
        if (cFileHandler::exists($this->sPath . $this->sFileName)) {
            $this->sCode = cFileHandler::read($this->sPath . $this->sFileName);
        } else {
            echo '<br>File not exists ' . $this->sPath . $this->sFileName;
        }
    }

    /**
     * This function read an XML file nodes
     *
     * @param string $path Path to file
     * @return array Returns array width nodes
     * @throws cException
     */
    public function initXmlReader(string $path): array
    {
        $result = [];
        if ($path != '') {
            $xml = new cXmlReader();
            $xml->load($path);

            $result['name'] = $xml->getXpathValue('/version/body/name');
            $result['desc'] = $xml->getXpathValue('/version/body/description');
            $result['code'] = $xml->getXpathValue('/version/body/code');
        }

        return $result;
    }

    /**
     * Returns the path of file
     */
    public function getPathFile(): string
    {
        return $this->sPath;
    }

    /**
     * Function returns javascript which refreshes CONTENIDO frames for file list a sub-navigation.
     * This is necessary, if filenames where changed, when a history entry is restored
     *
     * @param string $area Name of CONTENIDO area in which this procedure should be done
     * @param string $filename New filename of file which should be updated in other frames
     * @param cSession $sess CONTENIDO session object
     * @return string Javascript for refreshing frames
     */
    public function renderReloadScript($area, $filename, cSession $sess)
    {
        $urlRightTop = $sess->url("main.php?area=$area&frame=3&file=$filename&history=true");
        $urlLeftBottom = $sess->url("main.php?area=$area&frame=2&file=$filename");
        return <<<JS
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
    }

}
