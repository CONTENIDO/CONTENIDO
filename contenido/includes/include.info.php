<?php

/**
 * This file contains the backend page for general information about CONTENIDO.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// Generate template
$tpl->reset();

$message = sprintf(i18n("You can find many information and a community forum on the <a href=\"http://forum.contenido.org\" target=\"_blank\">CONTENIDO Portal</a>"));

$tpl->set('s', 'VERSION', CON_VERSION);
$tpl->set('s', 'PORTAL', $message);
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['info']);

?>