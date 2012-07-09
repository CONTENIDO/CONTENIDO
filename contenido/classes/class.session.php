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
 * @version    1.0
 * @author     Mischa Holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9
 *
 * {@internal
 *   created  2012-07-06
 *
 *   $Id: class.session.php 2486 2012-07-02 21:49:26Z xmurrix $:
 * }}
 *
 */
if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cSession {

	/**
	 * Saves the registered variables
	 * @var array
	 */
	protected $pt;

	/**
	 * The prefix for the session variables
	 * @var string
	 */
	protected $prefix;

	/**
	 * Placeholder. This variable isn't needed to make sessions work any longer but some CONTENIDO functions/classes rely on it
	 * @var string
	 */
	public $id;

	/**
	 * Placeholder. This variable isn't needed to make sessions work any longer but some CONTENIDO functions/classes rely on it
	 * @var unknown_type
	 */
	public $name;

	/**
	 * Starts the session
	 * @param string The prefix for the session variables
	 */
	public function __construct($sprefix = "backend") {
		$this->pt = array();
		$this->prefix = $sprefix;

		$this->id = "1";
		$this->name = "contenido";

		if(!isset($_SESSION)) {
			session_name($this->prefix);
			session_start();
		}
	}

	/**
	 * Registers a global variable which will become persistent
	 * @param string $things The name of the variable (e.g. "idclient")
	 */
	public function register($things)
	{
		$things = explode(',', $things);

		foreach($things as $thing) {
			$thing = trim($thing);
			if ($thing) {
				$this->pt[$thing] = true;
			}
		}
	}

	/**
	 * Unregisters a variable
	 * @param string $name The name of the variable (e.g. "idclient")
	 */
	public function unregister($name) {
		$this->pt[$name] = false;
	}

	/**
	 * Checks if a variable is registered
	 * @param string $name The name of the variable (e.g. "idclient")
	 */
	public function is_registered($name) {
		if (isset($this->pt[$name]) && $this->pt[$name] == true) {
			return true;
		}
		return false;
	}
	/**
	 * Attaches "&contenido=1" at the end of the URL. This is no longer needed to make sessions work but some CONTENIDO functions/classes rely on it
	 * @param string $url A URL
	 */
	public function url($url) {
        // Remove existing session info from url
        $url = preg_replace('/([&?])'.quotemeta(urlencode($this->name)).'='.$this->id.'(&|$)/', "\\1", $url);

        // Remove trailing ?/& if needed
        $url = preg_replace('/[&?]+$/', '', $url);

        $url .= (strpos($url, '?') != false ? '&' : '?') . urlencode($this->name).'='.$this->id;

        // Encode naughty characters in the URL
        $url = str_replace(array('<', '>', ' ', '"', '\''), array('%3C', '%3E', '+', '%22', '%27'), $url);
        return $url;
	}

	/**
	 * Attaches "&contenido=1" at the end of the current URL. This is no longer needed to make sessions work but some CONTENIDO functions/classes rely on it
	 * @param string $url A URL
	 */
	public function self_url() {
		return $this->url($_SERVER['PHP_SELF'].((isset($_SERVER['QUERY_STRING']) && ('' != $_SERVER['QUERY_STRING'])) ? '?' . $_SERVER['QUERY_STRING'] : ''));
	}

	/**
	 * Returns PHP code which can be used to rebuild the variable by evaluating it. This will work recursevly on arrays
	 * @param mixed $var A variable which should get serialized.
	 * @return string the PHP code which can be evaluated.
	 */
	public function serialize($var) {
		$str = "";
		$this->rSerialize($var, $str);
		return $str;
	}

	/**
	 * This function will go recursevly through arrays and objects to serialize them.
	 * @param mixed $var The variable
	 * @param string $str The PHP code will be attached to this string
	 */
	protected function rSerialize($var, &$str) {
		static $t,$l,$k;

		// Determine the type of $$var
		eval("\$t = gettype(\$$var);");
		switch ($t) {
			case 'array':
				// $$var is an array. Enumerate the elements and serialize them.
				eval("reset(\$$var); \$l = gettype(list(\$k)=each(\$$var));");
				$str .= "\$$var = array(); ";
				while ('array' == $l) {
					// Structural recursion
					$this->rSerialize($var."['".preg_replace("/([\\'])/", "\\\\1", $k)."']", $str);
					eval("\$l = gettype(list(\$k)=each(\$$var));");
				}
				break;
			case 'object':
				// $$var is an object. Enumerate the slots and serialize them.
				eval("\$k = \$${var}->classname; \$l = reset(\$${var}->persistent_slots);");
				$str.="\$$var = new $k; ";
				while ($l) {
					// Structural recursion.
					$this->rSerialize($var."->".$l, $str);
					eval("\$l = next(\$${var}->persistent_slots);");
		}
		break;
		default:
			// $$var is an atom. Extract it to $l, then generate code.
			eval("\$l = \$$var;");
			$str.="\$$var = '".preg_replace("/([\\'])/", "\\\\1", $l)."'; ";
			break;
		}
	}

	/**
	 * Stores the session using PHP's own session implementation
	 */
	public function freeze() {
		$str = $this->serialize("this->pt");

		foreach($this->pt as $thing => $value) {
            $thing = trim($thing);
            if ($value) {
                $str .= $this->serialize("GLOBALS['".$thing."']");
            }
		}

		$_SESSION[$this->prefix.'csession'] = $str;
	}

	/**
	 * Rebuilds every registered variable from the session.
	 */
	public function thaw() {
		if($_SESSION[$this->prefix.'csession'] != "") {
        	eval(sprintf(';%s', $_SESSION[$this->prefix.'csession']));
		}
	}

	/**
	 * Deletes the session by calling session_destroy()
	 */
	public function delete() {
		session_destroy();
	}

	/**
	 * Dummy function. This is no longer needed and will always return "".
	 */
	public function hidden_session() {
		return "";
	}

	/**
	 * Starts the session and rebuilds the variables
	 */
	public function start() {
		$this->thaw();
	}
}

/**
 * Session class for the frontend. It uses a different prefix. The rest is the same
 * @author mischa.holz
 *
 */
class cFrontendSession extends cSession
{
	/**
	 * Starts the session and initilializes the class
	 */
	public function __construct()
	{
		parent::__construct("frontend");
	}

	public function url($url) {
        $url = preg_replace('/([&?])'.quotemeta(urlencode($this->name)).'='.$this->id.'(&|$)/', "\\1", $url);

        $url = preg_replace('/[&?]+$/', '', $url);

        $url = str_replace(array('<', '>', ' ', '"', '\''), array('%3C', '%3E', '+', '%22', '%27'), $url);

        return $url;
	}
}

?>