<?php

/**
 * This file contains the upload collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upload collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiUpload>
 */
class cApiUploadCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('upl'), 'idupl');
        $this->_setItemClass('cApiUpload');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Synchronizes upload directory and file with database.
     *
     * @param string $dirname
     * @param string $filename
     * @param int $client
     * @return cApiUpload
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function sync($dirname, $filename, $client = 0)
    {
        $client = cSecurity::toInteger($client);
        if ($client <= 0) {
            $client = cRegistry::getClientId();
        }

        // build escaped vars for SQL
        $escClient = cSecurity::toInteger($client);
        $escDirname = $this->escape($dirname);
        $escFilename = $this->escape($filename);

        // Unix style OS distinguish between lower and uppercase file names,
        // i.e. test.gif is not the same as Test.gif
        // Windows OS doesn't distinguish between lower and uppercase file
        // names, i.e. test.gif is the same as Test.gif in file system
        $os = cString::toLowerCase(getenv('OS'));
        $isWindows = cString::findFirstPos($os, 'windows') !== false;
        $binary = $isWindows ? '' : 'BINARY';

        $this->select("idclient = $escClient AND dirname = $binary '$escDirname' AND filename = $binary '$escFilename'");

        if (($item = $this->next()) !== false) {
            $item->update();
        } else {
            $filetype = cFileHandler::getExtension($dirname . $filename);
            $iFilesize = cApiUpload::getFileSize($dirname, $filename);
            $item = $this->create($dirname, $filename, $filetype, $iFilesize, '');
        }

        return $item;
    }

    /**
     * Creates a upload entry.
     *
     * @param string $dirname
     * @param string $filename
     * @param string $filetype [optional]
     * @param int $filesize [optional]
     * @param string $description [optional]
     * @param int $status [optional]
     * @return cApiUpload
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create(
        $dirname,
        $filename,
        $filetype = '',
        $filesize = 0,
        $description = '',
        $status = 0
    )
    {
        $client = cRegistry::getClientId();
        $auth = cRegistry::getAuth();

        $item = $this->createNewItem();

        $item->set('idclient', $client);
        $item->set('filename', $filename, false);
        $item->set('filetype', $filetype, false);
        $item->set('size', $filesize, false);
        $item->set('dirname', $dirname, false);
        // $item->set('description', $description, false);
        $item->set('status', $status, false);
        $item->set('author', $auth->getUserId());
        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->store();

        return $item;
    }

    /**
     * Deletes upload file and its properties
     *
     * @inheritDoc
     * @param int $id
     * @throws cDbException|cException
     * @todo Code is similar/redundant to include.upl_files_overview.php 216-230
     */
    public function delete($id)
    {
        $id = cSecurity::toInteger($id);

        $clientCfg = cRegistry::getClientConfig(cRegistry::getClientId());

        $oUpload = new cApiUpload();
        $oUpload->loadByPrimaryKey($id);

        $sDirFileName = $oUpload->get('dirname') . $oUpload->get('filename');

        // call chain for deleted file
        $cecIterator = cApiCecRegistry::getInstance()->getIterator('Contenido.Upl_edit.Delete');
        while ($chainEntry = $cecIterator->next()) {
            $chainEntry->execute($oUpload->get('idupl'), $oUpload->get('dirname'), $oUpload->get('filename'));
        }

        // delete from dbfs or filesystem
        if (cApiDbfs::isDbfs($sDirFileName)) {
            $oDbfs = new cApiDbfsCollection();
            $oDbfs->remove($sDirFileName);
        } elseif (cFileHandler::exists($clientCfg['upl']['path'] . $sDirFileName)) {
            unlink($clientCfg['upl']['path'] . $sDirFileName);
        }

        // delete properties
        // note: parents delete methods does normally this job, but the
        // properties are stored by using dirname + filename instead of idupl
        $oUpload->deletePropertiesByItemid($sDirFileName);

        $this->deleteUploadMetaData($id);

        // delete in DB
        return parent::delete($id);
    }

    /**
     * Deletes meta-data from con_upl_meta table if file is deleting
     *
     * @param int $id
     * @throws cDbException|cInvalidArgumentException
     */
    protected function deleteUploadMetaData($id): bool
    {
        return (new cApiUploadMetaCollection())->deleteBy('idupl', cSecurity::toInteger($id)) > 0;
    }

    /**
     * Deletes upload directory by its dirname for current client.
     *
     * @param string $dirname
     * @throws cDbException|cException
     */
    public function deleteByDirname($dirname)
    {
        $client = cRegistry::getClientId();
        $this->select("dirname = '" . $this->escape($dirname) . "' AND idclient = " . $client);
        while ($oUpload = $this->next()) {
            $this->delete($oUpload->get('idupl'));
        }
    }
}

/**
 * Upload item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiUpload extends Item
{

    /**
     * Property collection instance
     *
     * @var cApiPropertyCollection
     */
    protected $_oPropertyCollection;

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('upl'), 'idupl');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Updates upload recordset.
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function update()
    {
        $dirname = $this->get('dirname');
        $filename = $this->get('filename');
        $sExtension = cFileHandler::getExtension($dirname . $filename);
        $filesize = self::getFileSize($dirname, $filename);

        $bTouched = false;

        if ($this->get('filetype') != $sExtension) {
            $this->set('filetype', $sExtension);
            $bTouched = true;
        }

        if ($this->get('size') != $filesize) {
            $this->set('size', $filesize);
            $bTouched = true;
        }

        if ($bTouched) {
            $this->store();
        }
    }

    /**
     * Stores made changes
     *
     * @inheritDoc
     */
    public function store()
    {
        $auth = cRegistry::getAuth();
        $this->set('modifiedby', $auth->getUserId());
        $this->set('lastmodified', date('Y-m-d H:i:s'), false);

        // Call chain
        $cecIterator = cApiCecRegistry::getInstance()->getIterator('Contenido.Upl_edit.SaveRows');
        while ($chainEntry = $cecIterator->next()) {
            $chainEntry->execute($this->get('idupl'), $this->get('dirname'), $this->get('filename'));
        }

        return parent::store();
    }

    /**
     * Deletes all upload properties by its itemid
     *
     * @param string $itemId
     * @throws cDbException|cInvalidArgumentException
     */
    public function deletePropertiesByItemid($itemId)
    {
        $oPropertiesColl = $this->_getPropertiesCollectionInstance();
        $oPropertiesColl->deleteProperties('upload', $itemId);
    }

    /**
     * Returns the filesize
     *
     * @param string $dirname
     * @param string $filename
     * @throws cDbException|cException
     */
    public static function getFileSize($dirname, $filename): int
    {
        $bIsDbfs = cApiDbfs::isDbfs($dirname);
        if (!$bIsDbfs) {
            $clientCfg = cRegistry::getClientConfig(cRegistry::getClientId());
            $dirname = $clientCfg['upl']['path'] . $dirname;
        }

        $sFilePathName = $dirname . $filename;

        $fileSize = 0;
        if ($bIsDbfs) {
            $oDbfsCol = new cApiDbfsCollection();
            $fileSize = $oDbfsCol->getSize($sFilePathName);
        } elseif (cFileHandler::exists($sFilePathName)) {
            $fileSize = filesize($sFilePathName);
        }

        return cSecurity::toInteger($fileSize);
    }

    /**
     * Lazy instantiation and return of properties object for current client.
     */
    protected function _getPropertiesCollectionInstanceX(): cApiPropertyCollection
    {
        // Runtime on-demand allocation of the properties object
        if (!is_object($this->_oPropertyCollection)) {
            $this->_oPropertyCollection = new cApiPropertyCollection();
            $this->_oPropertyCollection->changeClient(cRegistry::getClientId());
        }
        return $this->_oPropertyCollection;
    }
}
