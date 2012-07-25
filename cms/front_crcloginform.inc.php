<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Login form
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO Frontend
 * @version    0.5.2
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2003-01-2003
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $cfg;

// Include clients login form handler
include($cfg['path']['contenido'] . $cfg['path']['includes'] . '/frontend/include.front_crcloginform.inc.php');

?>