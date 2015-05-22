<?php

/**
 * This file contains the DBFS collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.file.php');

/**
 * DFFS item collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiDbfsCollection extends ItemCollection {

    /**
     * Constructor Function
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['dbfs'], 'iddbfs');
        $this->_setItemClass('cApiDbfs');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Outputs dbfs file related by it's path property
     *
     * @param string $path
     */
    public function outputFile($path) {
        global $cfg, $client, $auth;

        $path = $this->escape($path);
        $client = (int) $client;
        $path = cApiDbfs::stripPath($path);
        $dir = dirname($path);
        $file = basename($path);

        if ($dir == '.') {
            $dir = '';
        }

        $this->select("dirname = '" . $dir . "' AND filename = '" . $file . "' AND idclient = " . $client . " LIMIT 1");

        if (($item = $this->next()) !== false) {
            $properties = new cApiPropertyCollection();
            // Check if we're allowed to access it
            $protocol = cApiDbfs::PROTOCOL_DBFS;

            if ($properties->getValue('upload', $protocol . $dir . '/' . $file, 'file', 'protected') == '1') {
                if ($auth->auth['uid'] == 'nobody') {
                    header('HTTP/1.0 403 Forbidden');
                    return;
                }
            }
            $mimetype = $item->get('mimetype');

            header('Cache-Control: '); // leave blank to avoid IE errors
            header('Pragma: '); // leave blank to avoid IE errors
            header("Content-Type: $mimetype");
            header('Etag: ' . md5(mt_rand()));

            // Check, if output of Content-Disposition header should be skipped
            // for the mimetype
            $contentDispositionHeader = true;
            foreach ($cfg['dbfs']['skip_content_disposition_header_for_mimetypes'] as $mt) {
                if (strtolower($mt) == strtolower($mimetype)) {
                    $contentDispositionHeader = false;
                    break;
                }
            }
            if ($contentDispositionHeader) {
                header('Content-Disposition: attachment; filename=' . $file);
            }

            echo $item->get('content');
        }
    }

    /**
     * Writes physical existing file into dbfs
     *
     * @param string $localfile
     * @param string $targetfile
     */
    public function writeFromFile($localfile, $targetfile) {
        $targetfile = cApiDbfs::stripPath($targetfile);
        $stat = cFileHandler::info($localfile);
        $mimetype = $stat['mime'];

        $this->write($targetfile, cFileHandler::read($localfile), $mimetype);
    }

    /**
     * Writes dbfs file into phsical file system
     *
     * @param string $sourcefile
     * @param string $localfile
     */
    public function writeToFile($sourcefile, $localfile) {
        $sourcefile = cApiDbfs::stripPath($sourcefile);

        cFileHandler::write($localfile, $this->read($sourcefile));
    }

    /**
     * Writes dbfs file, creates if if not exists.
     *
     * @param string $file
     * @param string $content [optional]
     * @param string $mimetype [optional]
     */
    public function write($file, $content = '', $mimetype = '') {
        $file = cApiDbfs::stripPath($file);

        if (!$this->fileExists($file)) {
            $this->create($file, $mimetype);
        }
        $this->setContent($file, $content);
    }

    /**
     * Checks if passed dbfs path has any files.
     *
     * @global int $client
     * @param string $path
     * @return bool
     */
    public function hasFiles($path) {
        global $client;

        $path = cApiDbfs::stripPath($path);
        $client = (int) $client;

        // Are there any subdirs?
        $this->select("dirname LIKE '" . $path . "/%' AND idclient = " . $client . " LIMIT 1");
        if ($this->count() > 0) {
            return true;
        }

        $this->select("dirname LIKE '" . $path . "%' AND idclient = " . $client . " LIMIT 2");
        if ($this->count() > 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Reads content from dbfs file.
     *
     * @param string $file
     * @return string
     */
    public function read($file) {
        return ($this->getContent($file));
    }

    /**
     * Checks, if a dbfs file exists.
     *
     * @global int $client
     * @param string $path
     * @return bool
     */
    public function fileExists($path) {
        global $client;

        $path = cApiDbfs::stripPath($path);
        $dir = dirname($path);
        $file = basename($path);

        if ($dir == '.') {
            $dir = '';
        }

        $client = (int) $client;

        $this->select("dirname = '" . $dir . "' AND filename = '" . $file . "' AND idclient = " . $client . " LIMIT 1");
        if ($this->next()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks, if a dbfs directory exists.
     *
     * @global int $client
     * @param string $path
     * @return bool
     */
    public function dirExists($path) {
        global $client;

        $path = cApiDbfs::stripPath($path);

        if ($path == '') {
            return true;
        }

        $client = (int) $client;

        $this->select("dirname = '" . $path . "' AND filename = '.' AND idclient = " . $client . " LIMIT 1");
        if ($this->next()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param string $path
     * @return string
     */
    public function parentDir($path) {
        $path = dirname($path);

        return $path;
    }

    /**
     * Creates a dbfs item entry
     * @param string $path
     * @param string $mimetype [optional]
     * @param string $content [optional]
     * @return cApiDbfs|false
     */
    public function create($path, $mimetype = '', $content = '') {
        global $client, $auth;

        $client = (int) $client;
        $item = false;

        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }

        $dir = dirname($path);
        $file = basename($path);

        if ($dir == '.') {
            $dir = '';
        }

        if ($file == '') {
            return $item;
        }

        if ($file != '.') {
            if ($dir != '') {
                // Check if the directory exists. If not, create it.
                $this->select("dirname = '" . $dir . "' AND filename = '.' AND idclient = " . $client . " LIMIT 1");
                if (!$this->next()) {
                    $this->create($dir . '/.');
                }
            }
        } else {
            $parent = $this->parentDir($dir);

            if ($parent != '.') {
                if (!$this->dirExists($parent)) {
                    $this->create($parent . '/.');
                }
            }
        }

        if ($dir && !$this->dirExists($dir) || $file != '.') {
            $item = $this->createNewItem();
            $item->set('idclient', $client);
            $item->set('dirname', $dir);
            $item->set('filename', $file);
            $item->set('size', strlen($content));

            if ($mimetype != '') {
                $item->set('mimetype', $mimetype);
            }

            $item->set('content', $content);
            $item->set('created', date('Y-m-d H:i:s'), false);
            $item->set('author', $auth->auth['uid']);
            $item->store();
        }

        return $item;
    }

    /**
     *
     * @param string $path
     * @param string $content
     */
    public function setContent($path, $content) {
        global $client;

        $client = (int) $client;
        $path = cApiDbfs::stripPath($path);
        $dirname = dirname($path);
        $filename = basename($path);

        if ($dirname == '.') {
            $dirname = '';
        }

        $this->select("dirname = '" . $dirname . "' AND filename = '" . $filename . "' AND idclient = " . $client . " LIMIT 1");
        if (($item = $this->next()) !== false) {
            $item->set('content', $content);
            $item->set('size', strlen($content));
            $item->store();
        }
    }

    /**
     *
     * @param string $path
     * @return Ambigous <mixed, bool>
     */
    public function getSize($path) {
        global $client;

        $client = (int) $client;
        $path = cApiDbfs::stripPath($path);
        $dirname = dirname($path);
        $filename = basename($path);

        if ($dirname == '.') {
            $dirname = '';
        }

        $this->select("dirname = '" . $dirname . "' AND filename = '" . $filename . "' AND idclient = " . $client . " LIMIT 1");
        if (($item = $this->next()) !== false) {
            return $item->get('size');
        }
    }

    /**
     *
     * @param string $path
     * @return Ambigous <mixed, bool>
     */
    public function getContent($path) {
        global $client;

        $client = (int) $client;
        $dirname = dirname($path);
        $filename = basename($path);

        if ($dirname == '.') {
            $dirname = '';
        }

        $this->select("dirname = '" . $dirname . "' AND filename = '" . $filename . "' AND idclient = " . $client . " LIMIT 1");
        if (($item = $this->next()) !== false) {
            return ($item->get("content"));
        }
    }

    /**
     *
     * @param string $path
     */
    public function remove($path) {
        global $client;

        $client = (int) $client;
        $path = cApiDbfs::stripPath($path);
        $dirname = dirname($path);
        $filename = basename($path);

        if ($dirname == '.') {
            $dirname = '';
        }

        $this->select("dirname = '" . $dirname . "' AND filename = '" . $filename . "' AND idclient = " . $client . " LIMIT 1");
        if (($item = $this->next()) !== false) {
            $this->delete($item->get('iddbfs'));
        }
    }

    /**
     * Checks if time management is activated and if yes then check if file is
     * in period
     *
     * @param string $sPath
     * @param cApiPropertyCollection $oProperties
     * @return bool $bAvailable
     */
    public function checkTimeManagement($sPath, $oProperties) {
        global $contenido;
        if ($contenido) {
            return true;
        }
        $sPath = cSecurity::toString($sPath);
        $bAvailable = true;
        $iTimeMng = cSecurity::toInteger($oProperties->getValue('upload', $sPath, 'file', 'timemgmt'));
        if ($iTimeMng == 0) {
            return true;
        }
        $sStartDate = $oProperties->getValue('upload', $sPath, 'file', 'datestart');
        $sEndDate = $oProperties->getValue('upload', $sPath, 'file', 'dateend');

        $iNow = time();

        if ($iNow < $this->dateToTimestamp($sStartDate) || ($iNow > $this->dateToTimestamp($sEndDate) && (int) $this->dateToTimestamp($sEndDate) > 0)) {

            return false;
        }
        return $bAvailable;
    }

    /**
     * converts date to timestamp:
     *
     * @param string $sDate
     * @return int $iTimestamp
     */
    public function dateToTimestamp($sDate) {
        return strtotime($sDate);
    }
}

/**
 * DBFS item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiDbfs extends Item {

    /**
     * DBFS protocol
     *
     * @var string
     */
    const PROTOCOL_DBFS = 'dbfs:';

    /**
     * Constructor Function
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['dbfs'], 'iddbfs');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Stores the loaded and modified item to the database.
     * The properties "modified" & "modifiedby" are set automatically.
     *
     * @see Item::store()
     * @return bool
     */
    public function store() {
        global $auth;

        $this->set('modified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', $auth->auth['uid']);

        return parent::store();
    }

    /**
     * Sets the value of a specific field.
     * Ensures to bypass any set inFilter for 'content' field which is a blob.
     *
     * @param string $sField
     *         Field name
     * @param string $mValue
     *         Value to set
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($sField, $mValue, $bSafe = true) {
        if ('content' === $sField) {
            // Disable always filter for field 'content'
            return parent::setField($sField, $mValue, false);
        } else {
            return parent::setField($sField, $mValue, $bSafe);
        }
    }

    /**
     * User defined value getter for cApiDbfs.
     * Ensures to bypass any set outFilter for 'content' field which is a blob.
     *
     * @param string $sField
     *         Specifies the field to retrieve
     * @param bool $bSafe [optional]
     *         Flag to run defined outFilter on passed value
     * @return mixed
     *         Value of the field
     */
    public function getField($sField, $bSafe = true) {
        if ('content' === $sField) {
            // Disable always filter for field 'content'
            return parent::getField($sField, false);
        } else {
            return parent::getField($sField, $bSafe);
        }
    }

    /**
     * Removes the DBFS protocol and leading '/' from received path.
     *
     * @param string $path
     * @return string
     */
    public static function stripPath($path) {
        $path = self::stripProtocol($path);
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        return $path;
    }

    /**
     * Removes the DBFS protocol received path.
     *
     * @param string $path
     * @return string
     */
    public static function stripProtocol($path) {
        if (self::isDbfs($path)) {
            $path = substr($path, strlen(cApiDbfs::PROTOCOL_DBFS));
        }
        return $path;
    }

    /**
     * Checks if passed file id a DBFS
     *
     * @param string $file
     * @return bool
     */
    public static function isDbfs($file) {
        return (substr($file, 0, 5) == self::PROTOCOL_DBFS);
    }
}
