<?php

/**
 * iZoom JavaScript "pipe"
 *
 * @package    CONTENIDO Backend scripts
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @todo       Find a JS solution to load required translation string
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('../includes/startup.php');

header('Content-Type: text/javascript');

cRegistry::bootstrap(
    [
        'sess' => 'cSession',
        'auth' => 'cAuthHandlerBackend',
        'perm' => 'cPermission',
    ]
);

i18nInit($cfg['path']['contenido_locale'], $belang);
// do not call cRegistry::shutdown(); here because
// it will print <script> tags which result in errors

?>
/**
 * CONTENIDO iZoom JavaScript module.
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

/**
 * Display an image in a pop-up window
 *
 * @param {String}  path  image path
 */
function iZoom(path) {
    var defaultWidth = 640;
    var defaultHeight = 480;

    var xwin = parseInt((screen.availWidth / 2) - (defaultWidth / 2));
    var ywin = parseInt((screen.availHeight / 2) - (defaultHeight / 2));

    zwin = window.open("", "", "menubar=no,status=no,resizable=no,toolbar=no,statusbar=no,scrollbars=no,left="+xwin+",top="+ywin+",width=" + defaultWidth + ",height=" + defaultHeight + "\"");
    zwin.moveTo(xwin, ywin);

    zcon  = "<html>\n<head>\n<title><?php echo i18n("Click to close"); ?></title>\n</head>\n";
    zcon += "<body bgcolor=\"#ffffff\" onload=\"self.resizeTo(zimg.offsetWidth+40,zimg.offsetHeight+80);self.moveTo((screen.availWidth / 2) - (zimg.offsetWidth / 2 + 5),(screen.availHeight / 2) - (zimg.offsetHeight / 2 + 20))\" leftmargin=\"0\" topmargin=\"0\" marginwidth=\"0\" marginheight=\"0\">\n";
    zcon += "<table width=\"100%\" height=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td align=\"center\" valign=\"middle\">";
    zcon += "<a href=\"javascript:self.close()\"><img style=\"border: 1px; border-style: solid; border-color: black;\" name=\"zimg\" src=\""+path+"\" border=\"0\" alt=\"<?php echo i18n("Click to close"); ?>\" title=\"<?php echo i18n("Click to close"); ?>\"></a>\n";
    zcon += "</td></tr></table></body>\n</html>";

    zwin.document.open();
    zwin.document.write(zcon);
    zwin.document.close();
}