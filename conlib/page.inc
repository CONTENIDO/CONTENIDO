<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Base page setup for CONTENIDO.
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    CONTENIDO Core
 * @version    1.1.2
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created  2002-07-21
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2011-07-22, Murat Purc, added instantiation of db in page_open(), documenting and formatting.
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Initializes the database abstraction layer, session, authentication and 
 * permissions Handler..
 *
 * Instantiates following global variables if needed:
 * - $db, $sess, $auth, $perm
 *
 * @global  array  $_PHPLIB
 * @global  object  $db    Optional
 * @global  object  $sess  Optional
 * @global  object  $auth  Optional
 * @global  object  $perm  Optional
 */
function page_open($feature)
{
    global $_PHPLIB;

    // Instantiate database feature if needed
    if (isset($feature['db'])) {
        global $db;
        if (!isset($db)) {
            $db = new $feature['db'];
        }
    }

    // Enable sess and all dependent features.
    if (isset($feature['sess'])) {
        global $sess;
        $sess = new $feature['sess'];
        $sess->start();

        // The auth feature depends on sess
        if (isset($feature['auth'])) {
            global $auth;

            if (!isset($auth)) {
                $auth = new $feature['auth'];
            }
            $auth->start();

            // The perm feature depends on auth and sess
            if (isset($feature['perm'])) {
                global $perm;
                if (!isset($perm)) {
                    $perm = new $feature['perm'];
                }
            }

            // The user feature depends on auth and sess
            if (isset($feature['user'])) {
                global $user;
                if (!isset($user)) {
                    $user = new $feature['user'];
                }
                $user->start($auth->auth['uid']);
            }
        }

        // Load the auto_init-File, if one is specified.
        if (($sess->auto_init != '') && !$sess->in) {
            $sess->in = 1;
            include($_PHPLIB['libdir'] . $sess->auto_init);
            if ($sess->secure_auto_init != '') {
                $sess->freeze();
            }
        } 
    }
}

/**
 * Closes a page, shutting down session and user instances
 *
 * @global  object  $sess
 * @global  object  $user
 */
function page_close()
{
    global $sess, $user;

    if (isset($sess)) {
        $sess->freeze();
        if (isset($user) && method_exists($user, 'freeze')) {
            $user->freeze();
        }
    }
}

/**
 * Loads all session instances into superglobal $GLOBALS
 *
 * @param  array  $session
 */
function sess_load($session)
{
    reset($session);
    while (list($k,$v) = each($session)) {
        $GLOBALS[$k] = new $v;
        $GLOBALS[$k]->start();
    }
}

/**
 * Saves all session instances in available in $GLOBALS
 *
 * @param  array  $session
 */
function sess_save($session)
{
    reset($session);
    while (list(,$v) = each($session)) {
        $GLOBALS[$v]->freeze();
    }
}

?>