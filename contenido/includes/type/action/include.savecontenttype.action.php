<?php

/**
 * Backend action file savecontenttype
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// rights are being checked by the include file itself
cInclude("includes", "functions.tpl.php");
include(cRegistry::getBackendPath() . $cfg["path"]["includes"] . "include.con_content_list.php");

?>