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
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
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

cRegistry::bootstrap(array(
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission'
));

// include editor config/combat file
include(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'config.php');

$db = cRegistry::getDb();

cInclude('includes', 'functions.lang.php');

$mediaList = new cTinyMCE4List($_GET['mode']);

class cTinyMCE4List {
    public function __construct($mode = null) {
        // output an empty list for no specified mode
        if (false === isset($mode)) {
            echo '[]';
            return;
        }

        $list = array();
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
        }
        
        $this->printList($list);
    }
    
    
    /**
     * get a list of images that is accessible for tinymce
     * @return array The array of images filled with upload objects
     */
    protected function _buildImageList() {
        global $client, $cfgClient;
    
        $imageList = array();
    
        // get needed data using cApiUploadCollection class
        $oApiUploadCol = new cApiUploadCollection();
        // get uploads for current client
        // filetype can be either gif, jpg, jpeg or png
        $selectClause = "idclient='" . cSecurity::toInteger($client) . "' AND filetype IN ('gif', 'jpg', 'jpeg', 'png')";
        $oApiUploadCol->select($selectClause, '', 'dirname, filename ASC');
        $aUplList = $oApiUploadCol->fetchArray($oApiUploadCol->primaryKey, array('idclient', 'dirname', 'filetype', 'filename'));
        foreach ($aUplList as $uplItem) {
            $imageItem = new stdClass();
            $imageItem->title = $uplItem['dirname'] . $uplItem['filename'];
            $imageItem->value = $cfgClient[$client]['upload'] . $uplItem['dirname'] . $uplItem['filename'];
            
            $imageList[] = $imageItem;
        }
    
        return $imageList;
    }

    private function _gotoCatLvl($cat, $lvl = false) {
        if (isset($cat->menu)) {
            $this->_lastMenu($cat->menu);
        } else {
            return $cat;
        }
        //$lastCat->menu = array();
    }
    
    /**
     * get a list of links to articles for current client and language
     * @return array The array of articles filled with link objects
     */
    protected function _buildLinkList(&$subMenuItem = null, &$lastCat = null, $idx = -1, $lastLevel = 0) {
        global $client, $lang;
        
        $linkList = array();
        
        $catTree = new cApiCategoryTreeCollection();
        $catList = $catTree->getCategoryTreeStructureByClientIdAndLanguageId($client, $lang);
//         $lastLevel = 0;
        if (false === isset($lastCat)) {
            $lastCat = null;
        }
        
        $curIdx = 0;
        foreach ($catList as $catEntry) {
            $curIdx++;
            if ($idx >= 0) {
                if ($idx !== $curIdx) {
                    continue;
                }

            }
            
            if (isset($lastCat)) {
                $subItem = (object) array('title' => $catEntry['name'],
                                           'value' => 'front_content.php?idcat=' . $catEntry['idcat']);

                if ($lastLevel < intval($catEntry['level'])) {
                    // we are processing a subcat of lastCat
                    
                    // create new submenu if none exists yet
                    if (isset($subMenuItem)) {
var_dump($subMenuItem);
//                         if (intval($catEntry['level'] ))
                        $subMenuItem->menu = array();
                        $subMenuItem->menu[] = $subItem;
                    } else {
// var_dump($lastCat);
                        $lastCat->menu = $this->_gotoCatLvl($lastCat);
                        $lastCat->menu = array();
                        $lastCat->menu[] = $subItem;
                    }


                    $this->_buildLinkList($subItem, $lastCat, $curIdx, (int) $catEntry['level']);
                } else {
                    
                    // we are processing a category in same or higher level as last cat was
                    if (0 === $lastLevel) {
                        $linkList[] = $lastCat;
                    } else {//var_dump($linkList);
//                         var_dump($catEntry['level'] . ' - ' . $lastLevel . ' ' . $catEntry['name']);
                        if ($lastLevel > $catEntry['level']) {
                            $linkList[] = $lastCat;
                            
//                             $curLvl = $this->_gotoCatLvl($linkList, intval($catEntry['level']));
//                             $curLvl->menu[] = $subItem;
                        } else {
                            $linkList[] = $lastCat;
                        }
                    }
                }
            }

            $tmp_catname = $catEntry['name'];
            if ($catEntry['visible'] == 0) {
                $tmp_catname = "[" . $tmp_catname . "]";
            }
            
            $listEntry = new stdClass();
            $listEntry->title = $tmp_catname;
            $listEntry->value = 'front_content.php?idcat=' . $catEntry['idcat'];
            
            if (!isset($lastCat)) {
                $linkList[] = $listEntry;
            }

            $lastCat = $listEntry;


            $lastLevel = (int) $catEntry['level'];
continue;
            $options = array();
            $options['idcat'] = $catEntry['idcat'];
            // order by title
            $options['order'] = 'title';
            // order ascending
            $options['direction'] = 'asc';
            // include start articles
            $options['start'] = true;
            // show offline articles
            $options['offline'] = true;


            // create cArticleCollector instance with specified options
            $articleCollector = new cArticleCollector($options);
            
            // add subcategories to communicate category structure
            if (0 === count($articleCollector)) {
                continue;
            }
            
            $listEntry->menu = array();
            $listEntry->menu[] = (object) array('title' => $tmp_catname . ' ' . i18n('Category'),
                                                'value' => 'front_content.php?idcat=' . $catEntry['idcat']
            );
            
            foreach ($articleCollector as $articleLanguage) {
                $tmp_title = $articleLanguage->get("title");
            
                if (strlen($tmp_title) > 32) {
                    $tmp_title = substr($tmp_title, 0, 32);
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

    // output the created list as JSON
    private function printList($list) {
        echo json_encode($list);
    }
}
?>