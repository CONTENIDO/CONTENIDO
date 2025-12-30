<?php

/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Additional CONTENIDO frontend configuration for "min".
 *
 * Usage:
 * - If you don't have a configuration file "cms/data/config/{environment}/config.local.php",
 *   then create the file "config.local.php".
 * - Add following line to the configuration file "config.local.php":
 *   include_once __DIR__ . '/config.min.php';
 *
 * @package     CONTENIDO_Extension
 * @subpackage  mpMinify
 * @author      Murat Purç <murat@purc.de>
 * @copyright   Copyright (c) 2012-2019 Murat Purç (https://www.purc.de)
 * @license     https://www.gnu.org/licenses/gpl-2.0.html - GNU General Public License, version 2
 */

global $min_cFrontendEnable;

// Enable usage of minify in layouts
$min_cFrontendEnable = true;

