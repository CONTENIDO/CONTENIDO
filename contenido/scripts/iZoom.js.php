<?php

/* 
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * iZoom JavaScript "pipe"
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend scripts
 * @version    1.0.3
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-06-16, H. Librenz, Hotfix: Added check for invalid calls
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-07-02, Frederic Schneider, include security_class
 *   modified 2010-05-20, Murat Purc, standardized CONTENIDO startup and security check invocations, see [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */ 
 
if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

// CONTENIDO startup process
include_once ('../includes/startup.php');

header("Content-Type: text/javascript");

page_open(array('sess' => 'Contenido_Session',
                'auth' => 'Contenido_Challenge_Crypt_Auth',
                'perm' => 'Contenido_Perm'));

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
page_close();
?>

/**
 * Display an image in a pop-up window
 *
 * @param string image path
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG
 */
function iZoom(path)
{
    var defaultWidth = 640;
    var defaultHeight = 480;


    var xwin = parseInt((screen.availWidth / 2) - (defaultWidth / 2));
    var ywin = parseInt((screen.availHeight / 2) - (defaultHeight / 2));

    zwin = window.open("","","menubar=no,status=no,resizable=no,toolbar=no,statusbar=no,scrollbars=no,left="+xwin+",top="+ywin+",width=" + defaultWidth + ",height=" + defaultHeight + "\"");
    zwin.moveTo(xwin,ywin);

    zcon  = "<html>\n<head>\n<title><?php echo i18n("Click to close"); ?></title>\n</head>\n";
    zcon += "<body bgcolor=\"#ffffff\" onload=\"self.resizeTo(zimg.offsetWidth+40,zimg.offsetHeight+80);self.moveTo((screen.availWidth / 2) - (zimg.offsetWidth / 2 + 5),(screen.availHeight / 2) - (zimg.offsetHeight / 2 + 20))\" leftmargin=\"0\" topmargin=\"0\" marginwidth=\"0\" marginheight=\"0\">\n";
	zcon += "<table width=\"100%\" height=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td align=\"center\" valign=\"middle\">";
    zcon += "<a href=\"javascript:self.close()\"><img style=\"border: 1px; border-style: solid; border-color: black;\" name=\"zimg\" src=\""+path+"\" border=\"0\" alt=\"<?php echo i18n("Click to close"); ?>\" title=\"<?php echo i18n("Click to close"); ?>\"></a>\n";
    zcon += "</td></tr></table></body>\n</html>";

    zwin.document.open();
    zwin.document.write(zcon);
    zwin.document.close();

}