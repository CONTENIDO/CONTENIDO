<?php
/**
 * Installer for Advanced Mod Rewrite Plugin, used by plugin setup.
 *
 * Some features are taken over from initial functions.mod_rewrite_setup.php file beeing created by
 * Stefan Seifarth.
 *
 * @author      Murat Purc <murat@purc.de>
 * @copyright   © ww.purc.de
 * @package     Contenido
 * @subpackage  ModRewrite
 */


defined('CON_FRAMEWORK') or die('Illegal call');

if (!class_exists('PluginSetupAbstract')) {
    throw new Exception('ModRewriteInstaller: Base class "PluginSetupAbstract" doesn\'t exists, classfile must be included before.');
}


/**
 * Installer for Advanced Mod Rewrite Plugin, used by plugin setup.
 *
 * Some features are taken over from initial functions.mod_rewrite_setup.php file beeing created by
 * Stefan Seifarth (aka stese).
 *
 * @author      Murat Purc <murat@purc.de>
 * @copyright   © ww.purc.de
 * @package     Contenido
 * @subpackage  ModRewrite
 */
class ModRewriteInstaller extends PluginSetupAbstract implements IPluginSetup {

    /**
     * Constructor, initializes parent.
     */
    public function _construct(){
        parent::_construct();
    }


    /**
     * Installs the plugin, interface function implementation.
     *
     * Handle upgrading of mod rewrite needed database table columns
     */
    public function install(){
        // check the existance of art_lang.urlname
        $sql = "SELECT * FROM " . $this->_cfg['tab']['art_lang'] . " LIMIT 0,1";
        $this->_db->query($sql);
        if (!$this->_db->next_record() || !$this->_db->f('urlname')) {
            // add field 'urlname' to table
            $sql = "ALTER TABLE " . $this->_cfg['tab']['art_lang'] . " ADD urlname VARCHAR( 128 ) AFTER title";
            $this->_db->query($sql);
        }

        // check the existance of cat_lang.urlpath
        $sql = "SELECT * FROM " . $this->_cfg['tab']['cat_lang'] . " LIMIT 0,1";
        $this->_db->query($sql);
        if (!$this->_db->next_record() || !$this->_db->f('urlpath')) {
            // add field 'urlpath' to table
            $sql = "ALTER TABLE " . $this->_cfg['tab']['cat_lang'] . " ADD urlpath VARCHAR( 255 ) AFTER urlname";
            $this->_db->query($sql);
        }

        // check for empty article fields
        $sql = "SELECT idlang, title, idart FROM " . $this->_cfg['tab']['art_lang'] . " WHERE urlname IS NULL OR urlname = ''";
        $this->_db->query($sql);
        while ($this->_db->next_record()) {
            $this->_setArticle($this->_db->f('title'), $this->_db->f('idart'), $this->_db->f('idlang'));
        }

        // check for empty category urlname
        $sql = "SELECT name, idcat, idlang FROM " . $this->_cfg['tab']['cat_lang'] . " WHERE urlname IS NULL OR urlname = ''";
        $this->_db->query($sql);
        while ($this->_db->next_record()) {
            $this->_setCategory($this->_db->f('name'), $this->_db->f('idcat'), $this->_db->f('idlang'));
        }

        // check for empty category urlpath
        $sql = "SELECT name, idcat, idlang FROM " . $this->_cfg['tab']['cat_lang'] . " WHERE urlpath IS NULL OR urlpath = ''";
        $this->_db->query($sql);
        while ($this->_db->next_record()) {
            $this->_setCategoryPath($this->_db->f('idcat'), $this->_db->f('idlang'));
        }
    }


    /**
     * Upgrade plugin, interface function implementation.
     *
     * Handle upgrading of mod rewrite needed database table columns
     */
    public function upgrade(){
        $this->install();

        // delete some death recordsets remained from old AMR versions!

        // get old entries from {prefix}_files table
        $sql = "SELECT idfile FROM " . $this->_cfg['tab']['files'] . " "
             . "WHERE filename = 'mod_rewrite/includes/include.mod_rewrite_menu_top.php' OR "
             . "filename = 'mod_rewrite/includes/include.mod_rewrite_menu.php'";
        $this->_db->query($sql);
        $fileIds = array();
        while ($this->_db->next_record()) {
            $fileIds[] = (int) $this->_db->f('idfile');
        }

        foreach ($fileIds as $p => $id) {
            // delete old entries from {prefix}_frame_files table
            $sql = "DELETE FROM " . $this->_cfg['tab']['frame_files'] . " WHERE idfile = " . $id;
            $this->_db->query($sql);

            // delete old entries from {prefix}_files table
            $sql = "DELETE FROM " . $this->_cfg['tab']['files'] . " WHERE idfile = " . $id;
            $this->_db->query($sql);
        }
    }


    /**
     * Delete plugin, interface function implementation.
     *
     * Handle deleteting of mod rewrite needed database table columns
     */
    public function uninstall() {
        // remove field 'urlpath' from 'cat_lang' table
        $sql = "ALTER TABLE " . $this->_cfg['tab']['cat_lang'] . " DROP urlpath";
        $this->_db->query($sql);
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
    private function _setArticle($sName="", $iArtId=0, $iLangId=0, $iCatId=0) {
        static $db;
        if (!isset($db)) {
            $db = new DB_Contenido();
        }

        // create websafe name
        $sNewName = capiStrCleanURLCharacters($sName);

        // check if websafe name already exists
        if ($this->_inArticles($sNewName, $iArtId, $iLangId, $iCatId)) {
            // create new websafe name if exists
            $sNewName = capiStrCleanURLCharacters($sName) . '_' . $iArtId;
        }

        // check again - and set name
        if (!$this->_inArticles($sNewName, $iArtId, $iLangId, $iCatId)) {
            // insert websafe name in article list
            $sql = "UPDATE " . $this->_cfg['tab']['art_lang'] . " SET urlname = '" . $sNewName . "' WHERE idart = '" . $iArtId . "' AND idlang = '" . $iLangId . "'";
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
    private function _setCategory($sName='', $iCatId=0, $iLangId=0) {
        static $db;
        if (!isset($db)) {
            $db = new DB_Contenido();
        }

        // create websafe name
        $sNewName = capiStrCleanURLCharacters($sName);

        // check if websafe name already exists
        if ($this->_inCategory($sNewName, $iCatId, $iLangId)) {
            // create new websafe name if exists
            $sNewName = capiStrCleanURLCharacters($sName) . '_' . $iCatId;
        }

        // check again - and set name
        if (!$this->_inCategory($sNewName, $iCatId, $iLangId)) {
            // insert websafe name in article list
            $sql = "UPDATE " . $this->_cfg['tab']['cat_lang'] . " SET urlname = '$sNewName' WHERE idcat = '$iCatId' AND idlang = '$iLangId'";
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
    private function _setCategoryPath($iCatId=0, $iLangId=0, $iLastId=0) {
        static $db;
        if (!isset($db)) {
            $db = new DB_Contenido();
        }

        $aDirs     = array();
        $bFinish   = false;
        $iTmpCatId = $iCatId;

        while ($bFinish == false) {
            $sql = "SELECT cl.urlname, c.parentid FROM " . $this->_cfg['tab']['cat_lang'] . " cl "
                 . "LEFT JOIN " . $this->_cfg['tab']['cat'] . " c ON cl.idcat = c.idcat "
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
        $sql = "UPDATE " . $this->_cfg['tab']['cat_lang'] . " SET urlpath = '$sPath' WHERE idcat = '$iCatId' AND idlang = '$iLangId'";
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
    private function _inArticles($sName='', $iArtId=0, $iLangId=0, $iCatId=0) {
        static $db;
        if (!isset($db)) {
            $db = new DB_Contenido();
        }

        $iCatId = (int) $iCatId;

        // handle multipages
        if ($iCatId == 0) {
            // get category id if not set
            $sql = "SELECT idcat FROM " . $this->_cfg['tab']['cat_art'] . " WHERE idart = '$iArtId'";
            $db->query($sql);
            $db->next_record();
            $iCatId = ($db->f('idcat') > 0) ? $db->f('idcat') : '0';
        }

        $sWhere = " ca.idcat = '$iCatId' AND al.idlang = '" . $iLangId . "' AND"
                . " al.urlname = '" . $sName . "' AND al.idart <> '$iArtId'";

        // check if websafe name is in this category
        $sql = "SELECT count(al.idart) as numcats FROM " . $this->_cfg['tab']['art_lang'] . " al LEFT JOIN " . $this->_cfg['tab']['cat_art'] . " ca ON al.idart = ca.idart WHERE " . $sWhere;
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
    private function _inCategory($sName='', $iCatId=0, $iLangId=0) {
        static $db;
        if (!isset($db)) {
            $db = new DB_Contenido();
        }

        // get parentid
        $sql = "SELECT parentid FROM " . $this->_cfg['tab']['cat'] . " WHERE idcat = '$iCatId'";
        $db->query($sql);
        $db->next_record();
        $iParentId = ($db->f('parentid') > 0) ? $db->f('parentid') : '0';

        $sWhere = " c.parentid = '$iParentId' AND cl.idlang = '" . $iLangId . "' AND"
                . " cl.urlname = '" . $sName . "' AND cl.idcat <> '$iCatId'";

        // check if websafe name is in this category
        $sql = "SELECT count(cl.idcat) as numcats FROM " . $this->_cfg['tab']['cat_lang'] . " cl LEFT JOIN " . $this->_cfg['tab']['cat'] . " c ON cl.idcat = c.idcat WHERE " . $sWhere;
        $db->query($sql);
        $db->next_record();

        return ($db->f('numcats') > 0) ? true : false;
    }

}
