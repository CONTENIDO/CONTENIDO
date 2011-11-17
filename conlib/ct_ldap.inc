<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * PHPLIB Data Storage Container a LDAP database
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Core
 * @version    1.2
 * @author     Sascha Schumann <sascha@schumann.cx>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <CONTENIDO Version>
 *
 * {@internal
 *   created  2000-01-01
 *   modified 2008-07-03, bilal arslan, added security fix
 *   modified 2011-10-09, Murat Purc, Partly ported to PHP 5 and formatted/documented code
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @package    CONTENIDO Core
 * @subpackage Session
 */
class CT_Ldap
{
    /**
     * LDAP host name
     * @var string
     */
    public $ldap_host = 'localhost';

    /**
     * LDAP port
     * @var int
     */
    public $ldap_port = 389;

    /**
     * Base distinguished name
     * @var string
     */
    public $basedn = 'dc=your-domain, dc=com';

    /**
     * Root distinguished name
     * @var string
     */
    public $rootdn = 'cn=root, dc=your-domain, dc=com';

    /**
     * Root password
     * @var string
     */
    public $rootpw = 'secret';

    /**
     * Object class name
     * @var string
     */
    public $objclass = 'phplibdata';

    /**
     * Object class name
     * @var resource|bool
     */
    public $ds;


    public function ac_start()
    {
        $this->ds = ldap_connect($this->ldap_host, $this->ldap_port);
        if (!$this->ds) {
            $this->ac_halt('LDAP connect failed');
        }
        if (!ldap_bind($this->ds, $this->rootdn, $this->rootpw)) {
            $this->ac_halt('LDAP bind failed');
        }
    }

    public function ac_halt($msg = '')
    {
        echo 'Session_ldap failed: <b>'.htmlentities($msg).'</b><p>' . "\n";
        exit;
    }

    public function ac_store($id, $name, $str)
    {
        $dn = "cn=$id_$name, ".$this->basedn;
        $entry = array(
            'cn' => "$id_$name",
            'str' => $str,
            'objectclass' => $this->objclass
        );
        if (!@ldap_modify($this->ds, $dn, $entry)) {
            if (!ldap_add($this->ds, $dn, $entry)) {
                $this->ac_halt('LDAP add failed');
            }
        }
    }

    public function ac_delete($id, $name)
    {
        ldap_delete($this->ds, "cn=$id_$name, ".$this->basedn);
    }

    public function ac_get_value($id, $name)
    {
        $sr = ldap_search($this->ds, $this->basedn, "cn=$id_$name");
        $inf = ldap_get_entries($this->ds, $sr);
        $str = $inf[0]['str'][0];
        ldap_free_result($sr);
        return $str;
    }

    public function ac_release_lock()
    {
    }

    public function ac_get_lock()
    {
    }

    public function ac_newid($str, $name)
    {
        return $str;
    }

    public function ac_auth($username, $password)
    {
        // we need a username and a md5() encrypted password
        $sr = ldap_search($this->ds, $this->basedn, "username=$username");
        if (ldap_count_entries($this->ds, $sr) > 0) {
            $inf = ldap_get_entries($this->ds, $sr);
                $passmd5 = $inf[0]['password'][0];
                if (md5($password) == $passmd5) {
                return array($inf[0]['uid'][0],
                $inf[0]['perms'][0]);
            }
        }
        return array();
    }
}

?>