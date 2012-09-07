<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Template Configurations
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.2.0
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2004-02-24
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *
 *   $Id: config.xml.php 309 2008-06-26 10:06:56Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

global $cfg;

/* IMPORTANT! Put your modifications into the file "config.local.php"
   to prevent that your changes are overwritten during a system update. */

/* File is deprecated, not needed anymore !!! */

$cfg['lang']['de_DE']                   = 'lang_de_DE.xml';
$cfg['lang']['en_US']                   = 'lang_en_US.xml';
$cfg['lang']['fr_FR']                   = 'lang_fr_FR.xml';
$cfg['lang']['nl_NL']                   = 'lang_nl_NL.xml';
$cfg['lang']['he_HE']                   = 'lang_he_HE.xml';

?>