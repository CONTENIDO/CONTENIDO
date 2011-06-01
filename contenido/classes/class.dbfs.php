<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Database based file system
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.1.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created  2003-12-21
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2009-10-13, Dominik Ziegler, added "attachment" to Content-Disposition to force browsers downloading the file
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-06-02, Murat Purc, Fixed typo in function write()
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class DBFSCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["dbfs"], "iddbfs");
        $this->_setItemClass("DBFSItem");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function DBFSCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    public function outputFile($path)
    {
        global $client, $auth;

        $path   = Contenido_Security::escapeDB($path, null);
        $client = Contenido_Security::toInteger($client);
        $path   = $this->strip_path($path);
        $dir    = dirname($path);
        $file   = basename($path);

        if ($dir == ".") {
            $dir = "";
        }

        $this->select("dirname = '".$dir."' AND filename = '".$file."' AND idclient = '".$client."' LIMIT 1");

        if ($item = $this->next()) {
            $properties = new PropertyCollection();
            // Check if we're allowed to access it
            if ($properties->getValue("upload", "dbfs:/".$dir."/".$file, "file", "protected") == "1") {
                if ($auth->auth["uid"] == "nobody") {
                    header("HTTP/1.0 403 Forbidden");
                    return;
                }
            }
            $mimetype = $item->get("mimetype");

            header("Cache-Control: ");// leave blank to avoid IE errors
            header("Pragma: ");// leave blank to avoid IE errors
            header("Content-Type: $mimetype");
            header("Etag: ".md5(mt_rand()));

            // header("Content-Disposition: filename=$file");
            header("Content-Disposition: attachment; filename=$file");

            echo $item->get("content");
        }
    }

    public function writeFromFile($localfile, $targetfile)
    {
        $targetfile = $this->strip_path($targetfile);
        $mimetype     = mime_content_type($localfile);

        $this->write($targetfile, file_get_contents($localfile), $mimetype);
    }

    public function writeToFile($sourcefile, $localfile)
    {
        $sourcefile = $this->strip_path($sourcefile);

        file_put_contents($localfile, $this->read($sourcefile));
    }

    public function write($file, $content = "", $mimetype = "")
    {
        $file = $this->strip_path($file);

        if (!$this->file_exists($file)) {
            $this->create($file, $mimetype);
        }
        $this->setContent($file, $content);
    }

    public function hasFiles($path)
    {
        global $client;

        $path     = $this->strip_path($path);
        $client = Contenido_Security::toInteger($client);

        /* Are there any subdirs? */
        $this->select("dirname LIKE '".$path."/%' AND idclient = '".$client."' LIMIT 1");
        if ($this->count() > 0) {
            return true;
        }

        $this->select("dirname LIKE '".$path."%' AND idclient = '".$client."' LIMIT 2");

        if ($this->count() > 1) {
            return true;
        } else {
            return false;
        }
    }

    public function read($file)
    {
        return ($this->getContent($file));
    }

    public function file_exists($path)
    {
        global $client;

        $path  = $this->strip_path($path);
        $dir   = dirname($path);
        $file  = basename($path);

        if ($dir == ".") {
            $dir = "";
        }

        $client = Contenido_Security::toInteger($client);

        $this->select("dirname = '".$dir."' AND filename = '".$file."' AND idclient = '".$client."' LIMIT 1");
        if ($this->next()) {
            return true;
        } else {
            return false;
        }
    }

    public function dir_exists($path)
    {
        global $client;

        $path = $this->strip_path($path);

        if ($path == "") {
            return true;
        }

        $client = Contenido_Security::toInteger($client);

        $this->select("dirname = '".$path."' AND filename = '.' AND idclient = '".$client."' LIMIT 1");
        if ($this->next()) {
            return true;
        } else {
            return false;
        }
    }

    public function parent_dir($path)
    {
        $path = dirname($path);

        return $path;
    }

    public function create($path, $mimetype = "", $content = "")
    {
        global $client, $cfg, $auth;

        $client = Contenido_Security::toInteger($client);

        if (substr($path,0,1) == "/") {
            $path = substr($path,1);
        }

        $dir = dirname($path);
        $file = basename($path);

        if ($dir == ".") {
            $dir = "";
        }

        if ($file == "") {
            return;
        }

        if ($file != ".") {
            if ($dir != "") {
                // Check if the directory exists. If not, create it.
                $this->select("dirname = '".$dir."' AND filename = '.' AND idclient = '".$client."' LIMIT 1");
                if (!$this->next()) {
                    $this->create($dir."/.");
                }
            }
        } else {
            $parent = $this->parent_dir($dir);

            if ($parent != ".") {
                if (!$this->dir_exists($parent)) {
                    $this->create($parent."/.");
                }
            }
        }

        if ($dir && !$this->dir_exists($dir) || $file != ".") {
            $item = parent::create();
            $item->set("idclient", $client);
            $item->set("dirname", $dir);
            $item->set("filename", $file);
            $item->set("size", strlen($content));

            if ($mimetype != "") {
                $item->set("mimetype", $mimetype);
            }

            $item->set("content", $content);
            $item->set("created", date("Y-m-d H:i:s"), false);
            $item->set("author", $auth->auth["uid"]);
            $item->store();
        }
        return ($item);
    }

    public function setContent($path, $content)
    {
        global $client;

        $client    = Contenido_Security::toInteger($client);
        $path      = $this->strip_path($path);
        $dirname   = dirname($path);
        $filename  = basename($path);

        if ($dirname == ".") {
            $dirname = "";
        }

        $this->select("dirname = '".$dirname."' AND filename = '".$filename."' AND idclient = '".$client."' LIMIT 1");
        if ($item = $this->next()) {
            $item->set("content", $content);
            $item->set("size", strlen($content));
            $item->store();
        }
    }

    public function getSize($path)
    {
        global $client;

        $client   = Contenido_Security::toInteger($client);
        $path     = $this->strip_path($path);
        $dirname  = dirname($path);
        $filename = basename($path);

        if ($dirname == ".") {
            $dirname = "";
        }

        $this->select("dirname = '".$dirname."' AND filename = '".$filename."' AND idclient = '".$client."' LIMIT 1");
        if ($item = $this->next()) {
            return $item->get("size");
        }
    }

    public function getContent($path)
    {
        global $client;

        $client    = Contenido_Security::toInteger($client);
        $dirname   = dirname($path);
        $filename  = basename($path);

        if ($dirname == ".") {
            $dirname = "";
        }

        $this->select("dirname = '".$dirname."' AND filename = '".$filename."' AND idclient = '".$client."' LIMIT 1");
        if ($item = $this->next()) {
            return ($item->get("content"));
        }
    }

    public function remove($path)
    {
        global $client;

        $client   = Contenido_Security::toInteger($client);
        $path     = $this->strip_path($path);
        $dirname  = dirname($path);
        $filename = basename($path);

        if ($dirname == ".") {
            $dirname = "";
        }

        $this->select("dirname = '".$dirname."' AND filename = '".$filename."' AND idclient = '".$client."' LIMIT 1");
        if ($item = $this->next()) {
            $this->delete($item->get("iddbfs"));
        }
    }

    public function strip_path($path)
    {
        if (substr($path,0,5) == "dbfs:") {
            $path = substr($path,5);
        }

        if (substr($path,0,1) == "/") {
            $path = substr($path,1);
        }

        return $path;
    }

    /**
     * checks if time management is activated and if yes then check if file is in period
     * @param datatype $sPath
     * @return bool $bAvailable
     */
    public function checkTimeManagement($sPath, $oProperties)
    {
        global $contenido;
        if ($contenido) {
            return true;
        }
        $sPath      = Contenido_Security::toString($sPath);
        $bAvailable = true;
        $iTimeMng   = Contenido_Security::toInteger($oProperties->getValue("upload", $sPath, "file", "timemgmt"));
        if ($iTimeMng == 0) {
            return true;
        }
        $sStartDate = $oProperties->getValue("upload", $sPath, "file", "datestart");
        $sEndDate = $oProperties->getValue("upload", $sPath, "file", "dateend");

        $iNow = time();

        if ($iNow < $this->dateToTimestamp($sStartDate) ||
            ($iNow > $this->dateToTimestamp($sEndDate) && (int)$this->dateToTimestamp($sEndDate) > 0)) {

            return false;
        }
        return $bAvailable;
    }

    /**
     * converts date to timestamp:
     * @param string $sDate
     * @return int $iTimestamp
     */
    public function dateToTimestamp($sDate)
    {
        return strtotime($sDate);
    }
}


class DBFSItem extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["dbfs"], "iddbfs");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function DBFSItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    public function store()
    {
        global $auth;

        $this->set("modified", date("Y-m-d H:i:s"), false);
        $this->set("modifiedby", $auth->auth["uid"]);

        parent::store();
    }

    public function setField($field, $value, $safe = true)
    {
        if ($field == "dirname" || $field == "filename" || $field == "mimetype") {
            // Don't do safe encoding
            $safe = false;

            $value = str_replace("'", "", $value);
            $value = str_replace('"', "", $value);
        }

        parent::setField($field, $value, $safe);
    }
}

?>