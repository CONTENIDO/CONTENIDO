<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO setup
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 *
 */

 echo '<!-- Hello begin -->';

if(!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}



checkAndInclude("steps/forms/setupsummary.php");



$cSetupSetupSummary = new cSetupSetupSummary(7, "setup6", "doinstall");
$cSetupSetupSummary->render();
?>