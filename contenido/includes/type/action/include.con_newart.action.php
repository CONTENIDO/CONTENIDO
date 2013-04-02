<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * con_newart action
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend Includes
 * @version    0.0.1
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/* Code for action
   'con_newart' */
$sql = "SELECT
            a.idtplcfg,
            a.name
        FROM
            ".$cfg["tab"]["cat_lang"]." AS a,
            ".$cfg["tab"]["cat"]." AS b
        WHERE
            a.idlang    = '".$lang."' AND
            b.idclient  = '".$client."' AND
            a.idcat     = '".$idcat."' AND
            b.idcat     = a.idcat";

$db->query($sql);
$db->nextRecord();

if ($db->f("idtplcfg") != 0) {
    $newart = true;
} else {
    $page = new cGuiPage("con_newart");
    $page->displayCriticalError(i18n("This category has no templates assigned."));
    $page->render();
}
?>