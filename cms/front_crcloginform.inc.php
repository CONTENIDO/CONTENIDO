<?php
/**
 * Frontend login form.
 *
 * @package Frontend
 * @subpackage Form
 * @version SVN Revision $Rev:$
 * @version SVN Id $Id: front_crcloginform.inc.php 3417 2012-10-04 19:26:38Z xmurrix $
 *
 * @author Jan Lengowski
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $cfg;

// Include clients login form handler
include(cRegistry::getBackendPath() . $cfg['path']['includes'] . '/frontend/include.front_crcloginform.inc.php');

?>