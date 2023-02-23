/* global Con: true, jQuery: true */

/**
 * Main CONTENIDO application startup script, bootstraps the page.
 * Should be loaded after setting the configuration!
 *
 * @requires   jQuery, Con
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

(function(Con, $, scope) {

    /**
     * Module to startup CONTENIDO application.
     *
     * Usage of tooltips in CONTENIDO backend:
     * ---------------------------------------
     * 1. Create a link with the class 'i-link' and the 'data-tooltip-id'
     *    attribute containing the id of the element to show as tooltip.
     * 2. Create an element with the class 'nodisplay' or an inline-style
     *    'display:none;', and the 'id' attribute, which is referenced by
     *    the corresponding link. The element has to be hidden, and it is
     *    just a container for the tooltip content.
     *
     * NOTES:
     * The link and the corresponding tooltip content element can be separately
     * rendered, their dom is not bound together.
     * The old way to use the id attribute in the link with the ending "-link"
     * is still supported, but deprecated.
     *
     * Example:
     * --------
     * <a href="javascript:void(0)" data-tooltip-id="my_unique_id" title="" class="i-link">Show tooltip</a>
     *
     * <div id="my_unique_id">
     *     <strong>Title</strong><br>
     *     <p>Some text to display</p>
     * </div>
     *
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
            var id = $(this).data('tooltip-id');
            if (!id) {
                // Check the old way, which is deprecated, but still supported
                id = $(this).attr('id').substring(0, $(this).attr('id').indexOf('-link'));
            }
            $(this).aToolTip({
                clickIt:    true,
                xOffset:    -20,
                yOffset:    4,
                outSpeed:   250,
                removeOnOutsideClick: true,
                tipContent: $('#' + id).html()
            });
        });
    });

    // ########################################################################
    // Frame 1 (left_top) related initialization
    // @TODO: This should be outsourced to a file like "startup.left_top.js"

    if (scope.name && 'left_top' === scope.name) {
        $(function() {
            Con.FrameLeftTop.resize({initial: true});
        });
        //$(top.window).on('resize', function() {
        //    console.log('resize');
        //    Con.FrameLeftTop.resize();
        //});
    }

})(Con, Con.$, window);
