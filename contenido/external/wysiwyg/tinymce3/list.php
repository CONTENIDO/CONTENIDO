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
 * @version    0.0.5
 * @author     Martin Horwath, horwath@dayside.net
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  2005-06-10
 *   $Id: list.php 739 2008-08-27 10:37:54Z timo.trautmann $:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
$contenido_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../')) . '/';

if (!is_file($contenido_path . 'includes/startup.php')) {
    die("<h1>Fatal Error</h1><br>Couldn't include CONTENIDO startup.");
}
include_once($contenido_path . 'includes/startup.php');

// include editor config/combat file
include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');

$db2 = cRegistry::getDb();

$arg_seperator = '&amp;';

$output = '';

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

            $db2->query($sql2);

            while ($db2->nextRecord()) {

                $tmp_title = $db2->f("title");

                if (strlen($tmp_title) > 32) {
                    $tmp_title = substr($tmp_title, 0, 32);
                }

                $is_start = isStartArticle($db2->f("idartlang"), $db2->f("idcat"), $lang);

                if ($is_start) {
                    $tmp_title .= "*";
                }
                if ($db2->f("online") == 0) {
                    $tmp_title = "[" . $tmp_title . "]";
                }
                $output .= ",\n\t".'["&nbsp;&nbsp;'.$spaces.'|&nbsp;&nbsp;'.$tmp_title.'", "'."front_content.php?idart=".$db2->f("idart").'"]';
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