<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * TINYMCE 1.45rc1 PHP WYSIWYG interface
 * Generates file/link list for editor
 *
 * Requirements:
 * @con_php_req 5
 * @con_notice
 * TINYMCE 1.45rc1 Fileversion
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
        $aUplList = $oApiUploadCol->fetchArray($oApiUploadCol->primaryKey, array('idclient', 'filetype', 'filename'));
        foreach ($aUplList as $uplItem) {
            $imageItem = new stdClass();
            $imageItem->title = $uplItem['dirname'] . $uplItem['filename'];
            $imageItem->value = $cfgClient[$client]['upload'].$uplItem['dirname'] . $uplItem['filename'];
            $imageList[] = $imageItem;
        }
    
        return $imageList;
    }
    
    /**
     * get a list of links to articles for current client and language
     * @return array The array of articles filled with link objects
     */
    protected function _buildLinkList() {
        global $client, $lang;
        
        $linkList = array();
        
        $catTree = new cApiCategoryTreeCollection();
        $catList = $catTree->getCategoryTreeStructureByClientIdAndLanguageId($client, $lang);
        foreach ($catList as $catEntry) {
            $tmp_catname = $catEntry['name'];
            $spaces = "";

            $spaces = str_repeat("&nbsp;&nbsp;", $catEntry['level']);

            if ($catEntry['visible'] == 0) {
                $tmp_catname = "[" . $tmp_catname . "]";
            }
            $listEntry = new stdClass();
            $listEntry->title = $spaces . $tmp_catname;
            $listEntry->value = 'front_content.php?idcat=' . $catEntry['idcat'];

            $linkList[] = $listEntry;

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
            foreach ($articleCollector as $articleLanguage) {
                $tmp_title = $articleLanguage->get("title");

                if (strlen($tmp_title) > 32) {
                    $tmp_title = substr($tmp_title, 0, 32);
                }

                $is_start = isStartArticle($articleLanguage->get('idartlang'), $catEntry['idcat'], $lang);

                if ($is_start) {
                    $tmp_title .= "*";
                }
                if ($articleLanguage->get("online") == 0) {
                    $tmp_title = "[" . $tmp_title . "]";
                }
                $listEntry = new stdClass();
                $listEntry->title = '&nbsp;&nbsp;' . $spaces . '|&nbsp;&nbsp;' . $tmp_title;
                $listEntry->value = 'front_content.php?idart='.$articleLanguage->get('idart');
                $linkList[] = $listEntry;
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