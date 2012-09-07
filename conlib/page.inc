<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Session Management for PHP3
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    1.1.1.1
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2002-07-21
 *   modified 2008-07-04, bilal arslan, added security fix
 *
 *   $Id: page.inc 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}


function page_open($feature) {
  global $_PHPLIB;

  # enable sess and all dependent features.
  if (isset($feature["sess"])) {
    global $sess;
    $sess = new $feature["sess"];
    $sess->start();
    
    # the auth feature depends on sess
    if (isset($feature["auth"])) {
      global $auth;
      
      if (!isset($auth)) {
        $auth = new $feature["auth"];
      }
      $auth->start();
  
      
      # the perm feature depends on auth and sess
      if (isset($feature["perm"])) {
        global $perm;
        
        if (!isset($perm)) {
          $perm = new $feature["perm"];
        }
      }

      # the user feature depends on auth and sess
      if (isset($feature["user"])) {
        global $user;
        
        if (!isset($user)) {
          $user = new $feature["user"];
        }
        $user->start($auth->auth["uid"]);
      }
    }

    ## Load the auto_init-File, if one is specified.
    if (($sess->auto_init != "") && !$sess->in) {
      $sess->in = 1;
      include($_PHPLIB["libdir"] . $sess->auto_init);
      if ($sess->secure_auto_init != "") {
        $sess->freeze();
      }
    } 
  }
}

function page_close() {
  global $sess, $user;

  if (isset($sess)) {
    $sess->freeze();
    if (isset($user) && method_exists($user, "freeze")) {
      $user->freeze();
    }
  }
}

function sess_load($session) {
  reset($session);
  while (list($k,$v) = each($session)) {
    $GLOBALS[$k] = new $v;
    $GLOBALS[$k]->start();
  }
}

function sess_save($session) {
  reset($session);
  while (list(,$v) = each($session)) {
    $GLOBALS[$v]->freeze();
  }
}
?>
