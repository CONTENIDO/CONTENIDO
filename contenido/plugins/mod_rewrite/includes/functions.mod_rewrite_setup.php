<?php
/**
 * Installer for Advanced Mod Rewrite Plugin, used by plugin setup.
 *
 * Some features are taken over from initial functions.mod_rewrite_setup.php file beeing created by
 * Stefan Seifarth.
 *
 * @todo: Remove code into an class file....
 *
 * @author      Murat Purc <murat@purc.de>
 * @copyright   © ww.purc.de
 * @package     Contenido
 * @subpackage  ModRewrite
 */


defined('CON_FRAMEWORK') or die('Illegal call');

error_reporting (E_ALL ^ E_NOTICE);


/**
 * Plugin setup class
 *
 * @todo should extend PluginSetupAbstract and implement IPluginSetup
 *
 * @author      Murat Purc (murat@purc.de)
 * @package     Contenido
 * @subpackage  PluginInstaller
 */
class PluginSetup {

    private static $_bInitialized = false;

    private static $_cfg;

    private static $_db;


    public static function initialize(){
        if (self::$_bInitialized == true) {
            return;
        }
        self::$_cfg = $GLOBALS['cfg'];
        self::$_db  = new DB_Contenido();
        self::$_bInitialized = true;
    }


    /**
     * Install plugin
     *
     * Handle upgrading of mod rewrite needed database table columns
     */
    public static function install(){

        self::initialize();

        // check the existance of art_lang.urlname
        $sql = "SELECT urlname FROM " . self::$_cfg['tab']['art_lang'] . " LIMIT 0,1";
        self::$_db->query($sql);
        if (!self::$_db->next_record()) {
            // add field 'urlname' to table
            $sql = "ALTER TABLE " . self::$_cfg['tab']['art_lang'] . " ADD urlname VARCHAR( 128 ) AFTER title";
            self::$_db->query($sql);
        }

        // check the existance of cat_lang.urlpath
        $sql = "SELECT urlpath FROM " . self::$_cfg['tab']['cat_lang'] . " LIMIT 0,1";
        self::$_db->query($sql);
        if (!self::$_db->next_record()) {
            // add field 'urlpath' to table
            $sql = "ALTER TABLE " . self::$_cfg['tab']['cat_lang'] . " ADD urlpath VARCHAR( 255 ) AFTER urlname";
            self::$_db->query($sql);
        }

        // check for empty article fields
        $sql = "SELECT idlang, title, idart FROM " . self::$_cfg['tab']['art_lang'] . " WHERE urlname IS NULL OR urlname = ''";
        self::$_db->query($sql);
        while (self::$_db->next_record()) {
            self::_setArticle(self::$_db->f('title'), self::$_db->f('idart'), self::$_db->f('idlang'));
        }

        // check for empty category urlname
        $sql = "SELECT name, idcat, idlang FROM " . self::$_cfg['tab']['cat_lang'] . " WHERE urlname IS NULL OR urlname = ''";
        self::$_db->query($sql);
        while (self::$_db->next_record()) {
            self::_setCategory(self::$_db->f('name'), self::$_db->f('idcat'), self::$_db->f('idlang'));
        }

        // check for empty category urlpath
        $sql = "SELECT name, idcat, idlang FROM " . self::$_cfg['tab']['cat_lang'] . " WHERE urlpath IS NULL OR urlpath = ''";
        self::$_db->query($sql);
        while (self::$_db->next_record()) {
            self::_setCategoryPath(self::$_db->f('idcat'), self::$_db->f('idlang'));
        }
    }


    /**
     * Upgrade plugin
     *
     * Handle upgrading of mod rewrite needed database table columns
     */
    public static function upgrade(){
        self::install();
    }


    /**
     * Delete plugin
     *
     * Removed done changes to database during installation/upgrade process
     */
    public static function uninstall() {
        self::initialize();
        // remove field 'urlpath' from 'cat_lang' table
        $sql = "ALTER TABLE " . self::$_cfg['tab']['cat_lang'] . " DROP urlpath";
        self::$_db->query($sql);
    }


    /**
     * Set websafe name in article list
     *
     * insert new websafe name in article list
     *
     * @param   string  original name (will be converted)
     * @param   integer current article id
     * @param   integer current language id
     * @return  boolean true if insert was successfully
     */
    private static function _setArticle($sName="", $iArtId=0, $iLangId=0, $iCatId=0) {
        static $db;

        if (!isset($db)) {
            $db = new DB_Contenido();
        }

        // create websafe name
        $sNewName = capiStrCleanURLCharacters($sName);

        // check if websafe name already exists
        if (self::_inArticles($sNewName, $iArtId, $iLangId, $iCatId)) {
            // create new websafe name if exists
            $sNewName = capiStrCleanURLCharacters($sName) . '_' . $iArtId;
        }

        // check again - and set name
        if (!self::_inArticles($sNewName, $iArtId, $iLangId, $iCatId)) {
            // insert websafe name in article list
            $sql = "UPDATE " . self::$_cfg['tab']['art_lang'] . " SET urlname = '" . $sNewName . "' WHERE idart = '" . $iArtId . "' AND idlang = '" . $iLangId . "'";
            return $db->query($sql);
        } else {
            return false;
        }
    }


    /**
     * Set websafe name in category list
     *
     * insert new websafe name in category list
     *
     * @param   string  original name (will be converted)
     * @param   integer current article id
     * @param   integer current language id
     * @return  boolean true if insert was successfully
     */
    private static function _setCategory($sName='', $iCatId=0, $iLangId=0) {
        static $db;

        if (!isset($db)) {
            $db = new DB_Contenido();
        }

        // create websafe name
        $sNewName = capiStrCleanURLCharacters($sName);

        // check if websafe name already exists
        if (self::_inCategory($sNewName, $iCatId, $iLangId)) {
            // create new websafe name if exists
            $sNewName = capiStrCleanURLCharacters($sName) . '_' . $iCatId;
        }

        // check again - and set name
        if (!self::_inCategory($sNewName, $iCatId, $iLangId)) {
            // insert websafe name in article list
            $sql = "UPDATE " . self::$_cfg['tab']['cat_lang'] . " SET urlname = '$sNewName' WHERE idcat = '$iCatId' AND idlang = '$iLangId'";
            return $db->query($sql);
        } else {
            return false;
        }
    }


    /**
     * Build and set recursiv path for mod_rewrite rule like server directories
     * (dir1/dir2/dir3)
     *
     * @param   int     $iCatId   Latest category id
     * @param   int     $iLangId  Language id
     * @param   int     $iLastId  Last category id
     * @return 	string	linkpath with correct uri
     */
    private static function _setCategoryPath($iCatId=0, $iLangId=0, $iLastId=0) {
        static $db;

        if (!isset($db)) {
            $db = new DB_Contenido();
        }

        $aDirs     = array();
        $bFinish   = false;
        $iTmpCatId = $iCatId;

        while ($bFinish == false) {
            $sql = "SELECT cl.urlname, c.parentid FROM " . self::$_cfg['tab']['cat_lang'] . " cl "
                 . "LEFT JOIN " . self::$_cfg['tab']['cat'] . " c ON cl.idcat = c.idcat "
                 . "WHERE cl.idcat = '$iTmpCatId' AND cl.idlang = '$iLangId'";
            $db->query($sql);
            if ($db->next_record()) {
                $aDirs[]   = $db->f('urlname');
                $iTmpCatId = $db->f('parentid');

                if ($db->f('parentid') == 0 || $db->f('parentid') == $iLastId) {
                    $bFinish = true;
                }
            } else {
                $bFinish = true;
            }
        }

        // reverse array entries and create directory string
        $sPath = join('/', array_reverse($aDirs));

        // insert urlpath for category
        $sql = "UPDATE " . self::$_cfg['tab']['cat_lang'] . " SET urlpath = '$sPath' WHERE idcat = '$iCatId' AND idlang = '$iLangId'";
        return $db->query($sql);
    }


    /**
     * Check articles on websafe name
     *
     * Check all articles in the current category on existing same websafe name
     *
     * @param   string  Websafe name to check
     * @param   integer current article id
     * @param   integer current language id
     * @param   integer current category id
     * @return  boolean true if websafename already exists, false if not
     */
    private static function _inArticles($sName='', $iArtId=0, $iLangId=0, $iCatId=0) {
        static $db;

        if (!isset($db)) {
            $db = new DB_Contenido();
        }

        $iCatId = (int) $iCatId;

        // handle multipages
        if ($iCatId == 0) {
            // get category id if not set
            $sql = "SELECT idcat FROM " . self::$_cfg['tab']['cat_art'] . " WHERE idart = '$iArtId'";
            $db->query($sql);
            $db->next_record();
            $iCatId = ($db->f('idcat') > 0) ? $db->f('idcat') : '0';
        }

        $sWhere = " ca.idcat = '$iCatId' AND al.idlang = '" . $iLangId . "' AND"
                . " LOWER(al.urlname) = LOWER('" . $sName . "') AND al.idart <> '$iArtId'";

        // check if websafe name is in this category
        $sql = "SELECT count(al.idart) as numcats FROM " . self::$_cfg['tab']['art_lang'] . " al LEFT JOIN " . self::$_cfg['tab']['cat_art'] . " ca ON al.idart = ca.idart WHERE " . $sWhere;
        $db->query($sql);
        $db->next_record();

        return ($db->f('numcats') > 0) ? true : false;
    }


    /**
     * Check categories on websafe name
     *
     * Check all categories in the main parent category on existing same websafe name
     *
     * @param   string  Websafe name to check
     * @param   integer current category id
     * @param   integer current language id
     * @return  boolean true if websafename already exists, false if not
     */
    private static function _inCategory($sName='', $iCatId=0, $iLangId=0) {
        static $db;

        if (!isset($db)) {
            $db = new DB_Contenido();
        }

        // get parentid
        $sql = "SELECT parentid FROM " . self::$_cfg['tab']['cat'] . " WHERE idcat = '$iCatId'";
        $db->query($sql);
        $db->next_record();
        $iParentId = ($db->f('parentid') > 0) ? $db->f('parentid') : '0';

        $sWhere = " c.parentid = '$iParentId' AND cl.idlang = '" . $iLangId . "' AND"
                . " LOWER(cl.urlname) = LOWER('" . $sName . "') AND cl.idcat <> '$iCatId'";

        // check if websafe name is in this category
        $sql = "SELECT count(cl.idcat) as numcats FROM " . self::$_cfg['tab']['cat_lang'] . " cl LEFT JOIN " . self::$_cfg['tab']['cat'] . " c ON cl.idcat = c.idcat WHERE " . $sWhere;
        $db->query($sql);
        $db->next_record();

        return ($db->f('numcats') > 0) ? true : false;
    }

}
