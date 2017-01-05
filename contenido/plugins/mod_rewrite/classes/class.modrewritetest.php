<?php
/**
 * AMR test class
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Mod rewrite test class.
 *
 * @author      Murat Purc <murat@purc.de>
 * @package     Plugin
 * @subpackage  ModRewrite
 */
class ModRewriteTest {

    /**
     * Global $cfg array
     * @var  array
     */
    protected $_aCfg;

    /**
     * Global $cfg['tab'] array
     * @var  array
     */
    protected $_aCfgTab;

    /**
     * Max items to process
     * @var  int
     */
    protected $_iMaxItems;

    /**
     * Actual resolved url
     * @var  string
     */
    protected $_sResolvedUrl;

    /**
     * Routing found flag
     * @var  bool
     */
    protected $_bRoutingFound = false;

    /**
     * Constuctor
     * @param  int  $maxItems  Max items (urls to articles/categories) to process
     */
    public function __construct($maxItems) {
        global $cfg;
        $this->_aCfg = & $cfg;
        $this->_aCfgTab = & $cfg['tab'];
        $this->_iMaxItems = $maxItems;
    }

    /**
     * Returns resolved URL
     *
     * @return  bool  Resolved URL
     */
    public function getResolvedUrl() {
        return $this->_sResolvedUrl;
    }

    /**
     * Returns flagz about found routing
     *
     * @return  bool
     */
    public function getRoutingFoundState() {
        return $this->_bRoutingFound;
    }

    /**
     * Fetchs full structure of the installation (categories and articles) and returns it back.
     *
     * @param   int    $idclient  Client id
     * @param   int    $idlang    Language id
     * @return  array  Full structure as follows
     * <code>
     *   $arr[idcat] = Category dataset
     *   $arr[idcat]['articles'][idart] = Article dataset
     * </code>
     */
    public function fetchFullStructure($idclient = NULL, $idlang = NULL) {
        global $client, $lang;

        $db = cRegistry::getDb();
        $db2 = cRegistry::getDb();

        if (!$idclient || (int) $idclient == 0) {
            $idclient = $client;
        }
        if (!$idlang || (int) $idlang == 0) {
            $idlang = $lang;
        }

        $aTab = $this->_aCfgTab;

        $aStruct = array();

        $sql = "SELECT
                    *
                FROM
                    " . $aTab['cat_tree'] . " AS a,
                    " . $aTab['cat_lang'] . " AS b,
                    " . $aTab['cat'] . " AS c
                WHERE
                    a.idcat = b.idcat AND
                    c.idcat = a.idcat AND
                    c.idclient = '" . $idclient . "' AND
                    b.idlang = '" . $idlang . "'
                ORDER BY
                    a.idtree";

        $db->query($sql);

        $counter = 0;

        while ($db->nextRecord()) {

            if (++$counter == $this->_iMaxItems) {
                break; // break this loop
            }

            $idcat = $db->f('idcat');
            $aStruct[$idcat] = $db->getRecord();
            $aStruct[$idcat]['articles'] = array();

            $sql2 = "SELECT
                         *
                     FROM
                         " . $aTab['cat_art'] . "  AS a,
                         " . $aTab['art'] . "      AS b,
                         " . $aTab['art_lang'] . " AS c
                     WHERE
                         a.idcat = '" . $idcat . "' AND
                         b.idart = a.idart AND
                         c.idart = a.idart AND
                         c.idlang = '" . $idlang . "' AND
                         b.idclient = '" . $idclient . "'
                     ORDER BY
                         c.title ASC";

            $db2->query($sql2);

            while ($db2->nextRecord()) {
                $idart = $db2->f('idart');
                $aStruct[$idcat]['articles'][$idart] = $db2->getRecord();
                if (++$counter == $this->_iMaxItems) {
                    break 2; // break this and also superior loop
                }
            }
        }

        return $aStruct;
    }

    /**
     * Creates an URL using passed data.
     *
     * The result is used to generate seo urls...
     *
     * @param  array  $arr  Assoziative array with some data as follows:
     * <code>
     * $arr['idcat']
     * $arr['idart']
     * $arr['idcatart']
     * $arr['idartlang']
     * </code>
     * @param  string  $type  Either 'c' or 'a' (category or article). If set to
     *                        'c' only the parameter idcat will be added to the URL
     */
    public function composeURL($arr, $type) {
        $type = ($type == 'a') ? 'a' : 'c';

        $param = array();

        if ($type == 'c') {
            $param[] = 'idcat=' . $arr['idcat'];
        } else {
            if (mr_getRequest('idart')) {
                $param[] = 'idart=' . $arr['idart'];
            }
            if (mr_getRequest('idcat')) {
                $param[] = 'idcat=' . $arr['idcat'];
            }
            if (mr_getRequest('idcatart')) {
                $param[] = 'idcatart=' . $arr['idcatart'];
            }
            if (mr_getRequest('idartlang')) {
                $param[] = 'idartlang=' . $arr['idartlang'];
            }
        }
        $param[] = 'foo=bar';
        return 'front_content.php?' . implode('&amp;', $param);
    }

    /**
     * Resolves variables of an page (idcat, idart, idclient, idlang, etc.) by
     * processing passed url using ModRewriteController
     *
     * @param   string  $url  Url to resolve
     * @return  array   Assoziative array with resolved data
     */
    public function resolveUrl($url) {
        // some globals to reset
        $aGlobs = array(
            'mr_preprocessedPageError', 'idart', 'idcat'
        );
        foreach ($aGlobs as $p => $k) {
            if (isset($GLOBALS[$k])) {
                unset($GLOBALS[$k]);
            }
        }

        $aReturn = array();

        // create an mod rewrite controller instance and execute processing
        $oMRController = new ModRewriteController($url);
        $oMRController->execute();

        if ($oMRController->errorOccured()) {

            // an error occured (idcat and or idart couldn't catched by controller)
            $aReturn['mr_preprocessedPageError'] = 1;
            $aReturn['error'] = $oMRController->getError();

            $this->_sResolvedUrl = '';
            $this->_bRoutingFound = false;
        } else {

            // set some global variables

            $this->_sResolvedUrl = $oMRController->getResolvedUrl();
            $this->_bRoutingFound = $oMRController->getRoutingFoundState();

            if ($oMRController->getClient()) {
                $aReturn['client'] = $oMRController->getClient();
            }

            if ($oMRController->getChangeClient()) {
                $aReturn['changeclient'] = $oMRController->getChangeClient();
            }

            if ($oMRController->getLang()) {
                $aReturn['lang'] = $oMRController->getLang();
            }

            if ($oMRController->getChangeLang()) {
                $aReturn['changelang'] = $oMRController->getChangeLang();
            }

            if ($oMRController->getIdArt()) {
                $aReturn['idart'] = $oMRController->getIdArt();
            }

            if ($oMRController->getIdCat()) {
                $aReturn['idcat'] = $oMRController->getIdCat();
            }

            if ($oMRController->getPath()) {
                $aReturn['path'] = $oMRController->getPath();
            }
        }

        return $aReturn;
    }

    /**
     * Creates a readable string from passed resolved data array.
     *
     * @param   array   $data Assoziative array with resolved data
     * @return  string  Readable resolved data
     */
    public function getReadableResolvedData(array $data) {
        // compose resolved string
        $ret = '';
        foreach ($data as $k => $v) {
            $ret .= $k . '=' . $v . '; ';
        }
        $ret = cString::getPartOfString($ret, 0, cString::getStringLength($ret) - 2);
        return $ret;
    }

}
