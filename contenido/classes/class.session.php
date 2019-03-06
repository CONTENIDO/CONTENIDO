<?php
/**
 * This file contains the the backend and frontend session class.
 *
 * @package Core
 * @subpackage Session
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Backend session class.
 *
 * @package Core
 * @subpackage Session
 */
class cSession {

    /**
     * Saves the registered variables
     *
     * @var array
     */
    protected $_pt;

    /**
     * The prefix for the session variables
     *
     * @var string
     */
    protected $_prefix;

    /**
     * Placeholder.
     * This variable isn't needed to make sessions work any longer
     * but some CONTENIDO functions/classes rely on it
     *
     * @var string
     */
    public $id;

    /**
     * Placeholder.
     * This variable isn't needed to make sessions work any longer
     * but some CONTENIDO functions/classes rely on it
     *
     * @var string
     */
    public $name;

    /**
     * cSession constructor. Starts a session if it does not yet exist.
     *
     * Session cookies will be created with these parameters:
     *
     * The session cookie will have a lifetime of 0 which means "until the browser is closed".
     *
     * It will be valid for the host name of the server which generated the cookie
     * and the path as in either the configured backend or frontend URL.
     *
     * @since CON-2785 the cookie path can be configured as $cfg['cookie']['path'].
     *        Configure in <CLIENT>/data/config/<ENV>/config.local.php
     *
     * @since CON-2423 Via $cfg['secure'] you can define if the cookie should only be sent over secure connections.
     *        Configure in data/config/<ENV>/config.misc.php
     *
     * The session cookie is accessible only through the HTTP protocol.
     *
     * @param string $prefix [optional] The prefix for the session variables
     */
    public function __construct($prefix = 'backend')
    {
        $this->_pt     = [];
        $this->_prefix = $prefix;
        $this->name    = 'contenido';

        if (isset($_SESSION)) {
            return;
        }

        // determine cookie lifetime
        $lifetime = 0;

        // determine cookie path (entire domain if path could not be determined)
        $url  = 'backend' === $prefix ? cRegistry::getBackendUrl() : cRegistry::getFrontendUrl();
        $path = parse_url($url, PHP_URL_PATH);
        $path = cRegistry::getConfigValue('cookie', 'path', $path);
        if (empty($path)) {
            $path = '/';
        }

        // determine cookie domain
        $domain = null;

        // determine cookie security flag
        $secure = cRegistry::getConfigValue('secure');

        // determine cookie httponly flag
        $httponly = true;

        session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
        session_name($this->_prefix);
        session_start();

        $this->id = session_id();
    }

    /**
     * Registers a global variable which will become persistent
     *
     * @param string $things
     *         The name of the variable (e.g. "idclient")
     */
    public function register($things) {
        $things = explode(',', $things);

        foreach ($things as $thing) {
            $thing = trim($thing);
            if ($thing) {
                $this->_pt[$thing] = true;
            }
        }
    }

    /**
     * Unregisters a variable
     *
     * @param string $name
     *         The name of the variable (e.g. "idclient")
     */
    public function unregister($name) {
        $this->_pt[$name] = false;
    }

    /**
     * Checks if a variable is registered
     *
     * @param string $name
     *         The name of the variable (e.g. "idclient")
     * @return bool
     */
    public function isRegistered($name) {
        if (isset($this->_pt[$name]) && $this->_pt[$name] == true) {
            return true;
        }
        return false;
    }

    /**
     * Attaches "&contenido=sessionid" at the end of the URL.
     * This is no longer needed to make sessions work but some CONTENIDO
     * functions/classes rely on it
     *
     * @param string $url
     *         a URL
     * @return mixed
     */
    public function url($url) {
        // Remove existing session info from url
        $url = preg_replace('/([&?])' . quotemeta(urlencode($this->name)) . '=1(&|$)/', "\\1", $url);

        // Remove trailing ?/& if needed
        $url = preg_replace('/[&?]+$/', '', $url);

        if (!preg_match('~\b' . quotemeta(urlencode($this->name)) . '=[a-zA-Z0-9]*\b~', $url)) {
            $url .= (cString::findFirstPos($url, '?') != false? '&' : '?') . urlencode($this->name) . '=' . $this->id;
        }

        // Encode naughty characters in the URL
        $url = str_replace(['<', '>', ' ', '"', '\''], ['%3C', '%3E', '+', '%22', '%27',], $url);

        return $url;
    }

    /**
     * Attaches "&contenido=1" at the end of the current URL.
     * This is no longer needed to make sessions work but some CONTENIDO
     * functions/classes rely on it
     *
     * @return mixed
     */
    public function selfURL() {
        return $this->url($_SERVER['REQUEST_URI'] . ((isset($_SERVER['QUERY_STRING']) && ('' != $_SERVER['QUERY_STRING'])) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    }

    /**
     * Returns PHP code which can be used to rebuild the variable by evaluating
     * it.
     * This will work recursevly on arrays
     *
     * @param mixed $var
     *         A variable which should get serialized.
     * @return string
     *         the PHP code which can be evaluated.
     */
    public function serialize($var) {
        $str = "";
        $this->_rSerialize($var, $str);
        return $str;
    }

    /**
     * This function will go recursevly through arrays and objects to serialize
     * them.
     *
     * @param mixed $var
     *         The variable
     * @param string $str
     *         The PHP code will be attached to this string
     */
    protected function _rSerialize($var, &$str) {
        static $t, $l, $k;

        // Determine the type of $$var
        eval("\$t = gettype(\$$var);");
        switch ($t) {
            case 'array':
                // $$var is an array. Enumerate the elements and serialize them.
                $str .= "\$$var = array(); ";
                eval("\$l = array(); foreach(\$$var as \$k => \$v) {\$l[] = array(\$k,gettype(\$k),\$v);}");
                foreach ($l as $item) {
                    // Structural recursion
                    $this->_rSerialize($var . "['" . preg_replace("/([\\'])/", "\\\\1", $item[0]) . "']", $str);
                }
                break;
            case 'object':
                // $$var is an object. Enumerate the slots and serialize them.
                eval("\$k = \$${var}->classname; \$l = reset(\$${var}->persistent_slots);");
                $str .= "\$$var = new $k; ";
                while ($l) {
                    // Structural recursion.
                    $this->_rSerialize($var . "->" . $l, $str);
                    eval("\$l = next(\$${var}->persistent_slots);");
                }
                break;
            default:
                // $$var is an atom. Extract it to $l, then generate code.
                eval("\$l = \$$var;");
                $str .= "\$$var = '" . preg_replace("/([\\'])/", "\\\\1", $l) . "'; ";
                break;
        }
    }

    /**
     * Stores the session using PHP's own session implementation
     */
    public function freeze() {
        $str = $this->serialize("this->_pt");

        foreach ($this->_pt as $thing => $value) {
            $thing = trim($thing);
            if ($value) {
                $str .= $this->serialize("GLOBALS['" . $thing . "']");
            }
        }

        $_SESSION[$this->_prefix . 'csession'] = $str;
    }

    /**
     * Rebuilds every registered variable from the session.
     */
    public function thaw() {
        if (isset($_SESSION[$this->_prefix . 'csession']) && $_SESSION[$this->_prefix . 'csession'] != '') {
            eval(sprintf(';%s', $_SESSION[$this->_prefix . 'csession']));
        }
    }

    /**
     * Deletes the session by calling session_destroy()
     */
    public function delete() {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);

        session_destroy();
    }

    /**
     * Starts the session and rebuilds the variables
     */
    public function start() {
        $this->thaw();
    }
}

/**
 * Session class for the frontend.
 * It uses a different prefix. The rest is the
 * same
 *
 * @package Core
 * @subpackage Session
 */
class cFrontendSession extends cSession {
    /**
     * cFrontendSession constructor. Starts a session if it does not yet exist.
     *
     * Session cookies will be created with these parameters:
     *
     * The session cookie will have a lifetime of 0 which means "until the browser is closed".
     *
     * It will be valid for the host name of the server which generated the cookie
     * and the path as in the configured frontend URL.
     *
     * @since CON-2785 the cookie path can be configured as $cfg['cookie']['path'].
     *        Configure in <CLIENT>/data/config/<ENV>/config.local.php
     *
     * @since CON-2423 Via $cfg['secure'] you can define if the cookie should only be sent over secure connections.
     *        Configure in data/config/<ENV>/config.misc.php
     *
     * The session cookie is accessible only through the HTTP protocol.
     *
     * @param string $prefix [optional] The prefix for the session variables
     */
    public function __construct() {
        $client = cRegistry::getClientId();

        parent::__construct($client . "frontend");
    }

    /**
     * This function overrides cSession::url() so that the contenido=1 isn't
     * attached to the URL for the frontend
     *
     * @see cSession::url()
     * @param string $url
     *         a URL
     * @return mixed
     */
    public function url($url) {
        // Remove existing session info from url
        $url = preg_replace('/([&?])' . quotemeta(urlencode($this->name)) . '=' . $this->id . '(&|$)/', "\\1", $url);

        // Remove trailing ?/& if needed
        $url = preg_replace('/[&?]+$/', '', $url);

        // Encode naughty characters in the URL
        $url = str_replace(['<', '>', ' ', '"', '\''], ['%3C', '%3E', '+', '%22', '%27'], $url);

        return $url;
    }
}
