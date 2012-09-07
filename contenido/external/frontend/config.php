<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * <Description>
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_template <Templatefiles>
 * @con_notice <Notice>
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    <version>
 * @author     <author>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  <date>
 *   modified 2008-07-04, bilal arslan, added security fix
 *
 *   $Id: config.php 739 2008-08-27 10:37:54Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}
 
// Relative path to contenido directory, for all inclusions, in most cases: "../contenido/"
$contenido_path = "!PATH!";

// If language isn't specified, set this client and language (ID)
$load_lang		= "!LANG!";
$load_client	= "!CLIENT!";

/* Various debugging options */
$frontend_debug["container_display"]		= false;
$frontend_debug["module_display"]			= false;
$frontend_debug["module_timing"]			= false;
$frontend_debug["module_timing_summary"]	= false;

/* Set to 1 to brute-force module regeneration */
$force = 0;

?>
