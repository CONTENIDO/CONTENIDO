<?PHP

cInclude('includes', 'functions.upl.php');

$clientData = cRegistry::getClientConfig(1);

$uplPath = $clientData['upl']['path'];

$path = realpath($uplPath);

$objects = new RecursiveIteratorIterator(
               new RecursiveDirectoryIterator($path),
               RecursiveIteratorIterator::SELF_FIRST);

uplSyncDirectory('');
echo 'Haupt-Verzeichnis: erfolgreich synchronisiert <br />';

foreach($objects as $name => $path){

       $file = $path->getPathname();
       
       if(is_dir($file)){
            $file_name_backslashes_replaced = str_replace('\\', '/', $file).'/';
            $syncDirName =  (substr($file_name_backslashes_replaced,strpos($file_name_backslashes_replaced,'upload/')+7,strlen($file_name_backslashes_replaced)));
            uplSyncDirectory($syncDirName);
            echo 'Verzeichnis: ' . $syncDirName . ' erfolgreich synchronisiert <br />';  
       }
}

echo '</pre>';

?>