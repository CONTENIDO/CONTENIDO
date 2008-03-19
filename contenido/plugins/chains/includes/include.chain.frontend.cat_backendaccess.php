<?php
function cecFrontendCategoryAccess_Backend($idlang, $idcat, $user)
{
	global $cfg;
	$sql = "SELECT idright 
					FROM ".$cfg["tab"]["rights"]." AS A,
						 ".$cfg["tab"]["actions"]." AS B,
						 ".$cfg["tab"]["area"]." AS C
					 WHERE B.name = 'front_allow' AND C.name = 'str' AND A.user_id = '".$user."' AND A.idcat = '$idcat'
							AND A.idarea = C.idarea AND B.idaction = A.idaction AND A.idlang = $idlang";
	$db2 = new DB_Contenido;
	$db2->query($sql);

	if (!$db2->next_record())
	{
		return false;
	}
	else
	{
		return true;
	}
}
?>