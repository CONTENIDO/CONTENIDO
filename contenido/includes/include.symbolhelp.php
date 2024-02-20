<?php

/**
 * This file contains the symbol help backend page.
 * TODO: check, if this page is used and if not, where it can be reintegrated
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['symbolhelp']);
