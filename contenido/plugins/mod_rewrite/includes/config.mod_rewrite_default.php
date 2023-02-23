<?php
/**
 * Plugin Advanced Mod Rewrite default settings. This file will be included if
 * mod rewrite settings of an client couldn't loaded.
 *
 * Containing settings are taken over from CONTENIDO-4.6.15mr setup installer
 * template being made originally by stese.
 *
 * NOTE:
 * Changes in these Advanced Mod Rewrite settings will affect all clients, as long
 * as they don't have their own configuration.
 * PHP needs write permissions to the folder, where this file resides. Mod Rewrite
 * configuration files will be created in this folder.
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     https://www.contenido.org/license/LIZENZ.txt
 * @link        https://www.4fb.de
 * @link        https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg;

// Use advanced mod_rewrites  ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use'] = 0;

// Path to the htaccess file with trailing slash from domain-root!
$cfg['mod_rewrite']['rootdir'] = '/';

// Check path to the htaccess file ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['checkrootdir'] = 1;

// Start TreeLocation from Root Tree (set to 1) or get location from first category (set to 0)
$cfg['mod_rewrite']['startfromroot'] = 0;

// Prevent Duplicated Content, if startfromroot is enabled ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['prevent_duplicated_content'] = 0;

// is multilingual? ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use_language'] = 0;

// use language name in url? ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use_language_name'] = 0;

// is multi-client in only one directory? ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use_client'] = 0;

// use client name in url? ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use_client_name'] = 0;

// use lowercase url? ( 1 = yes, 0 = none )
$cfg['mod_rewrite']['use_lowercase_uri'] = 1;

// file extension for article links
$cfg['mod_rewrite']['file_extension'] = '.html';

// The percentage if the category name have to match with database names.
$cfg['mod_rewrite']['category_resolve_min_percentage'] = '75';

// Add start article name to url (1 = yes, 0 = none)
$cfg['mod_rewrite']['add_startart_name_to_url'] = 1;

// Default start article name to use, depends on active add_startart_name_to_url
$cfg['mod_rewrite']['default_startart_name'] = 'index';

// Rewrite urls on generating the code for the page. If active, the responsibility will be
// outsourced to module outputs, and you have to adapt the module outputs manually. Each output of
// internal article/category links must be processed by using $sess->url. (1 = yes, 0 = none)
$cfg['mod_rewrite']['rewrite_urls_at_congeneratecode'] = 0;

// Rewrite urls on output of html code at front_content.php. Is the old way, and doesn't require
// adapting of module outputs. On the other hand usage of this way will be slower than rewriting
// option above. (1 = yes, 0 = none)
$cfg['mod_rewrite']['rewrite_urls_at_front_content_output'] = 1;

// Following five settings write urls like this one:
//     www.domain.de/category1-category2.articlename.html
// Changes of these settings causes a reset of all aliases, see Advanced Mod Rewrite settings in
// backend.
// NOTE: category_seperator and article_seperator must contain different character.
// Separator for categories
$cfg['mod_rewrite']['category_seperator'] = '/';

// Separator between category and article
$cfg['mod_rewrite']['article_seperator'] = '/';

// Word seperator in category names
$cfg['mod_rewrite']['category_word_seperator'] = '-';

// Word seperator in article names
$cfg['mod_rewrite']['article_word_seperator'] = '-';

// Routing settings for incoming urls. Here you can define routing rules as follows:
// $cfg['mod_rewrite']['routing'] = [
//    '/a_incoming/url/foobar.html' => '/new_url/foobar.html',  # route /a_incoming/url/foobar.html to /new_url/foobar.html
//    '/cms/' => '/' # route /cms/ to / (doc root of client)
// ];
$cfg['mod_rewrite']['routing'] = [];

// Redirect invalid articles to errorpage (1 = yes, 0 = none)
$cfg['mod_rewrite']['redirect_invalid_article_to_errorsite'] = 0;

