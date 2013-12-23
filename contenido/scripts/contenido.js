/* global jQuery: true */

/**
 * Main CONTENIDO JavaScript module.
 *
 * @module     contenido
 * @main       contenido
 * @submodule  base
 * @version    SVN Revision $Rev$
 * @requires   jQuery
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

/**
 * Initialization of CONTENIDO module
 * @param {Object} jQuery  jQuery object
 * @param {Object} scope  The scope, e. g. window
 * @returns {undefined}
 */
(function(jQuery, scope) {
//    'use strict';

    var NAME = 'contenido';

    /**
     * CONTENIDO class, is available as property Con in global scope, e. g. window.Con!
     * @class  Contenido
     * @static
     */
    var Con;
//    var $ = jQuery.noConflict();
    var $ = jQuery;

    // Create Con namespace in scope (window)
    /**
     * Common configurations
     * @property  cfg
     * @type {Object}
     * @static
     */

    /**
     * CONTENIDO session id
     * @property  sid
     * @type {Object}
     * @static
     */
    scope.Con = scope.Con || {Plugin: {}, cfg: {}, sid: 0};

    Con = scope.Con;

    // Assign jQuery instance
    /**
     * Reference to backend jQuery instance
     * @property  $
     * @type {jQuery}
     * @static
     */
    Con.$ = jQuery;

    // Browser detection
    // @TODO Should be removed, better to use feature detection
    var agent = navigator.userAgent.toLowerCase();
    Con.isMsie = (scope.document.all) ? true : false;
    Con.isNs = (agent.indexOf('netscape') >= 0 || agent.indexOf('mozilla') >= 0) ? true : false;

    Con.cfg.enableLog = true;

    /**
     * Registers namespace in global scope (window), if not exists.
     * @method namespace
     * @param  {String}  namespace  The full path to the desired namespace, like "Con.MyNamespace"
     * @return {Object}  The existing or new created namespace
     */
    Con.namespace = function(namespace) {
        var ns = namespace.split('.'),
            o = scope, i;
        for (i = 0; i < ns.length; i++) {
            o[ns[i]] = o[ns[i]] || {};
            o = o[ns[i]];
        }
        return o;
    };

    /**
     * Simple template parser.
     * Example:
     * <pre>
     * var tpl = 'Dear {abbreviation} {user}...',
     *     html = Con.parseTemplate(tpl, {abbreviation: 'Mrs.', user: 'Jane Doe'});
     * console.log(html);
     * </pre>
     * @method parseTemplate
     * @param  {String}  template  The template string to parse
     * @param  {Object}  replacements  Replacements object
     * @return {String}
     */
    Con.parseTemplate = function(template, replacements) {
        if (!template.replace || 'object' !== $.type(replacements)) {
            return template;
        }
        var regex = /\{\s*([^|}]+?)\s*(?:\|([^}]*))?\s*\}/g;
        return template.replace(regex, function(match, key) {
            return ('undefined' === $.type(replacements[key])) ? match : replacements[key];
        });
    };

    /**
     * Returns the desired backend frame by it's name.
     * @method getFrame
     * @param  {String}  name  The name of frame to get
     * @return {Window|null}
     */
    Con.getFrame = function(name) {
        try {
            switch (name) {
                case 'header':
                    return scope.top.header;
                case 'content':
                    return scope.top.content;
                case 'left':
                    return scope.top.content.left;
                case 'left_deco':
                    return scope.top.content.left.left_deco;
                case 'left_top':
                    return scope.top.content.left.left_top;
                case 'left_bottom':
                    return scope.top.content.left.left_bottom;
                case 'right':
                    return scope.top.content.right;
                case 'right_top':
                    if ('undefined' !== $.type(scope.top.content.right)) {
                        return scope.top.content.right.right_top;
                    } else {
                        return scope.top.content.right_top;
                    }
                case 'right_bottom':
                    if ('undefined' !== $.type(scope.top.content.right)) {
                        return scope.top.content.right.right_bottom;
                    } else {
                        return scope.top.content.right_bottom;
                    }
            }
        } catch (e) {
            Con.log(["getFrame: Couldn't get frame " + name, e], NAME, 'warn');
            return null;
        }
    };

    /**
     * Wrapper for console object.
     * @method log
     * @param {Mixed}  mixedVar  Any type of variable to print to the console
     * @param {String}  source   The source (template, page name, js module, etc.) who called this method
     * @param {String}  [severity='log']  Type of severity, feasible is 'log', 'info', 'warn', 'error'
     */
    Con.log = function(mixedVar, source, severity) {
        severity = severity || 'log';

        if (!Con.cfg.enableLog) {
            return;
        } else if (-1 === $.inArray(severity, ['log', 'info', 'warn', 'error'])) {
            return;
        }

        if (scope.console && 'function' === typeof scope.console[severity]) {
            var msg = severity.toUpperCase() + ': ' + source + ': ';
            scope.console[severity](msg, mixedVar);
        }
    };

    // Console emulation, to prevent errors if console is not available
    if (!('console' in scope)) {
        (function() {
            scope.console = {
                log: function() {
                },
                debug: function() {
                },
                info: function() {
                },
                warn: function() {
                },
                error: function() {
                }
            };
        })();
    }

})(jQuery, window);
