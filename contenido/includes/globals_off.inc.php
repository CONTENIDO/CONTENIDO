<?php


// REGISTER GLOBAL VARS
// Author Martin Horwath [horwath@dayside.net]

// Makes available those super global arrays that are made available in versions of PHP after v4.1.0
if (phpversion() <= "4.1.0")
{

	$_SERVER = & $HTTP_SERVER_VARS;
	$_GET = & $HTTP_GET_VARS;
	$_POST = & $HTTP_POST_VARS;
	$_COOKIE = & $HTTP_COOKIE_VARS;
	$_FILES = & $HTTP_POST_FILES;
	$_ENV = & $HTTP_ENV_VARS;

	// _SESSION is the only superglobal which is conditionally set
	if (isset ($HTTP_SESSION_VARS))
	{
		$_SESSION = & $HTTP_SESSION_VARS;
	}

}

// PHP5 with register_long_arrays off?
if (!isset ($HTTP_POST_VARS) && isset ($_POST))
{
	$HTTP_POST_VARS = & $_POST;
	$HTTP_GET_VARS = & $_GET;
	$HTTP_SERVER_VARS = & $_SERVER;
	$HTTP_COOKIE_VARS = & $_COOKIE;
	$HTTP_ENV_VARS = & $_ENV;
	$HTTP_POST_FILES = & $_FILES;

	// _SESSION is the only superglobal which is conditionally set
	if (isset ($_SESSION))
	{
		$HTTP_SESSION_VARS = & $_SESSION;
	}
}

// simulate get_magic_quotes_gpc on if turned off 
if (!get_magic_quotes_gpc()) { 
	function addslashes_deep($value) 
	{ 
		$value = is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value); 

		return $value;
	} 

	$_POST   = array_map('addslashes_deep', $_POST); 
	$_GET    = array_map('addslashes_deep', $_GET); 
	$_COOKIE = array_map('addslashes_deep', $_COOKIE); 

	$cfg['simulate_magic_quotes'] = true;
} else {
	$cfg['simulate_magic_quotes'] = false;
}

   if (!isset($_REQUEST) || $cfg['simulate_magic_quotes']) { 
      /* Register post,get and cookie variables into $_REQUEST */ 
      $_REQUEST = array_merge($_GET, $_POST, $_COOKIE); 
   } 

   // this should be the default setting 
   if (get_magic_quotes_runtime()) { 
      @set_magic_quotes_runtime(0); 
   }

// register globals
$types_to_register = array ('GET', 'POST', 'SERVER', 'COOKIE', 'SESSION');
foreach ($types_to_register as $global_type)
{
	$arr = @ ${'_'.$global_type};
	if (@ count($arr) > 0)
	{
		// echo "<pre>\$_$global_type:"; print_r ($arr); echo "</pre>";
		extract($arr, EXTR_OVERWRITE);
	}
}

// save memory
unset ($types_to_register, $global_type, $arr);

$FORM = $_REQUEST;
?>
