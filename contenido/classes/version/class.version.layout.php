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
     * @var string The name of Layout
     */
    private $sName;

    /**
     * @var string The code of Layout
     */
    private $sCode;

    /**
     * @var string The Description of Layout
     */
    protected $sDescription;

    /**
     * @var int  1 or 0. Whether the layout is deletable.
     */
    private $iDeletable;

    /**
     * Constructor to create an instance of this class.
     *
     * Initializes class variables.
     *
     * @param string $layoutId The name of style file
     * @param array $cfg
     * @param array $cfgClient
     * @param cDb $db CONTENIDO database object
     * @param int $clientId
     * @param string $area
     * @param int $frame
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function __construct($layoutId, array $cfg, array $cfgClient, cdb $db, $clientId, $area, $frame)
    {
        // Init class members in super class
        parent::__construct($cfg, $cfgClient, $db, $clientId, $area, $frame);

        // folder layout
        $this->sType = 'layout';
        $this->entityId = $layoutId;

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
        $oLayout = new cApiLayout($this->entityId);
        if ($oLayout->isLoaded()) {
            $this->clientId = $oLayout->get('idclient');
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
     * @param string $path Path to file
     * @return array Returns array width this three nodes
     */
    public function initXmlReader(string $path): array
    {
        $result = [];
        if ($path != '') {
            // Output this xml file
            $sXML = simplexml_load_file($path);

            if ($sXML) {
                foreach ($sXML->body as $oBodyValues) {
                    // if choose xml file read value an set it
                    $result['name'] = $oBodyValues->name;
                    $result['desc'] = $oBodyValues->description;
                    $result['code'] = $oBodyValues->code;
                }
            }
        }
        return $result;
    }

    /**
     * Function returns javascript which refreshes CONTENIDO frames for file list a sub navigation.
     * This is necessary, if filenames where changed, when a history entry is restored.
     *
     * @param string $area name of CONTENIDO area in which this procedure should be done
     * @param int $layoutId Id of layout to highlight
     * @param cSession $sess CONTENIDO session object
     * @return String Javascript for refreshing frames
     */
    public function renderReloadScript($area, $layoutId, cSession $sess)
    {
        $urlLeftBottom = $sess->url("main.php?area=$area&frame=2&idlay=$layoutId");
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
