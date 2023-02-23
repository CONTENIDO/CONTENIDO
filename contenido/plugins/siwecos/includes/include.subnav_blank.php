<?php
/**
 *
 * @package Plugin
 * @subpackage SIWECOS
 * @author Fulai Zhang <fulai.zhang@4fb.de>
 * @copyright four for business AG
 * @link https://www.4fb.de
 */

/**
 * This file contains the default blank sub navigation frame backend page.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cTemplate $tpl
 * @var array $cfg
 */

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav_blank']);