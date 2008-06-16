<?php

if (isset($_REQUEST['cfg'])) {
	die();
}

include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);

?>