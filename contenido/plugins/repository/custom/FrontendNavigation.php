<?php
/**
 * This file includes the "frontend navigation" sub plugin from the old plugin repository.
 *
 * @package    Plugin
 * @subpackage Repository_FrontendNavigation
 * @version    SVN Revision $Rev:$
 *
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * file FrontendNavigation.php
 *
 * @package    Plugin
 * @subpackage Repository_FrontendNavigation
 */
class FrontendNavigation {

    /**
     * Constructor
     */
    function FrontendNavigation($db, $cfg, $cfgClient, $client, $lang) {
        $this->_bDebug = false;
        $this->db = &$db;
        $this->cfgClient = &$cfgClient;
        $this->cfg = &$cfg;
        $this->client = &$client;
        $this->lang = &$lang;
    }

    /**
     * Get child categories by given parent category
     */
    function getSubCategories($iParentCategory) {
        if (!is_int((int) $iParentCategory) AND $iParentCategory < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return array();
        }

        $sql = "SELECT
                    A.idcat
                FROM
                    " . $this->cfg["tab"]["cat_tree"] . " AS A,
                    " . $this->cfg["tab"]["cat"] . " AS B,
                    " . $this->cfg["tab"]["cat_lang"] . " AS C
                WHERE
                    A.idcat    = B.idcat AND
                    B.idcat    = C.idcat AND
                    B.idclient = " . $this->client . " AND
                    C.idlang   = " . $this->lang . " AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.parentid = " . $iParentCategory . "
                ORDER BY
                    A.idtree ";

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->db->query($sql);

        $navigation = array();
        while ($this->db->nextRecord()) {
            $navigation[] = $this->db->f("idcat");
        }

        return $navigation;
    }

    /**
     * Check if child categories of a given parent category exist
     */
    function hasChildren($iParentCategory) {
        if (!is_int((int) $iParentCategory) AND $iParentCategory < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return false;
        }

        $sql = "SELECT
                    B.idcat
                FROM
                    " . $this->cfg["tab"]["cat"] . " AS B,
                    " . $this->cfg["tab"]["cat_lang"] . " AS C
                WHERE
                    B.idcat    = C.idcat AND
                    B.idclient = " . $this->client . " AND
                    C.idlang   = " . $this->lang . " AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.parentid = " . $iParentCategory . " ";

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->db->query($sql);

        if ($this->db->nextRecord()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get direct successor of a given category
     * Note: does not work if direct successor (with preid 0) is not visible
     * or not public
     */
    function getSuccessor($iCategory) {
        if (!is_int((int) $iCategory) AND $iCategory < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return -1;
        }

        $sql = "SELECT
                    B.idcat
                FROM
                    " . $this->cfg["tab"]["cat"] . " AS B,
                    " . $this->cfg["tab"]["cat_lang"] . " AS C
                WHERE
                    B.idcat    = C.idcat AND
                    B.idclient = " . $this->client . " AND
                    C.idlang   = " . $this->lang . " AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.preid    = 0 AND
                    B.parentid = " . $iCategory . " ";

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->db->query($sql);

        if ($this->db->nextRecord()) {
            return $this->db->f("idcat");
        } else {
            return -1;
        }
    }

    /**
     * Check if a given category has a direct successor
     */
    function hasSuccessor($iCategory) {
        if (!is_int((int) $iCategory) AND $iCategory < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return false;
        }

        $sql = "SELECT
                    B.idcat
                FROM
                    " . $this->cfg["tab"]["cat"] . " AS B,
                    " . $this->cfg["tab"]["cat_lang"] . " AS C
                WHERE
                    B.idcat    = C.idcat AND
                    B.idclient = " . $this->client . " AND
                    C.idlang   = " . $this->lang . " AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.preid    = 0 AND
                    B.parentid = " . $iCategory . " ";

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->db->query($sql);

        if ($this->db->nextRecord()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get category name
     */
    function getCategoryName($cat_id) {
        if (!is_int((int) $cat_id) AND $cat_id < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return '';
        }

        $sql = "SELECT
                    B.name
                FROM
                    " . $this->cfg["tab"]["cat"] . " AS A,
                    " . $this->cfg["tab"]["cat_lang"] . " AS B
                WHERE
                    A.idcat    = B.idcat AND
                    A.idcat    = $cat_id AND
                    A.idclient = " . $this->client . " AND
                    B.idlang   = " . $this->lang . "
                ";

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->db->query($sql);

        if ($this->db->nextRecord()) {
            return $this->db->f("name");
        } else {
            return '';
        }
    }

    /**
     * Get category urlname
     */
    function getCategoryURLName($cat_id) {
        if (!is_int((int) $cat_id) AND $cat_id < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return '';
        }

        $sql = "SELECT
                    B.urlname
                FROM
                    " . $this->cfg["tab"]["cat"] . " AS A,
                    " . $this->cfg["tab"]["cat_lang"] . " AS B
                WHERE
                    A.idcat    = B.idcat AND
                    A.idcat    = $cat_id AND
                    A.idclient = " . $this->client . " AND
                    B.idlang   = " . $this->lang . "
                ";

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->db->query($sql);

        if ($this->db->nextRecord()) {
            return $this->db->f("urlname");
        } else {
            return '';
        }
    }

    /**
     * Check if category is visible
     */
    function isVisible($cat_id) {
        if (!is_int((int) $cat_id) AND $cat_id < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return false;
        }

        $sql = "SELECT
                    B.visible
                FROM
                    " . $this->cfg["tab"]["cat"] . " AS A,
                    " . $this->cfg["tab"]["cat_lang"] . " AS B
                WHERE
                    A.idcat    = B.idcat AND
                    A.idcat    = $cat_id AND
                    A.idclient = " . $this->client . " AND
                    B.idlang   = " . $this->lang . "
                ";

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->db->query($sql);
        $this->db->nextRecord();

        if ($this->db->f("visible") == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if category is public
     */
    function isPublic($cat_id) {
        if (!is_int((int) $cat_id) AND $cat_id < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return false;
        }

        $sql = "SELECT
                    B.public
                FROM
                    " . $this->cfg["tab"]["cat"] . " AS A,
                    " . $this->cfg["tab"]["cat_lang"] . " AS B
                WHERE
                    A.idcat    = B.idcat AND
                    A.idcat    = $cat_id AND
                    A.idclient = " . $this->client . " AND
                    B.idlang   = " . $this->lang . "
                ";

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->db->query($sql);
        $this->db->nextRecord();

        if ($this->db->f("public") == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return true if $parentid is parent of $catid
     */
    function isParent($parentid, $catid) {
        if (!is_int((int) $parentid) AND $parentid < 0 AND !is_int((int) $catid) AND $catid < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return false;
        }

        $sql = "SELECT
                a.parentid
                FROM
                    " . $this->cfg["tab"]["cat"] . " AS a,
                    " . $this->cfg["tab"]["cat_lang"] . " AS b
                WHERE
                    a.idclient = " . $this->client . " AND
                    b.idlang   = " . $this->lang . " AND
                    a.idcat    = b.idcat AND
                    a.idcat    = " . $catid . " ";

        $this->db->query($sql);
        $this->db->nextRecord();

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $pre = $this->db->f("parentid");

        if ($parentid == $pre) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get parent id of a category
     */
    function getParent($preid) {
        if (!is_int((int) $preid) AND $preid < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return -1;
        }

        $sql = "SELECT
                a.parentid
                FROM
                    " . $this->cfg["tab"]["cat"] . " AS a,
                    " . $this->cfg["tab"]["cat_lang"] . " AS b
                WHERE
                    a.idclient = " . $this->client . " AND
                    b.idlang   = " . $this->lang . " AND
                    a.idcat    = b.idcat AND
                    a.idcat    = " . $preid . " ";

        $this->db->query($sql);

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        if ($this->db->nextRecord()) {
            return $this->db->f("parentid");
        } else {
            return -1;
        }
    }

    /**
     * Check if a category has a parent
     */
    function hasParent($preid) {
        if (!is_int((int) $preid) AND $preid < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return false;
        }

        $sql = "SELECT
                a.parentid
                FROM
                    " . $this->cfg["tab"]["cat"] . " AS a,
                    " . $this->cfg["tab"]["cat_lang"] . " AS b
                WHERE
                    a.idclient = " . $this->client . " AND
                    b.idlang   = " . $this->lang . " AND
                    a.idcat    = b.idcat AND
                    a.idcat    = " . $preid . " ";

        $this->db->query($sql);

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        if ($this->db->nextRecord()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get level of a category
     */
    function getLevel($catid) {
        if (!is_int((int) $catid) AND $catid < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return -1;
        }

        $sql = "SELECT
                    level
                FROM
                    " . $this->cfg["tab"]["cat_tree"] . "
                WHERE
                    idcat = " . $catid . " ";

        $this->db->query($sql);

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        if ($this->db->nextRecord()) {
            return $this->db->f("level");
        } else {
            return -1;
        }
    }

    /**
     * Get URL by given category in front_content.php style
     *
     * @param int $iIdcat
     * @param int $iIdart
     * @param bool $bAbsolute return absolute path or not
     * @return string URL
     * @author Willi Man
     */
    function getFrontContentUrl($iIdcat, $iIdart, $bAbsolute = true) {
        if (!is_int((int) $iIdcat) AND $iIdcat < 0) {
            return '';
        }

        if ($bAbsolute === true) {
            # add absolute web path to urlpath
            if (is_int((int) $iIdart) AND $iIdart > 0) {
                $sURL = $this->cfgClient[$this->client]['path']['htmlpath'] . 'front_content.php?idcat=' . $iIdcat . '&idart=' . $iIdart;
            } else {
                $sURL = $this->cfgClient[$this->client]['path']['htmlpath'] . 'front_content.php?idcat=' . $iIdcat;
            }
        } else {
            if (is_int((int) $iIdart) AND $iIdart > 0) {
                $sURL = 'front_content.php?idcat=' . $iIdcat . '&idart=' . $iIdart;
            } else {
                $sURL = 'front_content.php?idcat=' . $iIdcat;
            }
        }

        return $sURL;
    }

    /**
     * Get urlpath by given category and/or idart and level.
     * The urlpath looks like /Home/Product/Support/ where the directory-like string equals a category path.
     *
     * @requires functions.pathresolver.php
     * @param int $iIdcat
     * @param int $iIdart
     * @param bool $bAbsolute return absolute path or not
     * @return string path information or empty string
     * @author Marco Jahn (Project www.usa.de)
     * @modified by Willi Man
     */
    function getUrlPath($iIdcat, $iIdart, $bAbsolute = true, $iLevel = 0, $sURL_SUFFIX = 'index.html') {
        if (!is_int((int) $iIdcat) AND $iIdcat < 0) {
            return '';
        }

        $cat_str = '';
        prCreateURLNameLocationString($iIdcat, "/", $cat_str, false, "", $iLevel, $this->lang, true, false);

        if (strlen($cat_str) <= 1) {
            # return empty string if no url location is available
            return '';
        }

        if ($bAbsolute === true) {
            # add absolute web path to urlpath
            if (is_int((int) $iIdart) AND $iIdart > 0) {
                return $this->cfgClient[$this->client]['path']['htmlpath'] . $cat_str . '/index-d-' . $iIdart . '.html';
            } else {
                return $this->cfgClient[$this->client]['path']['htmlpath'] . $cat_str . '/' . $sURL_SUFFIX;
            }
        } else {
            if (is_int((int) $iIdart) AND $iIdart > 0) {
                return $cat_str . '/index-d-' . $iIdart . '.html';
            } else {
                return $cat_str . '/' . $sURL_SUFFIX;
            }
        }
    }

    /**
     * Get urlpath by given category and/or selected param and level.
     *
     * @requires functions.pathresolver.php
     * @param int $iIdcat
     * @param int $iSelectedNumber
     * @param bool $bAbsolute return absolute path or not
     * @return string path information or empty string
     * @author Willi Man
     */
    function getUrlPathGenParam($iIdcat, $iSelectedNumber, $bAbsolute = true, $iLevel = 0) {
        if (!is_int((int) $iIdcat) AND $iIdcat < 0) {
            return '';
        }

        $cat_str = '';
        prCreateURLNameLocationString($iIdcat, "/", $cat_str, false, "", $iLevel, $this->lang, true, false);

        if (strlen($cat_str) <= 1) {
            # return empty string if no url location is available
            return '';
        }

        if ($bAbsolute === true) {
            # add absolute web path to urlpath
            if (is_int((int) $iSelectedNumber)) {
                return $this->cfgClient[$this->client]['path']['htmlpath'] . $cat_str . '/index-g-' . $iSelectedNumber . '.html';
            }
        } else {
            if (is_int((int) $iSelectedNumber)) {
                return $cat_str . '/index-g-' . $iSelectedNumber . '.html';
            }
        }
    }

    /**
     * Get URL by given categoryid and/or articleid
     *
     * @param int $iIdcat url name to create for
     * @param int $iIdart
     * @param bool $bAbsolute return absolute path or not
     * @return string URL
     * @author Willi Man
     */
    function getURL($iIdcat, $iIdart, $sType = '', $bAbsolute = true, $iLevel = 0) {
        if (!is_int((int) $iIdcat) AND $iIdcat < 0) {
            return '';
        }

        #print "type ".$sType."<br>";

        switch ($sType) {
            case 'urlpath':
                $sURL = $this->getUrlPath($iIdcat, $iIdart, $bAbsolute, $iLevel);
                break;
            case 'frontcontent':
                $sURL = $this->getFrontContentUrl($iIdcat, $iIdart, $bAbsolute);
                break;
            case 'index-a':
                # not implemented
                $sURL = '';
                break;
            default:
                $sURL = $this->getFrontContentUrl($iIdcat, $iIdart, $bAbsolute);
        }

        return $sURL;
    }

    /**
     * Get category of article.
     *
     * If an article is assigned to more than one category take the first
     * category.
     *
     * @param  int $iArticleId
     * @return int category id
     */
    function getCategoryOfArticle($iArticleId) {

        # validate input
        if (!is_int((int) $iArticleId) OR $iArticleId <= 0) {
            return -1;
        }

        $sqlString = '
        SELECT
            c.idcat
        FROM
            ' . $this->cfg['tab']['art_lang'] . ' AS a,
            ' . $this->cfg['tab']['art'] . ' AS b,
            ' . $this->cfg['tab']['cat_art'] . ' AS c
        WHERE
            a.idart = ' . $iArticleId . ' AND
            b.idclient = ' . $this->client . ' AND
            a.idlang = ' . $this->lang . ' AND
            b.idart = c.idart AND
            a.idart = b.idart ';

        if ($this->_bDebug) {
            echo "<pre>" . $sqlString . "</pre>";
        }

        $this->db->query($sqlString);

        # $this->db->getErrorNumber() returns 0 (zero) if no error occurred.
        if ($this->db->getErrorNumber() == 0) {
            if ($this->db->nextRecord()) {
                return $this->db->f('idcat');
            } else {
                return -1;
            }
        } else {
            if ($this->_bDebug) {
                echo "<pre>Mysql Error:" . $this->db->getErrorMessage() . "(" . $this->db->getErrorNumber() . ")</pre>";
            }
            return -1; # error occurred.
        }
    }

    /**
     * Get path  of a given category up to a certain level
     */
    function getCategoryPath($cat_id, $level = 0, $reverse = true) {
        if (!is_int((int) $cat_id) AND $cat_id < 0) {
            return array();
        }

        $root_path = array();
        array_push($root_path, $cat_id);
        $parent_id = $cat_id;

        while ($this->getLevel($parent_id) >= 0 AND $this->getLevel($parent_id) > $level) {
            $parent_id = $this->getParent($parent_id);
            if ($parent_id >= 0) {
                array_push($root_path, $parent_id);
            }
        }

        if ($reverse == true) {
            $root_path = array_reverse($root_path);
        }

        return $root_path;
    }

    /**
     * Get root category of a given category
     */
    function getRoot($cat_id) {
        if (!is_int((int) $cat_id) AND $cat_id < 0) {
            return array();
        }

        $parent_id = $cat_id;

        while ($this->getLevel($parent_id) >= 0) {
            $iRootCategory = $parent_id;
            $parent_id = $this->getParent($parent_id);
        }

        return $iRootCategory;
    }

    /**
     * get subtree by a given id
     *
     * @param int $idcat_start Id of category
     * @return array Array with subtree
     *
     * @copyright four for business AG <www.4fb.de>
     */
    function getSubTree($idcat_start) {

        if (!is_int((int) $idcat_start) AND $idcat_start < 0 AND !is_array($this->cfg) AND !isset($this->cfg['tab']) AND !is_int((int) $this->client) AND $this->client < 0 AND !is_int((int) $this->lang) AND $this->lang < 0) {
            return array();
        }

        $sql = "SELECT
                    B.idcat, A.level
                FROM
                    " . $this->cfg["tab"]["cat_tree"] . " AS A,
                    " . $this->cfg["tab"]["cat"] . " AS B
                WHERE
                    A.idcat  = B.idcat AND
                    idclient = " . $this->client . "
                ORDER BY
                    idtree";

        if ($this->_bDebug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->db->query($sql);

        $i = false;

        while ($this->db->nextRecord()) {
            if ($this->db->f("idcat") == $idcat_start) {
                $curLevel = $this->db->f("level");
                $i = true;
            } else {
                if ($curLevel == $this->db->f("level")) {
                    # ending part of tree
                    $i = false;
                }
            }

            if ($i == true) {
                $deeper_cats[] = $this->db->f("idcat");
            }
        }
        return $deeper_cats;
    }

}

?>