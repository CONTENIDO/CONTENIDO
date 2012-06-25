<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * con_deleteart action
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

if(isset($_POST['idarts'])) {
    //delete articles (bulk editing)
    $idarts = explode('+', $_POST['idarts']);
    foreach( $idarts as $article) {
        conDeleteArt ($article);
    }
}else  {
    conDeleteArt ($idart);
}
$tmp_notification = $notification->returnNotification("info", i18n("Article deleted"));
?>