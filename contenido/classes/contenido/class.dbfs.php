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
 * @extends ItemCollection<cApiDbfs>
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
        parent::__construct(cDb::getTableName('dbfs'), 'iddbfs');
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
        $path = cApiDbfs::stripPath($path);
        $dirname = $this->getSanitizedDirname($path);
        $filename = basename($path);

        $dbfs = $this->fetchOneByDirnameFilenameAndClientId($dirname, $filename, cRegistry::getClientId());
        if ($dbfs) {
            $properties = new cApiPropertyCollection();
            // Check if we're allowed to access it
            $itemId = cApiDbfs::PROTOCOL_DBFS . $dirname . '/' . $filename;
            if ($properties->getValue('upload', $itemId, 'file', 'protected') == '1') {
                if (cRegistry::getAuth()->getUserId() === cAuth::AUTH_UID_NOBODY) {
                    header('HTTP/1.0 403 Forbidden');
                    return;
                }
            }
            $mimetype = $dbfs->get('mimetype');

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
                header('Content-Disposition: attachment; filename=' . $filename);
            }

            echo $dbfs->get('content');
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
     * @param string $content [optional]
     * @param string $mimetype [optional]
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function write(string $filename, $content = '', $mimetype = '')
    {
        $filename = cApiDbfs::stripPath($filename);

        if (!$this->fileExists($filename)) {
            $this->create($filename, $mimetype);
        }
        $this->setContent($filename, $content);
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
        $where = $this->db->prepare(
            "(`dirname` LIKE ':dir_name/%' AND `idclient` = :client_id) OR "
            . "(`dirname` = ':dir_name' AND `idclient` = :client_id AND `filename` != '' AND `filename` != '.') "
            . " LIMIT 1",
            ['dir_name' => $path, 'client_id' => $client]
        );
        $this->select($where);

        return $this->count() > 0;
    }

    /**
     * Reads content from dbfs file.
     *
     * @return string|mixed|false
     * @throws cDbException|cException
     */
    public function read(string $filename)
    {
        return $this->getContent($filename);
    }

    /**
     * Checks if a dbfs file exists.
     *
     * @throws cDbException|cException
     */
    public function fileExists(string $path): bool
    {
        $path = cApiDbfs::stripPath($path);
        $dirname = $this->getSanitizedDirname($path);
        $filename = basename($path);

        $dbfs = $this->fetchOneByDirnameFilenameAndClientId($dirname, $filename, cRegistry::getClientId());

        return $dbfs instanceof cApiDbfs;
    }

    /**
     * Checks if a dbfs directory exists.
     *
     * @throws cDbException|cException
     */
    public function dirExists(string $path): bool
    {
        $dirname = cApiDbfs::stripPath($path);
        if ($dirname == '') {
            return true;
        }

        $dbfs = $this->fetchOneByDirnameFilenameAndClientId($dirname, '.', cRegistry::getClientId());

        return $dbfs instanceof cApiDbfs;
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

        $dirname = $this->getSanitizedDirname($path);

        $filename = basename($path);
        if ($filename == '') {
            return false;
        }

        if ($filename != '.') {
            if ($dirname != '') {
                // Check if the directory exists. If not, create it.
                $dbfs = $this->fetchOneByDirnameFilenameAndClientId($dirname, '.', $client);
                if (!$dbfs instanceof cApiDbfs) {
                    $this->create($dirname . '/.');
                }
            }
        } else {
            $parent = $this->parentDir($dirname);

            if ($parent != '.') {
                if (!$this->dirExists($parent)) {
                    $this->create($parent . '/.');
                }
            }
        }

        $item = false;
        if ($dirname && !$this->dirExists($dirname) || $filename != '.') {
            $item = $this->createNewItem();
            $item->set('idclient', $client);
            $item->set('dirname', $dirname);
            $item->set('filename', $filename);
            $item->set('size', cString::getStringLength($content));

            if ($mimetype != '') {
                $item->set('mimetype', $mimetype);
            }

            $auth = cRegistry::getAuth();
            $item->set('content', $content);
            $item->set('created', date('Y-m-d H:i:s'), false);
            $item->set('author', $auth->getUserId());
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
        $path = cApiDbfs::stripPath($path);
        $dirname = $this->getSanitizedDirname($path);
        $filename = basename($path);

        $dbfs = $this->fetchOneByDirnameFilenameAndClientId($dirname, $filename, cRegistry::getClientId());
        if ($dbfs instanceof cApiDbfs) {
            $dbfs->set('content', $content);
            $dbfs->set('size', cString::getStringLength($content));
            $dbfs->store();
        }
    }

    /**
     * @throws cDbException|cException
     */
    public function getSize(string $path): int
    {
        $path = cApiDbfs::stripPath($path);
        $dirname = $this->getSanitizedDirname($path);
        $filename = basename($path);

        $dbfs = $this->fetchOneByDirnameFilenameAndClientId($dirname, $filename, cRegistry::getClientId());

        return $dbfs ? cSecurity::toInteger($dbfs->get('size')) : 0;
    }

    /**
     * Get content of path for current client.
     *
     * @return string|mixed|false
     * @throws cDbException|cException
     */
    public function getContent(string $path)
    {
        $dirname = $this->getSanitizedDirname($path);
        $filename = basename($path);

        $dbfs = $this->fetchOneByDirnameFilenameAndClientId($dirname, $filename, cRegistry::getClientId());

        return $dbfs ? $dbfs->get('content') : false;
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
        $path = cApiDbfs::stripPath($path);
        $dirname = $this->getSanitizedDirname($path);
        $filename = basename($path);

        $dbfs = $this->fetchOneByDirnameFilenameAndClientId($dirname, $filename, cRegistry::getClientId());

        return $dbfs && $this->delete($dbfs->get('iddbfs'));
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
        $timeManagement = cSecurity::toInteger($properties->getValue('upload', $path, 'file', 'timemgmt'));
        if ($timeManagement == 0) {
            return true;
        }

        $startDate = $properties->getValue('upload', $path, 'file', 'datestart');
        $endDate = $properties->getValue('upload', $path, 'file', 'dateend');
        $iNow = time();
        if (
            $iNow < $this->dateToTimestamp($startDate)
            || ($iNow > $this->dateToTimestamp($endDate) && (int)$this->dateToTimestamp($endDate) > 0)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Converts date to timestamp:
     *
     * @return int|false Timestamp
     */
    public function dateToTimestamp(string $date)
    {
        return strtotime($date);
    }

    /**
     * @since CONTENIDO 4.10.2
     */
    private function getSanitizedDirname(string $path): string
    {
        $dirname = dirname($path);
        if ($dirname === '.') {
            $dirname = '';
        }

        return $dirname;
    }

    /**
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    private function fetchOneByDirnameFilenameAndClientId(string $dirname, string $filename, int $clientId): ?cApiDbfs
    {
        $this->select(sprintf(
            "`dirname` = '%s' AND `filename` = '%s' AND `idclient` = %d LIMIT 1",
            $this->db->escape($dirname),
            $this->db->escape($filename),
            $clientId
        ));

        if (($item = $this->next()) !== false) {
            return $item;
        }
        return null;
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
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('dbfs'), 'iddbfs');
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
        $this->set('modifiedby', $auth->getUserId());

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
    public static function isDbfs(string $filename): bool
    {
        return cString::getPartOfString($filename, 0, 5) == self::PROTOCOL_DBFS;
    }
}
