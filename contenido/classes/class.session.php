<?php

/**
 * This file contains the backend and frontend session class.
 *
 * @package    Core
 * @subpackage Session
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Backend session class.
 *
 * @package    Core
 * @subpackage Session
 */
class cSession
{

    /**
     * @var array List of the registered variables
     */
    protected $_pt;

    /**
     * @var string The prefix for the session variables
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
     * Session namespace used as session key to store the session values.
     * @var string
     */
    protected $namespace;

    /***
     * @var array Session configuration array
     */
    protected $sessionConfig = [];

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
     * @param string $prefix The prefix for the session variables
     * @since CON-2423 Via $cfg['secure'] you can define if the cookie should only be sent over secure connections.
     *        Configure in data/config/<ENV>/config.misc.php
     *
     * The session cookie is accessible only through the HTTP protocol.
     *
     * @since CON-2785 the cookie path can be configured as $cfg['cookie']['path'].
     *        Configure in <CLIENT>/data/config/<ENV>/config.local.php
     *
     */
    public function __construct(string $prefix = 'backend')
    {
        $this->_pt = [];
        $this->_prefix = $prefix;
        $this->name = 'contenido';
        $this->namespace = $this->_prefix . ':csession';
        $this->sessionConfig = $this->getSessionConfig();

        if (in_array(session_status(), [PHP_SESSION_DISABLED, PHP_SESSION_ACTIVE])) {
            return;
        }

        $params = $this->getCookieParams();
        if (version_compare(PHP_VERSION, '7.3', '<')) {
            // @phpVersion Old signature up to PHP 7.3.0
            if ($params['samesite'] && strpos($params['path'], 'samesite=') !== false) {
                $params['path'] .= '; samesite=' . $params['samesite'];
            }
            session_set_cookie_params($params['lifetime'], $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        } else {
            // @phpVersion Alternative signature as of PHP 7.3.0
            session_set_cookie_params($params);
        }

        session_name($this->_prefix);
        session_start();

        $this->id = session_id();
    }

    /**
     * Registers a global variable which will become persistent
     *
     * @param string $things The name of the variable (e.g. "idclient")
     */
    public function register(string $things)
    {
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
     * @param string $name The name of the variable (e.g. "idclient")
     */
    public function unregister(string $name)
    {
        $this->_pt[$name] = false;
    }

    /**
     * Checks if a variable is registered
     *
     * @param string $name The name of the variable (e.g. "idclient")
     * @return  bool
     */
    public function isRegistered(string $name): bool
    {
        return isset($this->_pt[$name]) && $this->_pt[$name] === true;
    }

    /**
     * Attaches "&contenido=sessionid" at the end of the URL.
     * This is no longer needed to make sessions work but some CONTENIDO
     * functions/classes rely on it
     *
     * @param string $url A URL
     */
    public function url(string $url): string
    {
        // Return url with session parameter
        return $this->_url($url, true);
    }

    /**
     * Attaches "&contenido=1" at the end of the current URL.
     * This is no longer needed to make sessions work but some CONTENIDO
     * functions/classes rely on it
     */
    public function selfURL(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        return $this->url($requestUri . (!empty($queryString) ? '?' . $queryString : ''));
    }

    /**
     * Returns PHP code which can be used to rebuild the variable by evaluating it.
     * This will work recursively on arrays
     *
     * @param string $varName Name of variable to be serialized.
     * @param mixed $actualValue Content of the variable.
     * @return string The PHP code which can be evaluated.
     * @since CONTENIDO 4.10.2: The function accessibility has been changed from `public` to `protected`,
     *        and parameter `$actualValue` added.
     */
    protected function serialize(string $varName, $actualValue): string
    {
        $str = '';
        $this->_rSerialize($actualValue, $varName, $str);
        return $str;
    }

    /**
     * This function will go recursively through arrays and objects to serialize them.
     *
     * @param mixed $value The variable to serialize
     * @param string $str The PHP code will be attached to this string
     */
    protected function _rSerialize($value, string $label, string &$str)
    {
        $type = gettype($value);

        switch ($type) {
            case 'array':
                $str .= "\$$label = [];\n";
                foreach ($value as $k => $v) {
                    // Escape key for the string label
                    $escapedKey = preg_replace("/(')/", "\\\\1", $k);
                    // Recursively serialize the value using the path label
                    $this->_rSerialize($v, $label . "['" . $escapedKey . "']", $str);
                }
                break;
            case 'object':
                $className = $value->classname ?? get_class($value);
                $str .= "\$$label = new $className();\n";

                // Assuming persistent_slots is an array of property names
                if (isset($value->persistent_slots) && is_array($value->persistent_slots)) {
                    foreach ($value->persistent_slots as $prop) {
                        if (isset($value->$prop)) {
                            $this->_rSerialize($value->$prop, $label . "->" . $prop, $str);
                        }
                    }
                }
                break;
            case 'NULL':
                $str .= "\$$label = NULL;\n";
                break;
            default:
                // TODO Proper cast of int, bool, and double in future versions!
                // Handle scalars (int, string, bool, double)
                $escapedValue = preg_replace("/(')/", "\\\\1", $value);
                $str .= "\$$label = '" . $escapedValue . "';\n";
                break;
        }
    }

    /**
     * Stores the session using PHP's own session implementation
     */
    public function freeze()
    {
        // Pass the actual object/array and its intended name
        $str = $this->serialize('this->_pt', $this->_pt);

        foreach ($this->_pt as $thing => $active) {
            $thing = trim($thing);
            if ($active && isset($GLOBALS[$thing])) {
                // Pass the value from the GLOBALS array
                $str .= $this->serialize('GLOBALS["' . $thing . '"]', $GLOBALS[$thing]);
            }
        }

        $_SESSION[$this->namespace] = $str;
    }

    /**
     * Rebuilds every registered variable from the session.
     */
    public function thaw()
    {
        if (isset($_SESSION[$this->namespace]) && $_SESSION[$this->namespace] != '') {
            eval(sprintf(';%s', $_SESSION[$this->namespace]));
        }
    }

    /**
     * Deletes the session by calling session_destroy()
     */
    public function delete()
    {
        $_SESSION = [];

        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

        session_destroy();
    }

    /**
     * Starts the session and rebuilds the variables
     */
    public function start()
    {
        $this->thaw();
    }

    /**
     * Removes existing session parameter (e.g., contenido=1) from the URL and returns the rebuild URL back
     *
     * @param string $url The URL to process
     * @param bool $addSession Flag to add the current session parameter (e.g., contenido=1) to it, e.g. used by the backend
     */
    protected function _url(string $url, bool $addSession): string
    {
        $encodedName = urlencode($this->name);

        // Replace ampersand entity code, otherwise parsing the url below will fail
        $url = str_replace('&amp;', '&', $url);

        // Split URL by question mark and remove existing session parameter
        if (cString::findFirstPos($url, '?') !== false) {
            list($file, $query) = explode('?', $url);
            if (cString::findFirstPos($query, '#') !== false) {
                list($query, $fragment) = explode('#', $query);
            } else {
                $fragment = '';
            }
            parse_str($query, $parameters);
            if (isset($parameters[$encodedName])) {
                unset($parameters[$encodedName]);
            }
        } else {
            $file = $url;
            $parameters = [];
            $fragment = '';
        }

        if ($addSession) {
            // Add session parameter, use array_merge, so we have session name at first pos.
            $parameters = array_merge([$encodedName => $this->id], $parameters);
        }

        // Assemble URL again
        $url = $file . (count($parameters) > 0 ? '?' . http_build_query($parameters) : '');

        // Add fragment
        if ($fragment) {
            $url .= '#' . urlencode($fragment);
        }

        return $url;
    }

    /**
     * @since CONTENIDO 4.10.2
     */
    protected function getCookieParams(): array
    {
        return [
            'lifetime' => intval($this->sessionConfig['cookie_expires'] ?? 0) * 60,
            'path' => $this->getCookiePathParam(),
            'domain' => $this->sessionConfig['cookie_domain'] ?? null,
            'secure' => boolval($this->sessionConfig['cookie_secure'] ?? false),
            'httponly' => boolval($this->sessionConfig['cookie_httponly'] ?? true),
            'samesite' => $this->getCookieSamesiteParam(),
        ];
    }

    /**
     * @since CONTENIDO 4.10.2
     */
    protected function getCookiePathParam(): string
    {
        // Determine cookie path (entire domain if path could not be determined)
        $url = $this->_prefix === 'backend' ? cRegistry::getBackendUrl() : cRegistry::getFrontendUrl();
        $path = parse_url($url, PHP_URL_PATH);
        $path = strval($this->sessionConfig['cookie_path'] ?? $path);
        if (empty($path)) {
            $path = '/';
        }

        return $path;
    }

    /**
     * @since CONTENIDO 4.10.2
     */
    protected function getCookieSamesiteParam(): ?string
    {
        // Determine cookie samesite flag
        $samesite = strval($this->sessionConfig['cookie_samesite'] ?? '');
        if (!in_array(strtolower($samesite), ['none', 'lax', 'strict'])) {
            $samesite = null;
        }

        return $samesite;
    }

    /**
     * @since CONTENIDO 4.10.2
     */
    private function getSessionConfig(): array
    {
        $parts = explode(':', $this->_prefix);

        // Return `$cfg['backend']['session']` or `$cfg['frontend']['session']` or `$cfg[{section}]['session']`
        return cRegistry::getConfig()[array_pop($parts)]['session'] ?? [];
    }
}

/**
 * Session class for the frontend.
 * It uses a different prefix and session url will be created without the contenido=1 parameter.
 * The rest is the same.
 *
 * @package    Core
 * @subpackage Session
 */
class cFrontendSession extends cSession
{

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
     * @param string $prefix The prefix for the session variables
     * @since CON-2423 Via $cfg['secure'] you can define if the cookie should only be sent over secure connections.
     *        Configure in data/config/<ENV>/config.misc.php
     *
     * The session cookie is accessible only through the HTTP protocol.
     *
     * @since CON-2785 the cookie path can be configured as $cfg['cookie']['path'].
     *        Configure in <CLIENT>/data/config/<ENV>/config.local.php
     */
    public function __construct(string $prefix = 'frontend')
    {
        $client = cRegistry::getClientId();

        parent::__construct($client . ':' . $prefix);
    }

    /**
     * This function overrides {@see cSession::url()} so that the contenido=1 isn't
     * attached to the URL for the frontend
     */
    public function url(string $url): string
    {
        // Return url without session parameter
        return $this->_url($url, false);
    }

}
