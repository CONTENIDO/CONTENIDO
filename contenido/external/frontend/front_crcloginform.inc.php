<?php
/**
 * This file manages the login form page in the frontend.
 *
 * @package          Core
 * @subpackage       Frontend
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg;

// Include clients login form handler
include(cRegistry::getBackendPath() . $cfg['path']['includes'] . '/frontend/include.front_crcloginform.inc.php');

?>