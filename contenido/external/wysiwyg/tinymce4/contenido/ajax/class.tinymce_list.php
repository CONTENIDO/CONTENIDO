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
            case 'link':
                $list = $this->buildLinkList();
                break;
            default:
                // just output an empty list for unknown mode
        }
        
        $this->printList($list);
    }
    
    private function buildLinkList() {
        global $client, $lang;
        
        $linkList = array();
        
        $catTree = new cApiCategoryTreeCollection();
        $catList = $catTree->getCategoryTreeStructureByClientIdAndLanguageId($client, $lang);
        foreach ($catList as $catEntry) {
            $tmp_catname = $catEntry['name'];
            $spaces = "";

            $spaces = str_repeat("&nbsp;&nbsp;", $catEntry['level']);

            if ($loop) {
                $output .= ",";
            } else {
                $loop = true;
            }

            if ($catEntry['visible'] == 0) {
                $tmp_catname = "[" . $tmp_catname . "]";
            }
            $listEntry = new stdClass();
            $listEntry->title = $spaces . $tmp_catname;
            $listEntry->value = 'front_content.php?idcat=' . $catEntry['idcat'];

            $linkList[] = $listEntry;
            var_dump($linkList);
            $output .= "\n\t".'["'.$spaces.$tmp_catname.'", "'."front_content.php?idcat=".$catEntry['idcat'].'"]';

            $artList = new cApiCategoryArticleCollection();
// return $linkList;
            $sql2 = "SELECT
                         *
                     FROM
                         ".$cfg["tab"]["cat_art"]." AS a,
                        ".$cfg["tab"]["art"]." AS b,
                        ".$cfg["tab"]["art_lang"]." AS c
                     WHERE
                        a.idcat = '".$$catEntry['idcat']."' AND
                        b.idart = a.idart AND
                        c.idart = a.idart AND
                        c.idlang = '".cSecurity::toInteger($lang)."' AND
                        b.idclient = '".cSecurity::toInteger($client)."'
                     ORDER BY
                        c.title ASC";
die($sql2);
            global $db;
            $db->query($sql2);

            while ($db->nextRecord()) {

                $tmp_title = $db->f("title");

                if (strlen($tmp_title) > 32) {
                    $tmp_title = substr($tmp_title, 0, 32);
                }

                $is_start = isStartArticle($db->f("idartlang"), $db->f("idcat"), $lang);

                if ($is_start) {
                    $tmp_title .= "*";
                }
                if ($db->f("online") == 0) {
                    $tmp_title = "[" . $tmp_title . "]";
                }
                $output .= ",\n\t".'["&nbsp;&nbsp;'.$spaces.'|&nbsp;&nbsp;'.$tmp_title.'", "'."front_content.php?idart=".$db->f("idart").'"]';
            }
        }
        var_dump($output);
        $linkList[] = new stdClass("tut");
        
        return $linkList;
    }
    
    private function printList($list) {
        echo json_encode($list);
    }
}

echo "\n\n";
// return;

switch ($_REQUEST['mode']) {
    case 'link':
        $sql = "SELECT
                    *
                FROM
                    ".$cfg["tab"]["cat_tree"]." AS a,
                    ".$cfg["tab"]["cat_lang"]." AS b,
                    ".$cfg["tab"]["cat"]." AS c
                WHERE
                    a.idcat = b.idcat AND
                    c.idcat = a.idcat AND
                    c.idclient = '".cSecurity::toInteger($client)."' AND
                    b.idlang = '".cSecurity::toInteger($lang)."'
                ORDER BY
                    a.idtree";

        $db->query($sql);

        $output .= "var tinyMCELinkList = new Array(";

        $loop = false;

        while ($db->nextRecord()) {
            $tmp_catname = $db->f("name");
            $spaces = "";

            $spaces = str_repeat("&nbsp;&nbsp;", $db->f("level"));

            if ($loop) {
                $output .= ",";
            } else {
                $loop = true;
            }

            if ($db->f("visible") == 0) {
                $tmp_catname = "[" . $tmp_catname . "]";
            }

            $output .= "\n\t".'["'.$spaces.$tmp_catname.'", "'."front_content.php?idcat=".$db->f("idcat").'"]';

            $sql2 = "SELECT
                         *
                     FROM
                         ".$cfg["tab"]["cat_art"]." AS a,
                        ".$cfg["tab"]["art"]." AS b,
                        ".$cfg["tab"]["art_lang"]." AS c
                     WHERE
                        a.idcat = '".$db->f("idcat")."' AND
                        b.idart = a.idart AND
                        c.idart = a.idart AND
                        c.idlang = '".cSecurity::toInteger($lang)."' AND
                        b.idclient = '".cSecurity::toInteger($client)."'
                     ORDER BY
                        c.title ASC";

            $db->query($sql2);

            while ($db->nextRecord()) {

                $tmp_title = $db->f("title");

                if (strlen($tmp_title) > 32) {
                    $tmp_title = substr($tmp_title, 0, 32);
                }

                $is_start = isStartArticle($db->f("idartlang"), $db->f("idcat"), $lang);

                if ($is_start) {
                    $tmp_title .= "*";
                }
                if ($db->f("online") == 0) {
                    $tmp_title = "[" . $tmp_title . "]";
                }
                $output .= ",\n\t".'["&nbsp;&nbsp;'.$spaces.'|&nbsp;&nbsp;'.$tmp_title.'", "'."front_content.php?idart=".$db->f("idart").'"]';
            }
        }

        $output .= "\n);";

        break;

    case 'image':
        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".cSecurity::toInteger($client)."' AND filetype IN ('gif', 'jpg', 'jpeg', 'png') ORDER BY dirname, filename ASC";
        $db->query($sql);

        $output .= "var tinyMCEImageList = new Array(";

        $loop = false;

        while ($db->nextRecord()) {
            if ($loop) {
                $output .= ",";
            } else {
                $loop = true;
            }

            $output .= "\n\t".'["'.$db->f("dirname").$db->f("filename").'", "'.$cfgClient[$client]["upload"].$db->f("dirname").$db->f("filename").'"]';
        }

        $output .= "\n);";
        break;

    case 'media':
        $sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='".cSecurity::toInteger($client)."' AND filetype IN ('swf','dcr','mov','qt','mpg','mpg3','mpg4','mpeg','avi','wmv','wm','asf','asx','wmx','wvx','rm','ra','ram') ORDER BY dirname, filename ASC";
        $db->query($sql);

        $output .= "var tinyMCEMediaList = new Array(";

        $loop = false;

        while ($db->nextRecord()) {
            if ($loop) {
                $output .= ",";
            } else {
                $loop = true;
            }

            $output .= "\n\t".'["'.$db->f("dirname").$db->f("filename").'", "'.$cfgClient[$client]["upload"].$db->f("dirname").$db->f("filename").'"]';
        }

        $output .= "\n);";
        break;

    default:
}

echo $output;

?>