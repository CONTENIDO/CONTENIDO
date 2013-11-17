/* global Con: true, jQuery: true */

/**
 * Main CONTENIDO application startup script, bootstraps the page.
 * Should be loaded after setting the configuration!
 *
 * @version    SVN Revision $Rev$
 * @requires   jQuery, Con
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $, scope) {

    /**
     * Module to startup CONTENIDO application.
     * @module startup
     */

    var NAME = 'startup';

    // ########################################################################
    // Common initialization

    // Get the translations once, so that they are already loaded, but only for authenticated users
    if (Con.sid) {
        Con.getTranslations();
    }

    $(function() {
        // Assigns the tooltip to backend info boxes
        $('a.i-link').each(function() {
            var id = $(this).attr('id').substring(0, $(this).attr('id').indexOf('-link'));
            $(this).aToolTip({
                clickIt:    true,
                xOffset:    -20,
                yOffset:    4,
                outSpeed:   250,
                tipContent: $('#' + id).html()
            });
        });
    });

    // If browser is IE, disable BackgroundImageCache
    // NOTE: This is used to disable background image caching in IE 6
    try {
        document.execCommand('BackgroundImageCache', false, true);
    } catch (err) {}


    // ########################################################################
    // Frame 1 (left_top) related initialization
    // @TODO: This should be outsourced to a file like "startup.left_top.js"

    if (scope.name && 'left_top' === scope.name) {
        $(function() {
            Con.FrameLeftTop.resize({initial: true});
        });
/*
        $(top.window).on('resize', function() {
            console.log('resize');
            Con.FrameLeftTop.resize();
        });
*/
    }

})(Con, Con.$, window);
