<?php

/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * TINYMCE 4 PHP WYSIWYG interface
 * Generates file/link list for editor
 *
 * Requirements:
 * @con_php_req 5.3
 * @con_notice
 * TINYMCE 4 Fileversion
 *
 * @package    CONTENIDO Backend Editor
 * @version    0.0.1
 * @author     Thomas Stauer
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
$contenido_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../../../')) . '/';

if (!is_file($contenido_path . 'includes/startup.php')) {
    die("<h1>Fatal Error</h1><br>Couldn't include CONTENIDO startup.");
}
include_once($contenido_path . 'includes/startup.php');

cRegistry::bootstrap([
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission',
]);

// include editor config/combat file
include(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'config.php');

$db = cRegistry::getDb();

cInclude('includes', 'functions.lang.php');

$mediaList = new cTinyMCE4List($_GET['mode']);

/**
 */
class cTinyMCE4List {

    /**
     * @param string|null $mode
     */
    public function __construct($mode = null) {
        // output an empty list for no specified mode
        if (false === isset($mode)) {
            echo '[]';
            return;
        }

        // process defined list modes
        switch ($mode) {
            case 'image':
                $list = $this->_buildImageList();
                break;
            case 'link':
                $list = $this->_buildLinkList();
                break;
            default:
                // just output an empty list for unknown mode
                $list = [];
        }

        $this->_printList($list);
    }

    /**
     * get a list of images that is accessible for tinymce
     * @return array The array of images filled with upload objects
     */
    private function _buildImageList() {
        $client = cRegistry::getClientId();
        $clientConfig = cRegistry::getClientConfig($client);

        // get needed data using cApiUploadCollection class
        $oApiUploadCol = new cApiUploadCollection();
        // get uploads for current client
        // filetype can be either gif, jpg, jpeg or png

        $selectClause = "idclient='" . cSecurity::toInteger($client) . "' AND filetype IN ('gif', 'jpg', 'jpeg', 'png')";
        $oApiUploadCol->select($selectClause, '', 'dirname, filename ASC');
        // $oApiUploadCol->setWhere('idclient', cSecurity::toInteger($client));
        // $oApiUploadCol->setWhere('filetype', ['gif', 'jpg', 'jpeg', 'png'], 'IN');
        // $oApiUploadCol->setOrder('dirname, filename ASC');
        // $oApiUploadCol->query();
        $aUplList = $oApiUploadCol->fetchArray(
            $oApiUploadCol->getPrimaryKeyName(),
            ['idclient', 'dirname', 'filetype', 'filename']
        );

        $imageList = [];
        foreach ($aUplList as $uplItem) {
            $imageItem = new stdClass();
            $imageItem->title = $uplItem['dirname'] . $uplItem['filename'];
            $imageItem->value = $clientConfig['upload'] . $uplItem['dirname'] . $uplItem['filename'];

            $imageList[] = $imageItem;
        }

        return $imageList;
    }

    /**
     * This function helps adding the category entries to the wood according to their deepness level
     * @param array $woodTree
     * @param int $lvl The level where entry should be inserted into wood. Level 0 means we enter a tree to the wood.
     * @param stdClass $entry The entry to add into the wood.
     * @return array The altered woodTree with newly inserted entry at the correct level.
     */
    private function _addToWoodTree($woodTree, $lvl, $entry) {
        // add to wood if its a tree root
        if ($lvl === 0) {
            $woodTree[] = $entry;
            return $woodTree;
        }

        // get copy of catTree
        $res = unserialize(serialize($woodTree));

        // use pseudo-reference to manipulate result in-place
        $scope = &$res;
        for ($i = $lvl; $i > 0; $i--) {
            // set pointer to last element of array
            end($scope);
            // get reference to last element of array
            $scope = &$scope[key($scope)];
            // add menu property to object if it does not exist
            if (false === isset($scope->menu)) {
                $scope->menu = [];
            }
            // get reference to menu
            $scope = &$scope->menu;
        }

        // add entry to scope
        // because scope is a reference the res variable is altered at the correct place, too
        $scope[] = $entry;

        // we're done
        return $res;

    }

    /**
     * get a list of links to articles for current client and language
     * @return array The array of articles filled with link objects
     */
    private function _buildLinkList() {
        global $client, $lang;

        $catTree = new cApiCategoryTreeCollection();
        $catList = $catTree->getCategoryTreeStructureByClientIdAndLanguageId($client, $lang);

        $linkList = [];
        foreach ($catList as $catEntry) {
            $tmp_catname = $catEntry['name'];
            if ($catEntry['visible'] == 0) {
                $tmp_catname = "[" . $tmp_catname . "]";
            }
            $listEntry = (object)[
                'title' => $tmp_catname,
                'value' => 'front_content.php?idcat=' . $catEntry['idcat'],
            ];

            $linkList = $this->_addToWoodTree($linkList, (int) $catEntry['level'], $listEntry);

            $options = [
                'idcat'     => $catEntry['idcat'],
                // order by title
                'order'     => 'title',
                // order ascending
                'direction' => 'asc',
                // include start articles
                'start'     => true,
                // show offline articles
                'offline'   => true,
            ];

            // create cArticleCollector instance with specified options
            $articleCollector = new cArticleCollector($options);

            // add subcategories to communicate category structure
            if (0 === count($articleCollector)) {
                continue;
            }

            $listEntry->menu = [];
            $listEntry->menu[] = (object)[
                'title' => $tmp_catname . ' ' . i18n('Category'),
                'value' => 'front_content.php?idcat=' . $catEntry['idcat'],
            ];

            foreach ($articleCollector as $articleLanguage) {
                $tmp_title = $articleLanguage->get("title");

                if (cString::getStringLength($tmp_title) > 32) {
                    $tmp_title = cString::getPartOfString($tmp_title, 0, 32);
                }

                $is_start = isStartArticle($articleLanguage->get('idartlang'), $catEntry['idcat'], $lang);

                if ($is_start) {
                    $tmp_title .= "*";
                }

                if ('0' === $articleLanguage->get("online")) {
                    $tmp_title = "[" . $tmp_title . "]";
                }
                $articleEntry = new stdClass();
                $articleEntry->title = $tmp_title;
                $articleEntry->value = 'front_content.php?idart=' . $articleLanguage->get('idart');
                $listEntry->menu[] = $articleEntry;
            }
        }

        return $linkList;
    }

    /**
     * Output the created list as JSON.
     */
    private function _printList($list) {
        echo json_encode($list);
    }
}
