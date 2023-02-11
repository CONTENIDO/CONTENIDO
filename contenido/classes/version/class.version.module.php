<?php

/**
 * This file contains the module version class.
 *
 * @package    Core
 * @subpackage Versioning
 * @author     Bilal Arslan, Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Super class for revision
 *
 * @package    Core
 * @subpackage Versioning
 */
class cVersionModule extends cVersion {

    /**
     * Module type.
     *
     * @var string
     */
    public $sModType;

    /**
     * Constructor to create an instance of this class.
     *
     * Initializes class variables.
     *
     * @param string $iIdMod
     *         The name of style file
     * @param array  $aCfg
     * @param array  $aCfgClient
     * @param cDb    $oDB
     *         CONTENIDO database object
     * @param int    $iClient
     * @param string $sArea
     * @param int    $iFrame
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function __construct($iIdMod, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame) {
        // Set globals in main class
        parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);

        // folder layout
        $this->sType = 'module';
        $this->iIdentity = $iIdMod;

        $this->prune();
        $this->initRevisions();
        $this->_storeModuleInformation();
    }

    /**
     * @throws cDbException|cException|cInvalidArgumentException
     */
    protected function _storeModuleInformation() {
        $iIdMod = cSecurity::toInteger($this->iIdentity);
        $oModule = new cApiModule($iIdMod);

        // create body node of XML file
        $this->setData('Name', $oModule->getField('name'));
        $this->setData('Type', $oModule->getField('type'));
        $this->setData('Error', $oModule->getField('error'));
        $this->setData('Description', $oModule->getField('description') ?? '');
        $this->setData('Deletable', $oModule->getField('deletable'));
        $this->setData('Template', $oModule->getField('template'));
        $this->setData('Static', $oModule->getField('static'));
        $this->setData('PackageGuid', $oModule->getField('package_guid'));
        $this->setData('PackageData', $oModule->getField('package_data'));

        // retrieve module code from files
        $oModuleHandler = new cModuleHandler($iIdMod);
        $this->setData('CodeOutput', conHtmlSpecialChars($oModuleHandler->readOutput()));
        $this->setData('CodeInput', conHtmlSpecialChars($oModuleHandler->readInput()));
    }

    /**
     * This function read a xml file nodes
     *
     * @param string $sPath
     *         Path to file
     * @return array
     *         returns array width this four nodes
     */
    public function initXmlReader($sPath) {
        $aResult = [];
        if ($sPath != '') {
            // Output this xml file
            $sXML = simplexml_load_file($sPath);

            if ($sXML) {
                foreach ($sXML->body as $oBodyValues) {
                    // if choose xml file read value an set it
                    $aResult['name'] = $oBodyValues->Name;
                    $aResult['desc'] = $oBodyValues->Description;
                    $aResult['code_input'] = htmlspecialchars_decode($oBodyValues->CodeInput);
                    $aResult['code_output'] = htmlspecialchars_decode($oBodyValues->CodeOutput);
                }
            }
        }

        return $aResult;
    }

    /**
     * Function returns javascript which refreshes CONTENIDO frames for file
     * list a sub-navigation. This is necessary, if filenames where changed,
     * when a history entry is restored.
     *
     * @param string $sArea
     *         name of CONTENIDO area in which this procedure should be done
     * @param int $iIdModule
     *         Id of module
     * @param cSession $sess
     *         CONTENIDO session object
     * @return string
     *         Javascript for refreshing left_bottom frame
     */
    public function renderReloadScript($sArea, $iIdModule, cSession $sess) {
        $urlLeftBottom = $sess->url("main.php?area=$sArea&frame=2&idmod=$iIdModule");
        return <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('left_bottom');
    if (frame) {
        frame.location.href = '{$urlLeftBottom}';
    }
})(Con, Con.$);
</script>
JS;
    }

}
