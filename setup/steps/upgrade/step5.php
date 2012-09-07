<?php
 /**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Step 6 of installation
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    1.0.1
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2008-03-14
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id: step5.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

checkAndInclude("steps/forms/additionalplugins.php");

$cSetupSetupSummary = new cSetupAdditionalPlugins(5, "upgrade4", "upgrade6");
$cSetupSetupSummary->render();
?>