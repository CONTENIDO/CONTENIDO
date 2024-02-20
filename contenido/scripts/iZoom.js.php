<?php

/**
 * iZoom JavaScript "pipe"
 *
 * @package    CONTENIDO Backend scripts
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2023-02-16] Since 4.10.2, The upload area uses jQuery UI dialog, there is no need for iZoom, see upl_files_overview.js.
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

$cfg = cRegistry::getConfig();
$belang = cRegistry::getBackendLanguage();

i18nInit($cfg['path']['contenido_locale'], $belang);
// do not call cRegistry::shutdown(); here because
// it will print <script> tags which result in errors

?>
/**
 * CONTENIDO iZoom JavaScript module.
 *
 * @author     Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

console.warn('The usage of iZoom is deprecated since CONTENIDO 4.10.2, it has been replaced by jQuery UI dialog, see upl_files_overview.js!');

/**
 * Display an image in a pop-up window
 *
 * @param {String}  path  image path
 */
function iZoom(path) {
    var defaultWidth = 640;
    var defaultHeight = 480;

    window.top.avail

    var xwin = parseInt((screen.availWidth / 2) - (defaultWidth / 2));
    var ywin = parseInt((screen.availHeight / 2) - (defaultHeight / 2));

    var zwin = window.open("", "", "menubar=no,status=no,resizable=no,toolbar=no,statusbar=no,scrollbars=no,left="+xwin+",top="+ywin+",width=" + defaultWidth + ",height=" + defaultHeight + "\"");
    zwin.moveTo(xwin, ywin);

    var zcon  = "<html>\n<head>\n<title><?php echo i18n("Click to close"); ?></title>\n</head>\n";
    zcon += "<body bgcolor=\"#ffffff\" onload=\"self.resizeTo(zimg.offsetWidth+40,zimg.offsetHeight+80);self.moveTo((screen.availWidth / 2) - (zimg.offsetWidth / 2 + 5),(screen.availHeight / 2) - (zimg.offsetHeight / 2 + 20))\" leftmargin=\"0\" topmargin=\"0\" marginwidth=\"0\" marginheight=\"0\">\n";
    zcon += "<table width=\"100%\" height=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\"><tr><td align=\"center\" valign=\"middle\">";
    zcon += "<a href=\"javascript:self.close()\"><img style=\"border: 1px; border-style: solid; border-color: black;\" name=\"zimg\" src=\""+path+"\" border=\"0\" alt=\"<?php echo i18n("Click to close"); ?>\" title=\"<?php echo i18n("Click to close"); ?>\"></a>\n";
    zcon += "</td></tr></table></body>\n</html>";

    zwin.document.open();
    zwin.document.write(zcon);
    zwin.document.close();
}