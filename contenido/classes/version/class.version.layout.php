<?php

/**
 * This file contains the layout version class.
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
 * Class of Layout Revision
 * We use super class Version to create a new Version.
 *
 * @package    Core
 * @subpackage Versioning
 */
class cVersionLayout extends cVersion {

    /**
     * The name of Layout
     *
     * @var unknown_type
     */
    private $sName;

    /**
     * The code of Layout
     *
     * @var unknown_type
     */
    private $sCode;

    /**
     * The Description of Layout
     *
     * @var unknown_type
     */
    private $sDescripion;

    /**
     * The Metainformation about layout
     *
     * @var unknown_type
     */
    private $sDeletabel;

    /**
     * The class versionLayout object constructor, initializes class variables
     *
     * @param string $iIdLayout
     *         The name of style file
     * @param array $aCfg
     * @param array $aCfgClient
     * @param cDB $oDB
     * @param int $iClient
     * @param object $sArea
     * @param object $iFrame
     */
    public function __construct($iIdLayout, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame) {
        // Init class members in super class
        parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);

        // folder layout
        $this->sType = "layout";
        $this->iIdentity = $iIdLayout;

        // This function looks if maximum number of stored versions is achieved
        $this->prune();

        $this->initRevisions();

        // Set Layout Table Iformation
        $this->setLayoutTable();

        // Create Body Node of Xml File
        $this->setData("name", $this->sName);
        $this->setData("description", $this->sDescripion);
        $this->setData("code", $this->sCode);
        $this->setData("deletable", $this->sDeletabel);
    }

    /**
     * Set code to data ...
     *
     * @param string $code
     */
    public function setCode($code) {
        $this->setData('code', $code);
    }

    /**
     * Function reads rows variables from table con_layout and init with the
     * class members.
     */
    private function setLayoutTable() {
        if (!is_object($this->oDB)) {
            $this->oDB = cRegistry::getDb();
        }

        $sSql = "";
        $aLayout = array();

        $sSql = "SELECT * FROM " . $this->aCfg["tab"]["lay"] . "
                 WHERE idlay = '" . cSecurity::toInteger($this->iIdentity) . "'";

        if ($this->oDB->query($sSql)) {
            $this->oDB->nextRecord();
            $this->iClient = $this->oDB->f("idclient");
            $this->sName = $this->oDB->f("name");
            $this->sDescripion = $this->oDB->f("description");
            $this->sDeletabel = $this->oDB->f("deletable");
            $this->sAuthor = $this->oDB->f("author");
            $this->dCreated = $this->oDB->f("created");
            $this->dLastModified = $this->oDB->f("lastmodified");
        }
    }

    /**
     * This function read an xml file nodes
     *
     * @param string $sPath
     *         Path to file
     * @return array
     *         returns array width this three nodes
     */
    public function initXmlReader($sPath) {
        $aResult = array();
        if ($sPath != "") {
            // Output this xml file
            $sXML = simplexml_load_file($sPath);

            if ($sXML) {
                foreach ($sXML->body as $oBodyValues) {
                    // if choose xml file read value an set it
                    $aResult["name"] = $oBodyValues->name;
                    $aResult["desc"] = $oBodyValues->description;
                    $aResult["code"] = $oBodyValues->code;
                }
            }
        }
        return $aResult;
    }

    /**
     * Function returns javascript which refreshes CONTENIDO frames for file
     * list an subnavigation.
     * This is neccessary, if filenames where changed, when a history entry is
     * restored
     *
     * @param string $sArea
     *         name of CONTENIDO area in which this procedure should be done
     * @param int $iIdLayout
     *         Id of layout to highlight
     * @param object $sess
     *         CONTENIDO session object
     * @return string
     *         Javascript for refrehing frames
     */
    public function renderReloadScript($sArea, $iIdLayout, $sess) {
        $urlLeftBottom = $sess->url("main.php?area=$sArea&frame=2&idlay=$iIdLayout");
        $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('left_bottom');
    if (frame) {
        frame.location.href = '{$urlLeftBottom}';
    }
})(Con, Con.$);
</script>
JS;
        return $sReloadScript;
    }

}
