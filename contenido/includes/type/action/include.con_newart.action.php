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
$db->next_record();

if ($db->f("idtplcfg") != 0) {
    $newart = true;
} else {
    $noti_html = '<table cellspacing="0" cellpadding="2" border="0">

                    <tr class="text_medium">
                        <td colspan="2">
                            <b>Fehler bei der Erstellung des Artikels</b><br><br>
                            Der Kategorie ist kein Template zugewiesen.
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>

                  </table>';
    $code = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
    <head>
        <title>Error</title>
        <link rel="stylesheet" type="text/css" href="'.$cfg["path"]["contenido_fullhtml"].$cfg["path"]["styles"].'contenido.css"></link>
    </head>
    <body style="margin: 10px">'.$notification->returnNotification("error", $noti_html).'</body>
</html>';

    echo $code;
}
?>