<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Database file system output. Expects the request parameter 'file' containing
 * the file to output.
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Frontend
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
 *   modified 2008-06-16, H. Librenz - Hotfix: checking for potential unsecure calling 
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2011-07-26, Murat Purc, cleaned up, optimized code and some documentation
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

$contenido_path = '';
// Include the config file of the frontend to init the Client and Language Id
include_once('config.php');

// Contenido startup process
include_once($contenido_path . 'includes/startup.php');

// Initialize db, session, authentication and permission
frontendPageOpen();

// Shorten load time
$client = $load_client;

$dbfs = new DBFSCollection();
$dbfs->outputFile($file);

page_close();

?>