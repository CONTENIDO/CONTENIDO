<?php

/**
 * This file includes the "frontend navigation" sub plugin from the old plugin repository.
 *
 * @package    Plugin
 * @subpackage Repository_FrontendNavigation
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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
     * References database object
     *
     * @var cDb
     */
    protected $_db = null;

    /*
     *
     * @var boolean
     */
    protected $_debug = false;

    /**
     * @var integer
     */
    protected $_client = 0;

    /**
     * @var array
     */
    protected $_cfgClient = [];

    /**
     * @var array
     */
    protected $_cfg = [];

    /**
     * @var integer
     */
    protected $_lang = 0;

    /**
     * FrontendNavigation constructor
     */
    public function __construct() {
        $this->_db = cRegistry::getDb();
        $this->_cfgClient = cRegistry::getClientConfig();
        $this->_cfg = cRegistry::getConfig();
        $this->_client = cRegistry::getClientId();
        $this->_lang = cRegistry::getLanguageId();
    }

    /**
     * Old constructor
     *
     * @deprecated [2016-02-11]
     *                This method is deprecated and is not needed any longer. Please use __construct() as constructor function.
     * @return FrontendNavigation
     */
    public function FrontendNavigation() {
        cDeprecated('This method is deprecated and is not needed any longer. Please use __construct() as constructor function.');
        return $this->__construct();
    }

    /**
     * Get child categories by given parent category
     *
     * @param integer $parentCategory
     *
     * @return array
     * @throws cDbException
     */
    public function getSubCategories($parentCategory) {
        if (!is_int((int) $parentCategory)) {
            return [];
        }

        $sql = "SELECT
                    A.idcat
                FROM
                    " . $this->_cfg["tab"]["cat_tree"] . " AS A,
                    " . $this->_cfg["tab"]["cat"] . " AS B,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS C
                WHERE
                    A.idcat    = B.idcat AND
                    B.idcat    = C.idcat AND
                    B.idclient = " . $this->_client . " AND
                    C.idlang   = " . $this->_lang . " AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.parentid = " . $parentCategory . "
                ORDER BY
                    A.idtree ";

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->_db->query($sql);

        $navigation = [];
        while ($this->_db->nextRecord()) {
            $navigation[] = $this->_db->f("idcat");
        }

        return $navigation;
    }

    /**
     * Check if child categories of a given parent category exist
     *
     * @param integer $parentCategory
     *
     * @return boolean
     * @throws cDbException
     */
    public function hasChildren($parentCategory) {
        if (!is_int((int) $parentCategory)) {
            return false;
        }

        $sql = "SELECT
                    B.idcat
                FROM
                    " . $this->_cfg["tab"]["cat"] . " AS B,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS C
                WHERE
                    B.idcat    = C.idcat AND
                    B.idclient = " . $this->_client . " AND
                    C.idlang   = " . $this->_lang . " AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.parentid = " . $parentCategory . " ";

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->_db->query($sql);

        if ($this->_db->nextRecord()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get direct successor of a given category
     * Note: does not work if direct successor (with preid 0) is not visible
     * or not public
     *
     * @param integer $category
     *
     * @return integer
     * @throws cDbException
     */
    public function getSuccessor($category) {
        if (!is_int((int) $category)) {
            return -1;
        }

        $sql = "SELECT
                    B.idcat
                FROM
                    " . $this->_cfg["tab"]["cat"] . " AS B,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS C
                WHERE
                    B.idcat    = C.idcat AND
                    B.idclient = " . $this->_client . " AND
                    C.idlang   = " . $this->_lang . " AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.preid    = 0 AND
                    B.parentid = " . $category . " ";

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->_db->query($sql);

        if ($this->_db->nextRecord()) {
            return $this->_db->f("idcat");
        } else {
            return -1;
        }
    }

    /**
     * Check if a given category has a direct successor
     *
     * @param integer $category
     *
     * @return boolean
     * @throws cDbException
     */
    public function hasSuccessor($category) {
        if (!is_int((int) $category)) {
            return false;
        }

        $sql = "SELECT
                    B.idcat
                FROM
                    " . $this->_cfg["tab"]["cat"] . " AS B,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS C
                WHERE
                    B.idcat    = C.idcat AND
                    B.idclient = " . $this->_client . " AND
                    C.idlang   = " . $this->_lang . " AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.preid    = 0 AND
                    B.parentid = " . $category . " ";

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->_db->query($sql);

        if ($this->_db->nextRecord()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get category name
     *
     * @param integer $cat_id
     *
     * @return string
     * @throws cDbException
     */
    public function getCategoryName($cat_id) {
        if (!is_int((int) $cat_id)) {
            return '';
        }

        $sql = "SELECT
                    B.name
                FROM
                    " . $this->_cfg["tab"]["cat"] . " AS A,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS B
                WHERE
                    A.idcat    = B.idcat AND
                    A.idcat    = $cat_id AND
                    A.idclient = " . $this->_client . " AND
                    B.idlang   = " . $this->_lang . "
                ";

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->_db->query($sql);

        if ($this->_db->nextRecord()) {
            return $this->_db->f("name");
        } else {
            return '';
        }
    }

    /**
     * Get category urlname
     *
     * @param integer $cat_id
     *
     * @return string
     * @throws cDbException
     */
    public function getCategoryURLName($cat_id) {
        if (!is_int((int) $cat_id)) {
            return '';
        }

        $sql = "SELECT
                    B.urlname
                FROM
                    " . $this->_cfg["tab"]["cat"] . " AS A,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS B
                WHERE
                    A.idcat    = B.idcat AND
                    A.idcat    = $cat_id AND
                    A.idclient = " . $this->_client . " AND
                    B.idlang   = " . $this->_lang . "
                ";

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->_db->query($sql);

        if ($this->_db->nextRecord()) {
            return $this->_db->f("urlname");
        } else {
            return '';
        }
    }

    /**
     * Check if category is visible
     *
     * @param integer $cat_id
     *
     * @return boolean
     * @throws cDbException
     */
    public function isVisible($cat_id) {
        if (!is_int((int) $cat_id)) {
            return false;
        }

        $sql = "SELECT
                    B.visible
                FROM
                    " . $this->_cfg["tab"]["cat"] . " AS A,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS B
                WHERE
                    A.idcat    = B.idcat AND
                    A.idcat    = $cat_id AND
                    A.idclient = " . $this->_client . " AND
                    B.idlang   = " . $this->_lang . "
                ";

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->_db->query($sql);
        $this->_db->nextRecord();

        if ($this->_db->f("visible") == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if category is public
     *
     * @param integer $cat_id
     *
     * @return boolean
     * @throws cDbException
     */
    public function isPublic($cat_id) {
        if (!is_int((int) $cat_id)) {
            return false;
        }

        $sql = "SELECT
                    B.public
                FROM
                    " . $this->_cfg["tab"]["cat"] . " AS A,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS B
                WHERE
                    A.idcat    = B.idcat AND
                    A.idcat    = $cat_id AND
                    A.idclient = " . $this->_client . " AND
                    B.idlang   = " . $this->_lang . "
                ";

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->_db->query($sql);
        $this->_db->nextRecord();

        if ($this->_db->f("public") == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return true if $parentid is parent of $catid
     *
     * @param integer $parentid
     * @param integer $catid
     *
     * @return boolean
     * @throws cDbException
     */
    public function isParent($parentid, $catid) {
        if (!is_int((int) $parentid)) {
            return false;
        }

        $sql = "SELECT
                a.parentid
                FROM
                    " . $this->_cfg["tab"]["cat"] . " AS a,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS b
                WHERE
                    a.idclient = " . $this->_client . " AND
                    b.idlang   = " . $this->_lang . " AND
                    a.idcat    = b.idcat AND
                    a.idcat    = " . $catid . " ";

        $this->_db->query($sql);
        $this->_db->nextRecord();

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $pre = $this->_db->f("parentid");

        if ($parentid == $pre) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get parent id of a category
     *
     * @param integer $preid
     *
     * @return integer
     * @throws cDbException
     */
    public function getParent($preid) {
        if (!is_int((int) $preid)) {
            return -1;
        }

        $sql = "SELECT
                a.parentid
                FROM
                    " . $this->_cfg["tab"]["cat"] . " AS a,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS b
                WHERE
                    a.idclient = " . $this->_client . " AND
                    b.idlang   = " . $this->_lang . " AND
                    a.idcat    = b.idcat AND
                    a.idcat    = " . $preid . " ";

        $this->_db->query($sql);

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        if ($this->_db->nextRecord()) {
            return $this->_db->f("parentid");
        } else {
            return -1;
        }
    }

    /**
     * Check if a category has a parent
     *
     * @param integer $preid
     *
     * @return boolean
     * @throws cDbException
     */
    public function hasParent($preid) {
        if (!is_int((int) $preid)) {
            return false;
        }

        $sql = "SELECT
                a.parentid
                FROM
                    " . $this->_cfg["tab"]["cat"] . " AS a,
                    " . $this->_cfg["tab"]["cat_lang"] . " AS b
                WHERE
                    a.idclient = " . $this->_client . " AND
                    b.idlang   = " . $this->_lang . " AND
                    a.idcat    = b.idcat AND
                    a.idcat    = " . $preid . " ";

        $this->_db->query($sql);

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        if ($this->_db->nextRecord()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get level of a category
     *
     * @param integer $catid
     *
     * @return integer
     * @throws cDbException
     */
    public function getLevel($catid) {
        if (!is_int((int) $catid)) {
            return -1;
        }

        $sql = "SELECT
                    level
                FROM
                    " . $this->_cfg["tab"]["cat_tree"] . "
                WHERE
                    idcat = " . $catid . " ";

        $this->_db->query($sql);

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        if ($this->_db->nextRecord()) {
            return $this->_db->f("level");
        } else {
            return -1;
        }
    }

    /**
     * Get URL by given category in front_content.php style
     *
     * @param int $idcat
     * @param int $idart
     * @param bool $absolute return absolute path or not [optional]
     * @return string $url
     */
    public function getFrontContentUrl($idcat, $idart, $absolute = true) {
        if (!is_int((int) $idcat) && $idcat < 0) {
            return '';
        }

        if ($absolute === true) {
            # add absolute web path to urlpath
            if (is_int((int) $idart) && $idart > 0) {
                $url = $this->_cfgClient[$this->_client]['path']['htmlpath'] . 'front_content.php?idcat=' . $idcat . '&idart=' . $idart;
            } else {
                $url = $this->_cfgClient[$this->_client]['path']['htmlpath'] . 'front_content.php?idcat=' . $idcat;
            }
        } else {
            if (is_int((int) $idart) && $idart > 0) {
                $url = 'front_content.php?idcat=' . $idcat . '&idart=' . $idart;
            } else {
                $url = 'front_content.php?idcat=' . $idcat;
            }
        }

        return $url;
    }

    /**
     * Get urlpath by given category and/or idart and level.
     * The urlpath looks like /Home/Product/Support/ where the directory-like string equals a category path.
     *
     * @requires functions.pathresolver.php
     * @param int $idcat
     * @param int $idart
     * @param bool $absolute return absolute path or not [optional]
     * @param integer $level [optional]
     * @param string $urlSuffix [optional]
     * @return string path information or empty string
     */
    public function getUrlPath($idcat, $idart, $absolute = true, $level = 0, $urlSuffix = 'index.html') {
        if (!is_int((int) $idcat) && $idcat < 0) {
            return '';
        }

        $cat_str = '';
        prCreateURLNameLocationString($idcat, "/", $cat_str, false, "", $level, $this->_lang, true, false);

        if (cString::getStringLength($cat_str) <= 1) {
            # return empty string if no url location is available
            return '';
        }

        if ($absolute === true) {
            # add absolute web path to urlpath
            if (is_int((int) $idart) && $idart > 0) {
                return $this->_cfgClient[$this->_client]['path']['htmlpath'] . $cat_str . '/index-d-' . $idart . '.html';
            } else {
                return $this->_cfgClient[$this->_client]['path']['htmlpath'] . $cat_str . '/' . $urlSuffix;
            }
        } else {
            if (is_int((int) $idart) && $idart > 0) {
                return $cat_str . '/index-d-' . $idart . '.html';
            } else {
                return $cat_str . '/' . $urlSuffix;
            }
        }
    }

    /**
     * Get urlpath by given category and/or selected param and level.
     *
     * @requires functions.pathresolver.php
     * @param int $idcat
     * @param int $selectedNumber
     * @param bool $absolute return absolute path or not [optional]
     * @param integer $level [optional]
     * @return string path information or empty string
     */
    public function getUrlPathGenParam($idcat, $selectedNumber, $absolute = true, $level = 0) {
        if (!is_int((int) $idcat) && $idcat < 0) {
            return '';
        }

        $cat_str = '';
        prCreateURLNameLocationString($idcat, "/", $cat_str, false, "", $level, $this->_lang, true, false);

        if (cString::getStringLength($cat_str) <= 1) {
            // return empty string if no url location is available
            return '';
        }

        if ($absolute === true) {
            // add absolute web path to urlpath
            if (is_int((int) $selectedNumber)) {
                return $this->_cfgClient[$this->_client]['path']['htmlpath'] . $cat_str . '/index-g-' . $selectedNumber . '.html';
            }
        } else {
            if (is_int((int) $selectedNumber)) {
                return $cat_str . '/index-g-' . $selectedNumber . '.html';
            }
        }

        return '';
    }

    /**
     * Get URL by given categoryid and/or articleid
     *
     * @param int     $idcat    url name to create for
     * @param int     $idart
     * @param string  $type
     * @param bool    $absolute return absolute path or not [optional]
     * @param integer $level
     *
     * @return string $url or empty
     */
    public function getURL($idcat, $idart, $type = '', $absolute = true, $level = 0) {
        if (!is_int((int) $idcat) AND $idcat < 0) {
            return '';
        }

        switch ($type) {
            case 'urlpath':
                $url = $this->getUrlPath($idcat, $idart, $absolute, $level);
                break;
            case 'frontcontent':
                $url = $this->getFrontContentUrl($idcat, $idart, $absolute);
                break;
            case 'index-a':
                # not implemented
                $url = '';
                break;
            default:
                $url = $this->getFrontContentUrl($idcat, $idart, $absolute);
        }

        return $url;
    }

    /**
     * Get category of article.
     *
     * If an article is assigned to more than one category take the first
     * category.
     *
     * @param  int $idart
     *
     * @return int category id or negative integer
     * @throws cDbException
     */
    public function getCategoryOfArticle($idart) {

        # validate input
        if (!is_int((int) $idart) || $idart <= 0) {
            return -1;
        }

        $sql = '
        SELECT
            c.idcat
        FROM
            ' . $this->_cfg['tab']['art_lang'] . ' AS a,
            ' . $this->_cfg['tab']['art'] . ' AS b,
            ' . $this->_cfg['tab']['cat_art'] . ' AS c
        WHERE
            a.idart = ' . $idart . ' AND
            b.idclient = ' . $this->_client . ' AND
            a.idlang = ' . $this->_lang . ' AND
            b.idart = c.idart AND
            a.idart = b.idart ';

        if ($this->_debug) {
            echo "<pre>" . $sql . "</pre>";
        }

        $this->_db->query($sql);

        # $this->db->getErrorNumber() returns 0 (zero) if no error occurred.
        if ($this->_db->getErrorNumber() == 0) {
            if ($this->_db->nextRecord()) {
                return $this->_db->f('idcat');
            } else {
                return -1;
            }
        } else {
            if ($this->_debug) {
                echo "<pre>Mysql Error:" . $this->_db->getErrorMessage() . "(" . $this->_db->getErrorNumber() . ")</pre>";
            }
            return -1; # error occurred.
        }
    }

    /**
     * Get path  of a given category up to a certain level
     *
     * @param integer $cat_id
     * @param integer $level [optional]
     * @param boolean $reverse
     *
     * @return array
     * @throws cDbException
     */
    public function getCategoryPath($cat_id, $level = 0, $reverse = true) {
        if (!is_int((int) $cat_id) && $cat_id < 0) {
            return [];
        }

        $root_path = [];
        $root_path[] = $cat_id;
        $parent_id = $cat_id;

        while ($this->getLevel($parent_id) >= 0 && $this->getLevel($parent_id) > $level) {
            $parent_id = $this->getParent($parent_id);
            if ($parent_id >= 0) {
                $root_path[] = $parent_id;
            }
        }

        if ($reverse == true) {
            $root_path = array_reverse($root_path);
        }

        return $root_path;
    }

    /**
     * Get root category of a given category
     *
     * @param int $catId
     *
     * @return int|false
     * @throws cDbException
     */
    function getRoot($catId) {
        if (!is_int((int) $catId) && $catId < 0) {
            return false;
        }

        $rootCategory = false;
        $parentId = $catId;

        while ($this->getLevel($parentId) >= 0) {
            $rootCategory = $parentId;
            $parentId = $this->getParent($parentId);
        }

        return $rootCategory;
    }

    /**
     * get subtree by a given id
     *
     * @param int $idcat_start Id of category
     *
     * @return array Array with subtree
     * @throws cDbException
     */
    function getSubTree($idcat_start) {
        if (!is_int((int) $idcat_start)) {
            return [];
        }

        $sql = "SELECT
                    B.idcat, A.level
                FROM
                    " . $this->_cfg["tab"]["cat_tree"] . " AS A,
                    " . $this->_cfg["tab"]["cat"] . " AS B
                WHERE
                    A.idcat  = B.idcat AND
                    idclient = " . $this->_client . "
                ORDER BY
                    idtree";

        if ($this->_debug) {
            echo "<pre>";
            print_r($sql);
            echo "</pre>";
        }

        $this->_db->query($sql);

        $i = false;
        $curLevel = 0;

        while ($this->_db->nextRecord()) {
            if ($this->_db->f("idcat") == $idcat_start) {
                $curLevel = $this->_db->f("level");
                $i = true;
            } else {
                if ($curLevel == $this->_db->f("level")) {
                    # ending part of tree
                    $i = false;
                }
            }

            if ($i == true) {
                $deeper_cats[] = $this->_db->f("idcat");
            }
        }
        return $deeper_cats;
    }

}