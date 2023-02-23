<?php

/**
 * This file contains the right top frame backend page for the plugin cronjob overview.
 *
 * @package    Plugin
 * @subpackage CronjobOverview
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * @var array $cfg
 */

$tpl = new cTemplate();
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
