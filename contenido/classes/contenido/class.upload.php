<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Code is taken over from file contenido/classes/class.upload.php in favor of
 * normalizing API.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * @todo  Reset in/out filters of parent classes.
 *
 * {@internal
 *   created  2011-10-11
 *   modified 2011-12-09, Ingo van Peeren, added return of upload object in
 *                        method sync()
 *
 *   $Id: $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Upload collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiUploadCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @global array $cfg
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['upl'], 'idupl');
        $this->_setItemClass('cApiUpload');
    }


    /**
     * Syncronizes upload directory and file with database.
     * @global int $client
     * @param string $sDirname
     * @param string $sFilename
     * return cApiUpload
     */
    public function sync($sDirname, $sFilename)
    {
        global $client;

        $sDirname = $this->escape($sDirname);
        $sFilename = $this->escape($sFilename);
        if (strstr(strtolower($_ENV['OS']), 'windows') === false) {
            // Unix style OS distinguish between lower and uppercase file names, i.e. test.gif is not the same as Test.gif
            $this->select("dirname = BINARY '$sDirname' AND filename = BINARY '$sFilename' AND idclient=" . (int) $client);
        } else {
            // Windows OS doesn't distinguish between lower and uppercase file names, i.e. test.gif is the same as Test.gif in file system
            $this->select("dirname = '$sDirname' AND filename = '$sFilename' AND idclient=" . (int) $client);
        }

        if ($oItem = $this->next()) {
            $oItem->update();
        } else {
            $oItem = $this->create($sDirname, $sFilename);
        }
        
        return $oItem;
    }


    /**
     * Creates a upload entry.
     * @global int $client
     * @global array $cfg
     * @global object $auth
     * @param string $sDirname
     * @param string $sFilename
     * @return cApiUpload
     */
    public function create($sDirname, $sFilename)
    {
        global $client, $cfg, $auth;

        $oItem = parent::create();

        $oItem->set('idclient', $client);
        $oItem->set('filename', $sFilename, false);
        $oItem->set('dirname', $sDirname, false);
        $oItem->set('author', $auth->auth['uid']);
        $oItem->set('created', date('Y-m-d H:i:s'), false);
        $oItem->store();

        return ($oItem);
    }


    /**
     * Deletes upload file and it's properties
     * @global cApiCECRegistry $_cecRegistry
     * @global array $cfgClient
     * @global int $client
     * @param int $id
     * @return bool
     * FIXME  Code is similar/redundant to include.upl_files_overview.php 216-230
     */
    public function delete($id)
    {
        global $_cecRegistry, $cfgClient, $client;

        $oUpload = new cApiUpload();
        $oUpload->loadByPrimaryKey($id);

        $sDirFileName = $oUpload->get('dirname') . $oUpload->get('filename');

        // call chain
        $_cecIterator = $_cecRegistry->getIterator('Contenido.Upl_edit.Delete');
        if ($_cecIterator->count() > 0) {
            while ($chainEntry = $_cecIterator->next()) {
                $chainEntry->execute($oUpload->get('idupl'), $oUpload->get('dirname'), $oUpload->get('filename'));
            }
        }

        // delete from dbfs or filesystem
        if (is_dbfs($sDirFileName)) {
            $oDbfs = new cApiDbfsCollection();
            $oDbfs->remove($sDirFileName);
        } elseif (file_exists($cfgClient[$client]['upl']['path'] . $sDirFileName)) {
            unlink($cfgClient[$client]['upl']['path'] . $sDirFileName);
        }

        // delete properties
        // note: parents delete methos does normally this job, but the properties
        // are stored by using dirname + filename instead of idupl
        $oUpload->deletePropertiesByItemid($sDirFileName);

        // delete in DB
        return parent::delete($id);
    }

    /**
     * Deletes upload directory by its dirname.
     * @global int $client
     * @param string $sDirname
     */
    public function deleteByDirname($sDirname)
    {
        global $client;

        $this->select("dirname='" . $this->escape($sDirname) . "' AND idclient=" . (int) $client);
        while ($oUpload = $this->next()) {
            $this->delete($oUpload->get('idupl'));
        }
    }
}


/**
 * Upload item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiUpload extends Item
{
    /**
     * Property collection instance
     * @var cApiPropertyCollection
     */
    protected $_oPropertyCollection;

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['upl'], 'idupl');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }


    /**
     * Updates upload recordset
     * @global int $client
     * @global array $cfgClient
     */
    public function update()
    {
        global $client, $cfgClient;

        $bIsDbfs = is_dbfs($this->get('dirname'));
        if (is_dbfs($this->get('dirname'))) {
            $sDirname = $this->get('dirname');
        } else {
            $sDirname = $cfgClient[$client]['upl']['path'] . $this->get('dirname');
        }

        $sFile = $this->get('filename');
        $sFilePathName = $sDirname . $sFile;
        $sExtension = (string) uplGetFileExtension($sFile);

        $iFileSize = 0;
        if ($bIsDbfs) {
            $oDbfsCol = new cApiDbfsCollection();
            $iFileSize = $oDbfsCol->getSize($sFilePathName);
        } elseif (file_exists($sFilePathName)) {
            $iFileSize = filesize($sFilePathName);
        }

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
     * @global object $auth
     * @global cApiCECRegistry $_cecRegistry
     * @return bool
     */
    public function store()
    {
        global $auth, $_cecRegistry;

        $this->set('modifiedby', $auth->auth['uid']);
        $this->set('lastmodified', date('Y-m-d H:i:s'), false);

        // Call chain
        $_cecIterator = $_cecRegistry->getIterator('Contenido.Upl_edit.SaveRows');
        if ($_cecIterator->count() > 0) {
            while ($chainEntry = $_cecIterator->next()) {
                $chainEntry->execute($this->get('idupl'), $this->get('dirname'), $this->get('filename'));
            }
        }

        return parent::store();
    }


    /**
     * Deletes all upload properties by it's itemid
     * @param string $sItemid
     */
    public function deletePropertiesByItemid($sItemid)
    {
        $oPropertiesColl = $this->_getPropertiesCollectionInstance();
        $oPropertiesColl->deleteProperties('upload', $sItemid);
    }


    /**
     * Lazy instantiation and return of properties object
     * @global int $client
     * @return cApiPropertyCollection
     */
    protected function _getPropertiesCollectionInstanceX()
    {
        global $client;

        // Runtime on-demand allocation of the properties object
        if (!is_object($this->_oPropertyCollection)) {
            $this->_oPropertyCollection = new cApiPropertyCollection();
            $this->_oPropertyCollection->changeClient($client);
        }
        return $this->_oPropertyCollection;
    }

}


################################################################################
# Old versions of upload item collection and upload item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in 
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.


/**
 * Upload collection
 * @deprecated  [2011-10-11] Use cApiUploadCollection instead of this class.
 */
class UploadCollection extends cApiUploadCollection
{
    public function __construct()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct();
    }
    public function UploadCollection()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct();
    }
}


/**
 * Single upload item
 * @deprecated  [2011-10-11] Use cApiUpload instead of this class.
 */
class UploadItem extends cApiUpload
{
    public function __construct($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct($mId);
    }
    public function UploadItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }
}

?>