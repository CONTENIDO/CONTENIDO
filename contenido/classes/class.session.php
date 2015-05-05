<?php
/**
 * This file contains the the backend and frontend session class.
 *
 * @package Core
 * @subpackage Session
 * @version SVN Revision $Rev:$
 *
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
     * Starts the session
     *
     * @param string $prefix [optional]
     *         The prefix for the session variables
     */
    public function __construct($prefix = 'backend') {
        $this->_pt = array();
        $this->_prefix = $prefix;

        $this->name = 'contenido';

        if (!isset($_SESSION)) {
            if ('backend' === $prefix) {
                $url = cRegistry::getBackendUrl();
            } else {
                $url = cRegistry::getFrontendUrl();
            }

            // remove protocol from contenido URL
            $start = strpos($url, '://');
            if (false === $start) {
                $url = 'http://' . $url;
                $start = strpos($url, '://');
            }

            // url of contenido folder with hostname
            $path = substr($url, $start + 3);

            $start = strpos($path, '/');
            if (false !== $start) {
                $path = substr($path, $start);
                session_set_cookie_params(0, $path);
            } else {
                // fall back to entire domain if no path can be computed
                session_set_cookie_params(0, '/');
            }

            session_name($this->_prefix);
            session_start();
            $this->id = session_id();
        }
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
            $url .= (strpos($url, '?') != false? '&' : '?') . urlencode($this->name) . '=' . $this->id;
        }

        // Encode naughty characters in the URL
        $url = str_replace(array(
            '<',
            '>',
            ' ',
            '"',
            '\''
        ), array(
            '%3C',
            '%3E',
            '+',
            '%22',
            '%27'
        ), $url);
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
        return $this->url($_SERVER['PHP_SELF'] . ((isset($_SERVER['QUERY_STRING']) && ('' != $_SERVER['QUERY_STRING'])) ? '?' . $_SERVER['QUERY_STRING'] : ''));
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
                eval("reset(\$$var); \$l = gettype(list(\$k)=each(\$$var));");
                $str .= "\$$var = array(); ";
                while ('array' == $l) {
                    // Structural recursion
                    $this->_rSerialize($var . "['" . preg_replace("/([\\'])/", "\\\\1", $k) . "']", $str);
                    eval("\$l = gettype(list(\$k)=each(\$$var));");
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
     * Starts the session and initilializes the class
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
        $url = preg_replace('/([&?])' . quotemeta(urlencode($this->name)) . '=' . $this->id . '(&|$)/', "\\1", $url);

        $url = preg_replace('/[&?]+$/', '', $url);

        $url = str_replace(array(
            '<',
            '>',
            ' ',
            '"',
            '\''
        ), array(
            '%3C',
            '%3E',
            '+',
            '%22',
            '%27'
        ), $url);

        return $url;
    }
}

?>