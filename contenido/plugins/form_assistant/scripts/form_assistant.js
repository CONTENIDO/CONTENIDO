/* global Con: true, jQuery: true */

/**
 * Plugin Form Assistant main JavaScript file
 *
 * @module     plugin
 * @submodule  form-assistant
 * @version    SVN Revision $Rev$
 * @requires   jQuery, Con
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {

    var NAME = 'plugin-form-assistant';

    /**
     * @class FormAssistant
     * @extends Con.Plugin
     */
    var FormAssistant = {

        /**
         * @property _translations
         * @type {Object}
         */
        translations: {},

        /**
         * @method getTrans
         * @param  {String}  key
         * @return  {String}  Decoded value
         */
        getTrans: function(key) {
            // get translations
            var value = FormAssistant.translations[key];
            // htmldecode value
            value = $('<div/>').html(value).text();
            return value;
        }

    };

    // Assign to Con.Plugin namespace
    Con.Plugin.FormAssistant = FormAssistant;

})(Con, Con.$);
