/* global Con: true, jQuery: true */

/**
 * CONTENIDO JavaScript parameter collector module
 *
 * @module     parameter-collector
 * @requires   jQuery, Con
 * @author     ??
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {

    var NAME = 'parameter-collector';

    /**
     * Collected parameters
     * @property  parameters
     * @type {Object}
     * @private
     */
    var parameters = {};

    /**
     * ParameterCollector class
     * @class  ParameterCollector
     * @static
     */
    var ParameterCollector = {

        /**
         * Registers a parameter in current document
         * @param {String|Object} nameOrObj - Either name or an object with key/value pairs for multiple parameters to register
         * @param {String} [value] - Only used to register a single parameter together with the name parameter as of type string
         *
         * Examples:
         * <pre>
         *     // Multiple parameter
         *     Con.ParameterCollector.register({param1: 'value1', param2: 'value2'});
         *     // Single parameter
         *     Con.ParameterCollector.register('param1', 'value1');
         * </pre>
         */
        register: function(nameOrObj, value) {
            if ($.isPlainObject(nameOrObj)) {
                $.each(nameOrObj, function (key, value) {
                    parameters[key] = value;
                });
            } else {
                parameters[nameOrObj] = value;
            }
            //console.log(NAME + ' register parameters', parameters);
        },

        /**
         * Returns all registered parameters, either as an query string usable for urls or as a object.
         * @param {Boolean} [asQueryString=true] - Flag to return them as a string (query string) or as object
         * @returns {String|Object}
         */
        getAll: function(asQueryString) {
            asQueryString = typeof asQueryString === 'boolean' ? asQueryString : true;

            if (asQueryString) {
                //console.log(NAME + ' get output', $.param(parameters));
                return $.param(parameters);
            } else {
                //console.log(NAME + ' get output', $.extend({}, parameters));
                return $.extend({}, parameters);
            }
        },

        /**
         * Appends all registered parameter to the given form as an input of type hidden.
         * @param {jQuery} form
         */
        appendAll: function(form) {
            $.each(parameters, function(name, value) {
                if (Array.isArray(value)) {
                    $.each(value, function(p, valueItem) {
                        $('<input>').attr({
                            type: 'hidden',
                            name: name + '[]',
                            value: valueItem,
                        }).appendTo(form);
                    });
                } else {
                    $('<input>').attr({
                        type: 'hidden',
                        name: name,
                        value: value,
                    }).appendTo(form);
                }
            });
        }
    };

    Con.ParameterCollector = ParameterCollector;

    // Register old variables/functions in window to be downwards compatible
    window.m_documentParameters = parameters;
    window.register_parameter = Con.ParameterCollector.register;
    window.get_registered_parameters = Con.ParameterCollector.getAll;
    window.append_registered_parameters = Con.ParameterCollector.appendAll;

})(Con, Con.$);
