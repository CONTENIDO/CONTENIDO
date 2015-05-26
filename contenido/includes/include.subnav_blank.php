<?php

/**
 * This file contains the default blank sub navigation frame backend page.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav_blank']);

?>