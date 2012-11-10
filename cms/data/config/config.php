<?php
/**
 * Contains clients basic configuration.
 *
 * @package Frontend
 * @subpackage Configuration
 * @version SVN Revision $Rev:$
 * @version SVN Id $Id: config.php 2900 2012-08-15 00:37:20Z xmurrix $
 *
 * @author System
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// Relative path to CONTENIDO directory, for all inclusions, in most cases: '../contenido/'
$contenido_path = 'C:/projects/workspace/contenido_example/contenido/';

// If language isn't specified, set this client and language (ID)
$load_lang   = '1';
$load_client = '1';

// Various debugging options
$frontend_debug['container_display']     = false;
$frontend_debug['module_display']        = false;
$frontend_debug['module_timing']         = false;
$frontend_debug['module_timing_summary'] = false;

// Set to 1 to brute-force module regeneration
$force = 0;

?>