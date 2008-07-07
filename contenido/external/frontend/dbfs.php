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
 *
 * @package    Contenido Backend <Area>
 * @version    <version>
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *  created  unknown
 *  modified 2008-06-16, H. Librenz - Hotfix: checking for potential unsecure calling 
 *  modified 2008-07-04, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}

if (isset($_REQUEST['contenido_path'])) {
    die ('Illegal call!');
}
include_once ("config.php");
include_once ($contenido_path . "includes/startup.php");
cInclude("includes", "functions.general.php");

cInclude("classes", "class.dbfs.php");


if ($contenido)
{
    page_open(array('sess' => 'Contenido_Session',
                    'auth' => 'Contenido_Challenge_Crypt_Auth',
                    'perm' => 'Contenido_Perm'));

} else {
    page_open(array('sess' => 'Contenido_Frontend_Session',
                    'auth' => 'Contenido_Frontend_Challenge_Crypt_Auth',
                    'perm' => 'Contenido_Perm'));
}

/* Shorten load time */
$client = $load_client;

$dbfs = new DBFSCollection;
$dbfs->outputFile($file);

page_close();

?>