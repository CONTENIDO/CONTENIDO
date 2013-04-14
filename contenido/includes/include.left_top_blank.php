<?php
/**
 * This file contains the blank lef top frame backend page.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tpl->reset();
$tpl->generate($cfg["path"]["templates"] . $cfg['templates']['left_top_blank']);
?>