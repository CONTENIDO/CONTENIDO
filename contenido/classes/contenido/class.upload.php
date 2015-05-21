<?php
/**
 * This file contains the upload collection and item class.
 *
 * @todo Reset in/out filters of parent classes.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upload collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiUploadCollection extends ItemCollection {

    /**
     * Constructor Function
     *
     * @global array $cfg
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['upl'], 'idupl');
        $this->_setItemClass('cApiUpload');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Syncronizes upload directory and file with database.
     *
     * @global int $client
     * @param string $sDirname
     * @param string $sFilename
     * @param int $clientid [optional]
     * @return cApiUpload
     */
    public function sync($sDirname, $sFilename, $client = 0) {
        $client = cSecurity::toInteger($client);

        if ($client <= 0) {
            global $client;
        }

        // build escaped vars for SQL
        $escClient = cSecurity::toInteger($client);
        $escDirname = $this->escape($sDirname);
        $escFilename = $this->escape($sFilename);

        // Unix style OS distinguish between lower and uppercase file names,
        // i.e. test.gif is not the same as Test.gif
        // Windows OS doesn't distinguish between lower and uppercase file
        // names, i.e. test.gif is the same as Test.gif in file system
        $os = strtolower(getenv('OS'));
        $isWindows = (false !== strpos($os, 'windows'));
        $binary = $isWindows ? '' : 'BINARY';

        $this->select("idclient = $escClient AND dirname = $binary '$escDirname' AND filename = $binary '$escFilename'");

        if (false !== $oItem = $this->next()) {
            $oItem->update();
        } else {
        	$sFiletype = cFileHandler::getExtension($sDirname . $sFilename);
            $iFilesize = cApiUpload::getFileSize($sDirname, $sFilename);
            $oItem = $this->create($sDirname, $sFilename, $sFiletype, $iFilesize, '');
        }

        return $oItem;
    }

    /**
     * Creates a upload entry.
     *
     * @global int $client
     * @global array $cfg
     * @global object $auth
     * @param string $sDirname
     * @param string $sFilename
     * @param string $sFiletype [optional]
     * @param int $iFileSize [optional]
     * @param string $sDescription [optional]
     * @param int $iStatus [optional]
     * @return cApiUpload
     */
    public function create($sDirname, $sFilename, $sFiletype = '', $iFileSize = 0,
            $sDescription = '', $iStatus = 0) {
        global $client, $cfg, $auth;

        $oItem = $this->createNewItem();

        $oItem->set('idclient', $client);
        $oItem->set('filename', $sFilename, false);
        $oItem->set('filetype', $sFiletype, false);
        $oItem->set('size', $iFileSize, false);
        $oItem->set('dirname', $sDirname, false);
        // $oItem->set('description', $sDescription, false);
        $oItem->set('status', $iStatus, false);
        $oItem->set('author', $auth->auth['uid']);
        $oItem->set('created', date('Y-m-d H:i:s'), false);
        $oItem->store();

        return $oItem;
    }

    /**
     * Deletes upload file and it's properties
     *
     * @todo Code is similar/redundant to include.upl_files_overview.php 216-230
     * @global cApiCecRegistry $_cecRegistry
     * @global array $cfgClient
     * @global int $client
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        global $cfgClient, $client;

        $oUpload = new cApiUpload();
        $oUpload->loadByPrimaryKey($id);

        $sDirFileName = $oUpload->get('dirname') . $oUpload->get('filename');

        // call chain for deleted file
        $_cecIterator = cRegistry::getCecRegistry()->getIterator('Contenido.Upl_edit.Delete');
        if ($_cecIterator->count() > 0) {
            while (($chainEntry = $_cecIterator->next()) !== false) {
                $chainEntry->execute($oUpload->get('idupl'), $oUpload->get('dirname'), $oUpload->get('filename'));
            }
        }

        // delete from dbfs or filesystem
        if (cApiDbfs::isDbfs($sDirFileName)) {
            $oDbfs = new cApiDbfsCollection();
            $oDbfs->remove($sDirFileName);
        } elseif (cFileHandler::exists($cfgClient[$client]['upl']['path'] . $sDirFileName)) {
            unlink($cfgClient[$client]['upl']['path'] . $sDirFileName);
        }

        // delete properties
        // note: parents delete methos does normally this job, but the
        // properties are stored by using dirname + filename instead of idupl
        $oUpload->deletePropertiesByItemid($sDirFileName);

        $this->deleteUploadMetaData($id);

        // delete in DB
        return parent::delete($id);
    }

    /**
     * Deletes meta-data from con_upl_meta table if file is deleting
     *
     * @param int $idupl
     * @return bool
     */
    protected function deleteUploadMetaData($idupl) {
        global $client, $db, $cfg;
        $sql = "DELETE FROM `%s` WHERE %s = '%s'";
        return $db->query($sql, $cfg['tab']['upl_meta'], 'idupl', (int) $idupl);
    }

    /**
     * Deletes upload directory by its dirname.
     *
     * @global int $client
     * @param string $sDirname
     */
    public function deleteByDirname($sDirname) {
        global $client;

        $this->select("dirname = '" . $this->escape($sDirname) . "' AND idclient = " . (int) $client);
        while (($oUpload = $this->next()) !== false) {
            $this->delete($oUpload->get('idupl'));
        }
    }
}

/**
 * Upload item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiUpload extends Item {

    /**
     * Property collection instance
     *
     * @var cApiPropertyCollection
     */
    protected $_oPropertyCollection;

    /**
     * Constructor Function
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['upl'], 'idupl');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates upload recordset
     */
    public function update() {
        $sDirname = $this->get('dirname');
        $sFilename = $this->get('filename');
        $sExtension = cFileHandler::getExtension($sDirname . $sFilename);
        $iFileSize = self::getFileSize($sDirname, $sFilename);

        $bTouched = false;

        if ($this->get('filetype') != $sExtension) {
            $this->set('filetype', $sExtension);
            $bTouched = true;
        }

        if ($this->get('size') != $iFileSize) {
            $this->set('size', $iFileSize);
            $bTouched = true;
        }

        if ($bTouched == true) {
            $this->store();
        }
    }

    /**
     * Stores made changes
     *
     * @global object $auth
     * @global cApiCecRegistry $_cecRegistry
     * @return bool
     */
    public function store() {
        global $auth, $_cecRegistry;

        $this->set('modifiedby', $auth->auth['uid']);
        $this->set('lastmodified', date('Y-m-d H:i:s'), false);

        // Call chain
        $_cecIterator = $_cecRegistry->getIterator('Contenido.Upl_edit.SaveRows');
        if ($_cecIterator->count() > 0) {
            while (($chainEntry = $_cecIterator->next()) !== false) {
                $chainEntry->execute($this->get('idupl'), $this->get('dirname'), $this->get('filename'));
            }
        }

        return parent::store();
    }

    /**
     * Deletes all upload properties by it's itemid
     *
     * @param string $sItemid
     */
    public function deletePropertiesByItemid($sItemid) {
        $oPropertiesColl = $this->_getPropertiesCollectionInstance();
        $oPropertiesColl->deleteProperties('upload', $sItemid);
    }

    /**
     * Returns the filesize
     *
     * @param string $sDirname
     * @param string $sFilename
     * @return string
     */
    public static function getFileSize($sDirname, $sFilename) {
        global $client, $cfgClient;

        $bIsDbfs = cApiDbfs::isDbfs($sDirname);
        if (!$bIsDbfs) {
            $sDirname = $cfgClient[$client]['upl']['path'] . $sDirname;
        }

        $sFilePathName = $sDirname . $sFilename;

        $iFileSize = 0;
        if ($bIsDbfs) {
            $oDbfsCol = new cApiDbfsCollection();
            $iFileSize = $oDbfsCol->getSize($sFilePathName);
        } elseif (cFileHandler::exists($sFilePathName)) {
            $iFileSize = filesize($sFilePathName);
        }

        return $iFileSize;
    }

    /**
     * Lazy instantiation and return of properties object
     *
     * @global int $client
     * @return cApiPropertyCollection
     */
    protected function _getPropertiesCollectionInstanceX() {
        global $client;

        // Runtime on-demand allocation of the properties object
        if (!is_object($this->_oPropertyCollection)) {
            $this->_oPropertyCollection = new cApiPropertyCollection();
            $this->_oPropertyCollection->changeClient($client);
        }
        return $this->_oPropertyCollection;
    }
}
