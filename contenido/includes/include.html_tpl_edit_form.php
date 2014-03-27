<?php
/**
 * This file contains the backend page for editing html template files.
 * @fixme: Rework logic for creation of cApiFileInformation entries
 * It may happpen, that we have already a file but not a entry or vice versa!
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Willi Man
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$editor = new cGuiSourceEditor($_REQUEST['tmp_file']);
$editor->render();
