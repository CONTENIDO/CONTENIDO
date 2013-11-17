/* global Con: true, jQuery: true */

/**
 * CONTENIDO help module.
 *
 * @module     help
 * @version    SVN Revision $Rev$
 * @requires   jQuery
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {
    'use strict';

    var NAME = 'help';

    /**
     * Help class
     *
     * @class Help
     * @static
     */
    Con.Help = {
        /**
         * @method show
         * @param {String} path
         */
        show: function(path) {
            var f1 = window.open(Con.cfg.urlHelp + path, 'contenido_help', 'height=500,width=600,resizable=yes,scrollbars=yes,location=no,menubar=no,status=no,toolbar=no');
            f1.focus();
        }
    };

    // @deprecated [2013-11-05] Downwards compatibility
    window.callHelp = Con.Help.show;

})(Con, Con.$);
