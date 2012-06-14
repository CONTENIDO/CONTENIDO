<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * con_makeonline action
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
	$idarts = explode('+', $_POST['idarts']);
	$online = $_POST['invert'] == 1 ? 0 : 1;
	conMakeOnlineBulkEditing($idarts, $_POST['idlang'], $online);
}else {
	conMakeOnline($idart, $lang);
}
?>