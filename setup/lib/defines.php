<?php

if (array_key_exists("setuptype", $_SESSION))
{
	switch ($_SESSION["setuptype"])
	{
		case "setup":
			define("C_SETUP_STEPS", 8);
			break;
		case "upgrade":
			define("C_SETUP_STEPS", 7);
			break;
		case "migration":
			define("C_SETUP_STEPS", 8);
			break;
	}
}

define("C_SETUP_STEPFILE", "images/steps/s%d.png");
define("C_SETUP_STEPFILE_ACTIVE", "images/steps/s%da.png");
define("C_SETUP_STEPWIDTH", 28);
define("C_SETUP_STEPHEIGHT", 28);
define("C_SETUP_VERSION", "4.8.5");
?>