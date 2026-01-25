The smarty object is treated as a Singleton
You can now use the smarty object via
    $oMySmarty = cSmartyFrontend::getInstance();
in any contenido context.


Smarty templates are named as followed:
    sm.FILENAME.html


Reset of the smarty paths to default values is possible via:
    cSmartyFrontend::resetPaths();

Instructions for Smarty Upgrade/Update
    If you want to use the latest version of smarty go to
    http://www.smarty.net/download.php
    download the newest version
    delete the content in "smarty_sources"
    copy all content from the folder "Smarty-X.x/libs" into the "smarty_sources" folder