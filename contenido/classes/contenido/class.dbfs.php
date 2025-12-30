<?php

/**
 * This file contains the DBFS collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.file.php');

/**
 * DBFS item collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiDbfs createNewItem
 * @method cApiDbfs|bool next
 */
class cApiDbfsCollection extends ItemCollection
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
        parent::__construct(cRegistry::getDbTableName('dbfs'), 'iddbfs');
        $this->_setItemClass('cApiDbfs');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Outputs dbfs file related by its path property
     *
     * @throws cDbException|cException
     */
    public function outputFile(string $path)
    {
        $path = $this->escape($path);
        $client = cRegistry::getClientId();
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
                $auth = cRegistry::getAuth();
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
            $cfg = cRegistry::getConfig();
            foreach ($cfg['dbfs']['skip_content_disposition_header_for_mimetypes'] as $mt) {
                if (cString::toLowerCase($mt) == cString::toLowerCase($mimetype)) {
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
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function writeFromFile(string $localFile, string $targetFile)
    {
        $targetFile = cApiDbfs::stripPath($targetFile);
        $stat = cFileHandler::info($localFile);
        $mimetype = $stat['mime'];

        $this->write($targetFile, cFileHandler::read($localFile), $mimetype);
    }

    /**
     * Writes dbfs file into physical file system
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function writeToFile(string $sourceFile, string $localFile)
    {
        $sourceFile = cApiDbfs::stripPath($sourceFile);

        cFileHandler::write($localFile, $this->read($sourceFile));
    }

    /**
     * Writes dbfs file, creates if if not exists.
     *
     * @param string $file
     * @param string $content [optional]
     * @param string $mimetype [optional]
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function write(string $file, $content = '', $mimetype = '')
    {
        $file = cApiDbfs::stripPath($file);

        if (!$this->fileExists($file)) {
            $this->create($file, $mimetype);
        }
        $this->setContent($file, $content);
    }

    /**
     * Checks if passed dbfs path has any files.
     *
     * @throws cDbException
     */
    public function hasFiles(string $path): bool
    {
        $client = cRegistry::getClientId();
        $path = cApiDbfs::stripPath($path);

        // Are there any subdirectories or any files?
        $this->select(
            "(`dirname` LIKE '" . $path . "/%' AND `idclient` = " . $client . ") OR " .
            "(`dirname` = '" . $path . "' AND `idclient` = " . $client . " AND `filename` != '' AND `filename` != '.') " .
            " LIMIT 1"
        );

        return $this->count() > 0;
    }

    /**
     * Reads content from dbfs file.
     *
     * @return string|mixed|false
     * @throws cDbException|cException
     */
    public function read(string $file)
    {
        return $this->getContent($file);
    }

    /**
     * Checks, if a dbfs file exists.
     *
     * @throws cDbException|cException
     */
    public function fileExists(string $path): bool
    {
        $client = cRegistry::getClientId();
        $path = cApiDbfs::stripPath($path);
        $dir = dirname($path);
        $file = basename($path);

        if ($dir == '.') {
            $dir = '';
        }

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
     * @throws cDbException|cException
     */
    public function dirExists(string $path): bool
    {
        $client = cRegistry::getClientId();
        $path = cApiDbfs::stripPath($path);

        if ($path == '') {
            return true;
        }

        $this->select("dirname = '" . $path . "' AND filename = '.' AND idclient = " . $client . " LIMIT 1");
        if ($this->next()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns parent directory name.
     */
    public function parentDir(string $path): string
    {
        return dirname($path);
    }

    /**
     * Creates a dbfs item entry
     * @param string $path
     * @param string $mimetype [optional]
     * @param string $content [optional]
     * @return cApiDbfs|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($path, $mimetype = '', $content = '')
    {
        $client = cRegistry::getClientId();

        if (cString::getPartOfString($path, 0, 1) == '/') {
            $path = cString::getPartOfString($path, 1);
        }

        $dir = dirname($path);
        $file = basename($path);

        if ($dir == '.') {
            $dir = '';
        }

        if ($file == '') {
            return false;
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

        $item = false;
        if ($dir && !$this->dirExists($dir) || $file != '.') {
            $item = $this->createNewItem();
            $item->set('idclient', $client);
            $item->set('dirname', $dir);
            $item->set('filename', $file);
            $item->set('size', cString::getStringLength($content));

            if ($mimetype != '') {
                $item->set('mimetype', $mimetype);
            }

            $auth = cRegistry::getAuth();
            $item->set('content', $content);
            $item->set('created', date('Y-m-d H:i:s'), false);
            $item->set('author', $auth->auth['uid']);
            $item->store();
        }

        return $item;
    }

    /**
     * @param string $path
     * @param string $content
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setContent(string $path, $content)
    {
        $client = cRegistry::getClientId();
        $path = cApiDbfs::stripPath($path);
        $dirname = dirname($path);
        $filename = basename($path);

        if ($dirname == '.') {
            $dirname = '';
        }

        $this->select("dirname = '" . $dirname . "' AND filename = '" . $filename . "' AND idclient = " . $client . " LIMIT 1");
        if (($item = $this->next()) !== false) {
            $item->set('content', $content);
            $item->set('size', cString::getStringLength($content));
            $item->store();
        }
    }

    /**
     * @throws cDbException|cException
     */
    public function getSize(string $path): int
    {
        $client = cRegistry::getClientId();
        $path = cApiDbfs::stripPath($path);
        $dirname = dirname($path);
        $filename = basename($path);

        if ($dirname == '.') {
            $dirname = '';
        }

        $this->select("dirname = '" . $dirname . "' AND filename = '" . $filename . "' AND idclient = " . $client . " LIMIT 1");
        if (($item = $this->next()) !== false) {
            return cSecurity::toInteger($item->get('size'));
        }

        return 0;
    }

    /**
     * Get content of path for current client.
     *
     * @return string|mixed|false
     * @throws cDbException|cException
     */
    public function getContent(string $path)
    {
        $client = cRegistry::getClientId();
        $dirname = dirname($path);
        $filename = basename($path);

        if ($dirname == '.') {
            $dirname = '';
        }

        $this->select("dirname = '" . $dirname . "' AND filename = '" . $filename . "' AND idclient = " . $client . " LIMIT 1");
        if (($item = $this->next()) !== false) {
            return $item->get("content");
        }

        return false;
    }

    /**
     * remove content of path for current client.
     *
     * @param string $path
     * @return bool Success state
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function remove(string $path): bool
    {
        $client = cRegistry::getClientId();
        $path = cApiDbfs::stripPath($path);
        $dirname = dirname($path);
        $filename = basename($path);

        if ($dirname == '.') {
            $dirname = '';
        }

        $this->select("dirname = '" . $dirname . "' AND filename = '" . $filename . "' AND idclient = " . $client . " LIMIT 1");
        if (($item = $this->next()) !== false) {
            return $this->delete($item->get('iddbfs'));
        }
        return false;
    }

    /**
     * Checks if time management is activated and if yes then check if file is in period
     *
     * @throws cDbException|cException
     */
    public function checkTimeManagement(string $path, cApiPropertyCollection $properties): bool
    {
        if (cRegistry::getBackendSessionId()) {
            return true;
        }

        $path = cSecurity::toString($path);
        $iTimeMng = cSecurity::toInteger($properties->getValue('upload', $path, 'file', 'timemgmt'));
        if ($iTimeMng == 0) {
            return true;
        }

        $sStartDate = $properties->getValue('upload', $path, 'file', 'datestart');
        $sEndDate = $properties->getValue('upload', $path, 'file', 'dateend');
        $iNow = time();
        if ($iNow < $this->dateToTimestamp($sStartDate) || ($iNow > $this->dateToTimestamp($sEndDate) && (int)$this->dateToTimestamp($sEndDate) > 0)) {
            return false;
        }

        return true;
    }

    /**
     * Converts date to timestamp:
     *
     * @return int|false Timestamp
     */
    public function dateToTimestamp(string $sDate)
    {
        return strtotime($sDate);
    }
}

/**
 * DBFS item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiDbfs extends Item
{

    /**
     * DBFS protocol
     *
     * @var string
     */
    public const PROTOCOL_DBFS = 'dbfs:';

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cRegistry::getDbTableName('dbfs'), 'iddbfs');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Stores the loaded and modified item to the database.
     * The properties "modified" & "modifiedby" are set automatically.
     *
     * @inheritDoc
     */
    public function store()
    {
        $auth = cRegistry::getAuth();

        $this->set('modified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', $auth->auth['uid']);

        return parent::store();
    }

    /**
     * Sets the value of a specific field.
     * Ensures to bypass any set inFilter for 'content' field which is a blob.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        if ('content' === $name) {
            // Disable always filter for field 'content'
            return parent::setField($name, $value, false);
        } else {
            return parent::setField($name, $value, $safe);
        }
    }

    /**
     * User defined value getter for cApiDbfs.
     * Ensures to bypass any set outFilter for 'content' field which is a blob.
     *
     * @inheritDoc
     */
    public function getField($name, $safe = true)
    {
        if ('content' === $name) {
            // Disable always filter for field 'content'
            return parent::getField($name, false);
        } else {
            return parent::getField($name, $safe);
        }
    }

    /**
     * Removes the DBFS protocol and leading '/' from received path.
     */
    public static function stripPath(string $path): string
    {
        $path = self::stripProtocol($path);
        if (cString::getPartOfString($path, 0, 1) == '/') {
            $path = cString::getPartOfString($path, 1);
        }
        return $path;
    }

    /**
     * Removes the DBFS protocol received path.
     */
    public static function stripProtocol(string $path): string
    {
        if (self::isDbfs($path)) {
            $path = cString::getPartOfString($path, cString::getStringLength(cApiDbfs::PROTOCOL_DBFS));
        }
        return $path;
    }

    /**
     * Checks if passed file id a DBFS
     */
    public static function isDbfs(string $file): bool
    {
        return cString::getPartOfString($file, 0, 5) == self::PROTOCOL_DBFS;
    }
}
