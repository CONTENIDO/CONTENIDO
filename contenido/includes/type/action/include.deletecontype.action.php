<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * deletecontype action
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

cInclude("includes", "functions.tpl.php");
include(cRegistry::getBackendPath() . $cfg["path"]["includes"] . "include.con_content_list.php");
?>