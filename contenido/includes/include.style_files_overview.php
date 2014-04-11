<?php
/**
 * This file contains the backend page for style files overview.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Willi Man, Olaf Niemann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$files = new cGuiFileOverview($cfgClient[$client]['css']['path'], stripslashes($_REQUEST['file']), 'css');
$files->setFileExtension('css');
$files->render();
