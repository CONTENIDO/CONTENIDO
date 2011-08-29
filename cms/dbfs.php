<?php
/**
 * Project: 
 * CONTENIDO Content Management System
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
 * @package    CONTENIDO Backend <Area>
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * 
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-06-16, H. Librenz - Hotfix: checking for potential unsecure calling
 *   modified 2008-07-03, bilal arslan, added security fix
 *   modified 2010-05-20, Murat Purc, standardized CONTENIDO startup and security check invocations, see [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

$contenido_path = '';
# include the config file of the frontend to init the Client and Language Id
include_once ("config.php");

// CONTENIDO startup process
include_once ($contenido_path . 'includes/startup.php');

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