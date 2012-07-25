<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Plugin configurations for frontend users
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Plugins
 * @subpackage Frontendusers
 * @version    0.2
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  Unknown
 *   modified 2005-05-24
 *   modified 2008-06-26 Timo Trautmann - Plugin include replaced with existing generic function
 *
 *   $Id$:
 * }}
 *
 */

cInclude("includes", "functions.general.php");
scanPlugins("frontendusers");
?>