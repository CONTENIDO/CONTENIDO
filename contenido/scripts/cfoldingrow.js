/* global Con: true, jQuery: true */

/**
 * FoldingRow JavaScript helper module
 *
 * @module     folding-row
 * @requires   jQuery, Con
 * @author     Unknown
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */


(function(Con, $) {
//    'use strict';

    var NAME = 'folding-row';

    var IMG_EXPANDED = 'images/widgets/foldingrow/expanded.gif',
        IMG_COLLAPSED = 'images/widgets/foldingrow/collapsed.gif';

    /**
     * Folding row class
     * @class  FoldingRow
     * @static
     */
    Con.FoldingRow = {

        /**
         * Expands or collapses the folding row element
         * @method toggle
         * @param  {String}  image  Image element id
         * @param  {String}  row  Row element id (the row to show hide)
         * @param  {String}  hidden  Hidden form field id to store the state
         * @param  {String}  uuid  Unique id to persist the state
         * @static
         */
        toggle: function(image, row, hidden, uuid) {
            var $img = $('#' + image),
                $row = $('#' + row),
                $hidden = $('#' + hidden);

            if ($img.data('state') == 'collapsed') {
                $row.css('display', '');
                $img.attr('src', IMG_EXPANDED);
                $img.data('state', 'expanded');
                $hidden.val('expanded');
                register_parameter('u_register[expandstate][' + uuid + ']', 'true');
            } else {
                $row.css('display', 'none');
                $img.attr('src', IMG_COLLAPSED);
                $img.data('state', 'collapsed');
                $hidden.val('collapsed');
                register_parameter('u_register[expandstate][' + uuid + ']', 'false');
            }
        }
    };

    // @deprecated [2013-10-21] Assign to windows scope (downwards compatibility)
    window.cGuiFoldingRow_expandCollapse = Con.FoldingRow.toggle;

})(Con, Con.$);
