<?php

/**
 * This file contains the layout version class.
 *
 * @package    Core
 * @subpackage Versioning
 * @author     Bilal Arslan, Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class of Layout Revision
 * We use super class Version to create a new Version.
 *
 * @package    Core
 * @subpackage Versioning
 */
class cVersionLayout extends cVersion
{

    /**
     * The name of Layout
     *
     * @var string
     */
    private $sName;

    /**
     * The code of Layout
     *
     * @var string
     */
    private $sCode;

    /**
     * The Description of Layout
     *
     * @var string
     */
    protected $sDescription;

    /**
     * Whether the layout is deletable.
     *
     * @var int  1 or 0
     */
    private $iDeletable;

    /**
     * Constructor to create an instance of this class.
     *
     * Initializes class variables.
     *
     * @param string $iIdLayout
     *         The name of style file
     * @param array $aCfg
     * @param array $aCfgClient
     * @param cDb $oDB
     *         CONTENIDO database object
     * @param int $iClient
     * @param string $sArea
     * @param int $iFrame
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function __construct($iIdLayout, $aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame)
    {
        // Init class members in super class
        parent::__construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame);

        // folder layout
        $this->sType = 'layout';
        $this->iIdentity = $iIdLayout;

        // This function looks if maximum number of stored versions is achieved
        $this->prune();

        $this->initRevisions();

        // Set layout table Information
        $this->setLayoutTable();

        // Create Body Node of Xml File
        $this->setData('name', $this->sName);
        $this->setData('description', $this->sDescription);
        $this->setData('code', $this->sCode);
        $this->setData('deletable', $this->iDeletable);
    }

    /**
     * Set code to data ...
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->setData('code', $code);
    }

    /**
     * Function reads rows variables from table con_layout and init with the
     * class members.
     *
     * @throws cDbException|cException
     */
    private function setLayoutTable()
    {
        $oLayout = new cApiLayout($this->iIdentity);
        if ($oLayout->isLoaded()) {
            $this->iClient = $oLayout->get('idclient');
            $this->sName = $oLayout->get('name');
            $this->sDescription = $oLayout->get('description') ?? '';
            $this->iDeletable = cSecurity::toInteger($oLayout->get('deletable'));
            $this->sAuthor = $oLayout->get('author');
            $this->dCreated = $oLayout->get('created');
            $this->dLastModified = $oLayout->get('lastmodified');
        }
    }

    /**
     * This function reads xml file nodes
     *
     * @param string $sPath
     *         Path to file
     * @return array
     *         returns array width this three nodes
     */
    public function initXmlReader($sPath)
    {
        $aResult = [];
        if ($sPath != '') {
            // Output this xml file
            $sXML = simplexml_load_file($sPath);

            if ($sXML) {
                foreach ($sXML->body as $oBodyValues) {
                    // if choose xml file read value an set it
                    $aResult['name'] = $oBodyValues->name;
                    $aResult['desc'] = $oBodyValues->description;
                    $aResult['code'] = $oBodyValues->code;
                }
            }
        }
        return $aResult;
    }

    /**
     * Function returns javascript which refreshes CONTENIDO frames for file
     * list a sub navigation.
     * This is necessary, if filenames where changed, when a history entry is
     * restored
     *
     * @param string $sArea
     *         name of CONTENIDO area in which this procedure should be done
     * @param int $iIdLayout
     *         Id of layout to highlight
     * @param object $sess
     *         CONTENIDO session object
     * @return string
     *         Javascript for refreshing frames
     */
    public function renderReloadScript($sArea, $iIdLayout, $sess)
    {
        $urlLeftBottom = $sess->url("main.php?area=$sArea&frame=2&idlay=$iIdLayout");
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
