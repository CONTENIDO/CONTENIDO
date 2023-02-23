<?php

/**
 * Backend action file savecontenttype
 *
 * @package    Core
 * @subpackage Backend
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// rights are being checked by the include file itself
cInclude("includes", "functions.tpl.php");
include(cRegistry::getBackendPath() . $cfg["path"]["includes"] . "include.con_content_list.php");
