<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Session management
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
 * @since      file available since CONTENIDO release <CONTENIDO Version>
 *
 * {@internal
 *   created  2000-01-01
 *   $Id$:
 * }}
 */

//if (!defined('CON_FRAMEWORK')) {
//    die('Illegal call');
//}

/**
 * @package    CONTENIDO Core
 * @deprecated [2012-07-06] This class was replcaed by cSession
 * @subpackage Session
 */
class Session {

    /**
     * Needed for object serialization.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var string
     */
    public $classname = 'Session';

    ## Define the parameters of your session by either overwriting
    ## these values or by subclassing session (recommended).

    /**
     * Some string you should change.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var string
     */
    public $magic = '';

    /**
     * We propagate session IDs with cookies
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var string
     */
    public $mode = 'cookie';

    /**
     * If this doesn't work, fall back... (feasible is 'get' or 'cookie')
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var string
     */
    public $fallback_mode;

    /**
     * 0 = do session cookies, else minutes
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var int
     */
    public $lifetime = 0;

    /**
     * If set, the domain for which the session cookie is set.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var string
     */
    public $cookie_domain = '';

    /**
     * Purge all session data older than 1440 minutes.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var int
     */
    public $gc_time = 1440;

    /**
     * Garbage collect probability in percent
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var int
     */
    public $gc_probability = 5;

    /**
     * 'passive', 'no', 'private' or 'public'
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var string
     */
    public $allowcache = 'no';

    /**
     * If you allowcache, data expires in this many minutes.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var int
     */
    public $allowcache_expire = 1440;

    /**
     * Name of data storage container
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var string
     */
    public $that_class = '';

    ##
    ## End of parameters.
    ##

    /**
     * Session name
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var string
     */
    public $name;

    /**
     * Unique Session ID
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var string
     */
    public $id;

    /**
     * Session sotorage instance
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var Object
     */
    public $that;

    /**
     * This array contains the registered things
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var array
     */
    public $pt = array();

    /**
     * Marker: Did we already include the autoinit file?
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var int
     */
    public $in = 0;

    /**
     * Expire date (not used???)
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var int
     */
    public $expires;

    /**
     * Expire date
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @var int
     */
    public $_expires;

    /**
     *
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function __construct() {
        cDeprecated("This class was replaced by cSession");
    }

    /**
     * Call this function to register the things that should become persistent
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @param  string  $things  One thing or comma separated things
     */
    public function register($things) {
        cDeprecated("This class was replaced by cSession");
        $things = explode(',', $things);
        reset($things);
        while (list(, $thing) = each($things)) {
            $thing = trim($thing);
            if ($thing) {
                $this->pt[$thing] = true;
            }
        }
    }

    /**
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function setExpires($time) {
        cDeprecated("This class was replaced by cSession");
        $this->_expires = $time;
    }

    public function is_registered($name) {
        cDeprecated("This class was replaced by cSession");
        if (isset($this->pt[$name]) && $this->pt[$name] == true) {
            return true;
        }
        return false;
    }

    /**
     * Call this function to unregister the things that should be removed
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @param  string  $things  One thing or comma separated things
     */
    public function unregister($things) {
        cDeprecated("This class was replaced by cSession");
        $things = explode(',', $things);
        reset($things);
        while (list(, $thing) = each($things)) {
            $thing = trim($thing);
            if ($thing) {
                unset($this->pt[$thing]);
            }
        }
    }

    /**
     * Propagate the session id according to mode and lifetime.
     * Will create a new id if necessary. To take over abandoned sessions,
     * one may provide the new session id as a parameter (not recommended).
     *
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @param  string  $id
     */
    public function get_id($id = '') {
        cDeprecated("This class was replaced by cSession");
        $newid = true;

        $this->name = ($this->cookiename == '') ? $this->classname : $this->cookiename;

        if ('' == $id) {
            $newid = false;
            switch ($this->mode) {
                case 'get':
                    $id = isset($_GET[$this->name]) ? $_GET[$this->name] :
                            (isset($_POST[$this->name]) ? $_POST[$this->name] : '');
                    break;
                case 'cookie':
                    $id = isset($_COOKIE[$this->name]) ? $_COOKIE[$this->name] : '';
                    break;
                default:
                    die('This has not been coded yet.');
                    break;
            }
        }

        if ('' == $id) {
            $newid = true;
            $id = $this->that->ac_newid(md5(uniqid($this->magic)), $this->name);
        }

        switch ($this->mode) {
            case 'cookie':
                if ($newid && (0 == $this->lifetime)) {
                    setcookie($this->name, $id, 0, '/', $this->cookie_domain);
                }
                if (0 < $this->lifetime) {
                    setcookie($this->name, $id, time() + $this->lifetime * 60, '/', $this->cookie_domain);
                }

                // Remove session ID info from QUERY String - it is in cookie
                if (isset($_SERVER['QUERY_STRING']) && ('' != $_SERVER['QUERY_STRING'])) {
                    $_SERVER['QUERY_STRING'] = preg_replace(
                            '/(^|&)' . quotemeta(urlencode($this->name)) . '=' . $id . '(&|$)/', "\\1", $_SERVER['QUERY_STRING']
                    );
                    // Update also existing global (fallback for backwards compatibility)
                    if (isset($GLOBALS['QUERY_STRING'])) {
                        $GLOBALS['QUERY_STRING'] = $_SERVER['QUERY_STRING'];
                    }
                }
                break;
            case 'get':
                if (isset($_SERVER['QUERY_STRING']) && ('' != $_SERVER['QUERY_STRING'])) {
                    $_SERVER['QUERY_STRING'] = preg_replace(
                            '/(^|&)' . quotemeta(urlencode($this->name)) . '=' . $id . '(&|$)/', "\\1", $_SERVER['QUERY_STRING']
                    );
                    // Update also existing global (fallback for backwards compatibility)
                    if (isset($GLOBALS['QUERY_STRING'])) {
                        $GLOBALS['QUERY_STRING'] = $_SERVER['QUERY_STRING'];
                    }
                }
                break;
            default:
                ;
                break;
        }
        $this->id = $id;
    }

    /**
     * Stop using the current session id (unset cookie, ...) and abandon a session.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function put_id() {
        cDeprecated("This class was replaced by cSession");
        switch ($this->mode) {
            case 'cookie':
                $this->name = ($this->cookiename == '') ? $this->classname : $this->cookiename;
                setcookie($this->name, '', 0, '/', $this->cookie_domain);
                $_COOKIE[$this->name] = '';
                break;
            default:
                // do nothing. We don't need to die for modes other than cookie here.
                break;
        }
    }

    /**
     * Delete the current session record and put the session id.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function delete() {
        cDeprecated("This class was replaced by cSession");
        $this->that->ac_delete($this->id, $this->name);
        $this->put_id();
    }

    /**
     * Returns $url concatenated with the current session $id.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @param string $url
     * @return string
     */
    public function url($url) {
        cDeprecated("This class was replaced by cSession");
        // Remove existing session info from url
        $url = preg_replace(
                '/([&?])' . quotemeta(urlencode($this->name)) . '=' . $this->id . '(&|$)/', "\\1", $url
        );

        // Remove trailing ?/& if needed
        $url = preg_replace('/[&?]+$/', '', $url);

        switch ($this->mode) {
            case 'get':
                $url .= (strpos($url, '?') != false ? '&' : '?') . urlencode($this->name) . '=' . $this->id;
                break;
            default:
                ;
                break;
        }

        // Encode naughty characters in the URL
        $url = str_replace(array('<', '>', ' ', '"', '\''), array('%3C', '%3E', '+', '%22', '%27'), $url);
        return $url;
    }

    /**
     * Prints $url concatenated with the current session $id.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @param string $url
     */
    public function purl($url) {
        cDeprecated("This class was replaced by cSession");
        print $this->url($url);
    }

    /**
     * Builds and returns self url
     * @deprecated [2012-07-06] This class was replcaed by cSession
     *
     * @return string
     */
    public function self_url() {
        cDeprecated("This class was replaced by cSession");
        return $this->url($_SERVER['PHP_SELF'] .
                        ((isset($_SERVER['QUERY_STRING']) && ('' != $_SERVER['QUERY_STRING'])) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    }

    /**
     * Prints builded self url
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function pself_url() {
        cDeprecated("This class was replaced by cSession");
        print $this->self_url();
    }

    /**
     * Prints or returns hidden form field containing session name and id
     *
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @param   int  $mode  1 to print the formfield or 0 to return it
     * @return  string|void
     */
    public function hidden_session($mode = 0) {
        cDeprecated("This class was replaced by cSession");
        if ($mode) {
            return sprintf('<input type="hidden" name="%s" value="%s">' . "\n", $this->name, $this->id);
        } else {
            printf('<input type="hidden" name="%s" value="%s">' . "\n", $this->name, $this->id);
        }
    }

    /**
     * Builda a query string by usingpassed query parameter
     * @deprecated [2012-07-06] This class was replcaed by cSession
     *
     * @param   array  $qarray  List of parameter/key values
     * @return  string  Query string like "?paramA=1&paramB=2" ...
     */
    public function add_query($qarray) {
        cDeprecated("This class was replaced by cSession");
        if ((isset($_SERVER['QUERY_STRING']) && ('' != $_SERVER['QUERY_STRING'])) || ($this->mode == 'get')) {
            $sep_char = '&';
        } else {
            $sep_char = '?';
        }

        $qstring = '';
        while (list($k, $v) = each($qarray)) {
            $qstring .= $sep_char . urlencode($k) . '=' . urlencode($v);
            $sep_char = '&';
        }

        return $qstring;
    }

    public function padd_query($qarray) {
        cDeprecated("This class was replaced by cSession");
        print $this->add_query($qarray);
    }

    /**
     * Appends a serialized representation of $$var at the end of $str.
     *
     * To be able to serialize an object, the object must implement
     * a variable $classname (containing the name of the class as string)
     * and a variable $persistent_slots (containing the names of the slots
     * to be saved as an array of strings).
     *
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * @param string $var
     * @param string $str
     */
    public function serialize($var, &$str) {
        cDeprecated("This class was replaced by cSession");
        static $t, $l, $k;

        // Determine the type of $$var
        eval("\$t = gettype(\$$var);");
        switch ($t) {
            case 'array':
                // $$var is an array. Enumerate the elements and serialize them.
                eval("reset(\$$var); \$l = gettype(list(\$k)=each(\$$var));");
                $str .= "\$$var = array(); ";
                while ('array' == $l) {
                    // Structural recursion
                    $this->serialize($var . "['" . preg_replace("/([\\'])/", "\\\\1", $k) . "']", $str);
                    eval("\$l = gettype(list(\$k)=each(\$$var));");
                }
                break;
            case 'object':
                // $$var is an object. Enumerate the slots and serialize them.
                eval("\$k = \$${var}->classname; \$l = reset(\$${var}->persistent_slots);");
                $str.="\$$var = new $k; ";
                while ($l) {
                    // Structural recursion.
                    $this->serialize($var . "->" . $l, $str);
                    eval("\$l = next(\$${var}->persistent_slots);");
                }
                break;
            default:
                // $$var is an atom. Extract it to $l, then generate code.
                eval("\$l = \$$var;");
                $str.="\$$var = '" . preg_replace("/([\\'])/", "\\\\1", $l) . "'; ";
                break;
        }
    }

    /**
     *
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function get_lock() {
        cDeprecated("This class was replaced by cSession");
        $this->that->ac_get_lock();
    }

    /**
     *
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function release_lock() {
        cDeprecated("This class was replaced by cSession");
        $this->that->ac_release_lock();
    }

    /**
     * Freezes all registered things (scalar variables, arrays, objects) into
     * a database table.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function freeze() {
        cDeprecated("This class was replaced by cSession");
        $str = '';
        //print 'DBG: <pre>'; var_dump($this->pt); print '</pre>';
        $this->serialize('this->in', $str);
        $this->serialize('this->pt', $str);

        reset($this->pt);
        while (list($thing) = each($this->pt)) {
            $thing = trim($thing);
            if ($thing) {
                $this->serialize("GLOBALS['" . $thing . "']", $str);
            }
        }
        //return;
        $r = $this->that->ac_store($this->id, $this->name, $str);
        $this->release_lock();

        if (!$r) {
            $this->that->ac_halt('Session: freeze() failed.');
        }
    }

    /**
     * Reload frozen variables from the database and microwave them.
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function thaw() {
        cDeprecated("This class was replaced by cSession");
        $this->get_lock();
        $vals = $this->that->ac_get_value($this->id, $this->name);
        eval(sprintf(';%s', $vals));
    }

    /**
     * Imports content of superglobal $_GET into superglobal $GLOBALS
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function reimport_get_vars() {
        cDeprecated("This class was replaced by cSession");
        $this->reimport_any_vars('_GET');
    }

    /**
     * Imports content of superglobal $_POST into superglobal $GLOBALS
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function reimport_post_vars() {
        cDeprecated("This class was replaced by cSession");
        $this->reimport_any_vars('_POST');
    }

    /**
     * Imports content of superglobal $_COOKIE into superglobal $GLOBALS
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function reimport_cookie_vars() {
        cDeprecated("This class was replaced by cSession");
        $this->reimport_any_vars('_COOKIE');
    }

    /**
     * Imports content an passed globaö available variable from type array into
     * superglobal $GLOBALS
     * @deprecated [2012-07-06] This class was replcaed by cSession
     *
     * @var string $arrayname Feasible values are e. g. $_GET, $POST, $_COOKIE
     */
    public function reimport_any_vars($arrayname) {
        global $$arrayname;

        cDeprecated("This class was replaced by cSession");
        if (!is_array($$arrayname)) {
            return;
        }

        reset($$arrayname);
        while (list($key, $val) = each($$arrayname)) {
            $GLOBALS[$key] = $val;
        }
    }

    ##
    ## All this is support infrastructure for the start() method
    ##

    /**
     * Sets and instantiates the session container class
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function set_container() {
        cDeprecated("This class was replaced by cSession");
        $name = $this->that_class;
        $this->that = new $name;
        $this->that->ac_start();
    }

    /**
     * Sets the token name
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function set_tokenname() {
        cDeprecated("This class was replaced by cSession");
        $this->name = ($this->cookiename == '') ? $this->classname : $this->cookiename;
    }

    /**
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function release_token($sid = '') {
        cDeprecated("This class was replaced by cSession");
        if (isset($this->fallback_mode) && ('get' == $this->fallback_mode)
                && ('cookie' == $this->mode) && (!isset($_COOKIE[$this->name]))) {
            // Looks like no cookie here - check GET/POST params
            if (isset($_GET[$this->name]) || isset($_POST[$this->name])) {
                // Session info passed via GET/POST - go to fallback_mode
                $this->mode = $this->fallback_mode;
            } else {
                // It seems to be the first load of this page -
                // no cookie and no GET/POST params
                // Generate session ID and setup cookie.
                $this->get_id($sid);

                // Next line is to generate correct self_url() later
                $this->mode = $this->fallback_mode;

                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                    // You will need to fix suexec as well, if you
                    // use Apache and CGI PHP
                    $PROTOCOL = 'https';
                } else {
                    $PROTOCOL = 'http';
                }
                header('Status: 302 Moved Temporarily');
                header('Location: ' . $PROTOCOL . '://' . $_SERVER['HTTP_HOST'] . $this->self_url());
                exit;
            }
        }
    }

    /**
     *
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function put_headers() {
        # Allowing a limited amount of caching, as suggested by
        # Padraic Renaghan on phplib@lists.netuse.de.
        #
        # Note that in HTTP/1.1 the Cache-Control headers override the Expires
        # headers and HTTP/1.0 ignores headers it does not recognize (e.g,
        # Cache-Control). Mulitple Cache-Control directives are split into
        # mulitple headers to better support MSIE 4.x.
        #
        # Added pre- and post-check for MSIE 5.x as suggested by R.C.Winters,
        # see http://msdn.microsoft.com/workshop/author/perf/perftips.asp#Use%20Cache-Control%20Extensions
        # for details
        cDeprecated("This class was replaced by cSession");
        switch ($this->allowcache) {
            case 'passive':
                $mod_gmt = gmdate('D, d M Y H:i:s', getlastmod()) . ' GMT';
                header('Last-Modified: ' . $mod_gmt);
                # possibly ie5 needs the pre-check line. This needs testing.
                header('Cache-Control: post-check=0, pre-check=0');
                break;

            case 'public':
                $exp_gmt = gmdate('D, d M Y H:i:s', time() + $this->allowcache_expire * 60) . ' GMT';
                $mod_gmt = gmdate('D, d M Y H:i:s', getlastmod()) . ' GMT';
                header('Expires: ' . $exp_gmt);
                header('Last-Modified: ' . $mod_gmt);
                header('Cache-Control: public');
                header('Cache-Control: max-age=' . $this->allowcache_expire * 60);
                break;

            case 'private':
                $mod_gmt = gmdate('D, d M Y H:i:s', getlastmod()) . ' GMT';
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Last-Modified: ' . $mod_gmt);
                header('Cache-Control: private');
                header('Cache-Control: max-age=' . $this->allowcache_expire * 60);
                header('Cache-Control: pre-check=' . $this->allowcache_expire * 60);
                break;

            default:
                $rand = md5(mt_rand());
                $mod_gmt = gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT';

                if ($this->_expires > 0) {
                    header('Expires: ' . gmdate('D, d M Y H:i:s', $this->_expires) . ' GMT');
                } else {
                    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                }

                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                header('Cache-Control:no-store, no-cache, must-revalidate');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Cache-control: private, no-cache');
                header('Pragma: no-cache');
                header('ETag: ' . $rand);
                break;
        }
    }

    /**
     * Garbage collection
     *
     * @deprecated [2012-07-06] This class was replcaed by cSession
     * Destroy all session data older than this
     */
    public function gc() {
        cDeprecated("This class was replaced by cSession");
        srand(time());
        if ((rand() % 100) < $this->gc_probability) {
            $this->that->ac_gc($this->gc_time, $this->name);
        }
    }

    /**
     * Initialization
     * @deprecated [2012-07-06] This class was replcaed by cSession
     */
    public function start($sid = '') {
        cDeprecated("This class was replaced by cSession");
        $this->set_container();
        $this->set_tokenname();
        $this->put_headers();
        $this->release_token($sid);
        $this->get_id($sid);
        $this->thaw();
        $this->gc();
        $this->setExpires(0);
    }

}
