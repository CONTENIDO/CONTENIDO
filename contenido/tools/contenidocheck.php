<?php

echo "<pre>";
checkOwnerDir(getcwd()."/../");
checkWriteDir("cronjobs");
checkWriteDir("logs");
echo "checkPerms beendet.";
echo "</pre>";

function checkWriteDir ($dir)
{
	$fp = @fopen($dir."/temp.txt","ab+");
	
	if ($fp)
	{
		fclose($fp);
		unlink($dir."/temp.txt");
	} else {
		echo "<font color=\"red\">Fehler:</font> Verzeichnis $dir ist nicht schreibbar\n";
	}
		
}
function checkOwnerDir ($from_path)
{
        $old_path = $from_path;
    if (is_dir($from_path))
    {
        chdir($from_path);
        $myhandle=opendir('.');

        while (($myfile = readdir($myhandle))!==false)
        {
            if (($myfile != ".") && ($myfile != ".."))
            {
                if (fileowner($myfile) != getmyuid())
                {
                        $ownerInfo = posix_getpwuid(fileowner($myfile));

                        echo "<font color=\"red\">Fehler:</font> Der Besitzer \"".$ownerInfo["name"]."\" der Datei ".$from_path.$myfile." ist unterschiedlich zu dem Besitzer (\"".get_current_user()."\") des aktuellen Scriptes. Stellen Sie sicher, daß die Datei den gleichen Besitzer (owner) erhält wie das Script.\n";
                }

                if (is_dir($myfile))
                {
                    checkOwnerDir ($from_path.$myfile."/");
                    chdir($from_path);
                }

            }
        }
        closedir($myhandle);
    }

    chdir($old_path);
    return;
}


?>
