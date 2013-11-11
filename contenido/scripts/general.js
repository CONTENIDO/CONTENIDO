/* global Con: true, jQuery: true */

/**
 * This file contains general functions which are potentially helpful for every
 * backend page. The file should therefore be included in every backend page.
 *
 * Following modules are implemented here:
 * - Registry
 * - Loader
 * - UtilUrl
 * - FrameLeftTop
 *
 * @module     contenido
 * @version    SVN Revision $Rev:$
 * @requires   jQuery, Con
 *
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {

    var NAME = 'registry';

    /**
     * Registry class
     * @submodule  registry
     * @class  Registry
     * @static
     */
    Con.Registry = {
        /**
         * @property _instances
         * @type {Object}
         * @private
         */
        _instances: {},
        /**
         * @method set
         * @param {String}  key
         * @param {Mixed}  value
         */
        set: function(key, value) {
            this._instances[key] = value;
        },
        /**
         * @method get
         * @param {String}  key
         * @return {Mixed}
         */
        get: function(key) {
            if ('undefined' === $.type(this._instances[key])) {
//                Con.log('Registry.get: No entry is registered for key ' + key, NAME, 'warn');
                return null;
            }
            return this._instances[key];
        },
        /**
         * @method isRegistered
         * @param {String}  key
         * @return {Boolean}
         */
        isRegistered: function(key) {
            return ('undefined' === $.type(this._instances[key]));
        },
        /**
         * @method remove
         * @param {String}  key
         */
        remove: function(key) {
            delete this._instances[key];
        }
    };

    // @deprecated [2013-10-15] Assign to windows scope (downwards compatibility)
    window.ContenidoRegistry = Con.Registry;

})(Con, Con.$);


// ############################################################################


(function(Con, $) {

    var NAME = 'loader';

    /**
     * Asset (script) loader class
     * @submodule  base-loader
     * @class  Loader
     * @static
     */
     Con.Loader = {
        /**
         * @property _loaded
         * @type {Object}
         * @private
         */
        _loaded: {},
        /**
         * @property _stack
         * @type {Object}
         * @private
         */
        _stack: {},

        /**
         * Evaluates the given callbacks.
         * @method _conEvaluateCallbacks
         * @param  {Array}  callbacks  List of callbacks. A callback is either a simple string
         *             which can be evaluated or an object with callback, scope and params
         *             properties.
         * @private
         * @static
         */
        _conEvaluateCallbacks: function(callbacks) {
            $.each(callbacks, function(index, value) {
                var type = $.type(value);
                if ('object' === type) {
                    // Object callback, call it with the appropriate scope
                    value.callback.apply(value.scope, value.params);
                } else if ('string' === type) {
                    // Simple callback, just evaluate it
                    Con.log('_conEvaluateCallbacks: Deprecated string callback!', NAME, 'warn');
                    eval(value);
                }
            });
        },

        /**
         * Loads the given script and evaluates the given callback function
         * when the script has been loaded successfully. The callback can be
         * a simple string which is evaluated or a function which is called with
         * the given scope and params.
         *
         * @method get
         * @param {String}  script  The path to the script which should be loaded
         * @param {Function|String}  [callback='']  Code which should be evaluated
         *             after the script has been loaded or a callback function
         *             which is called with the given params and the given scope
         * @param {Object}  [scope=this]  The scope with which the callback function should be called
         * @param {Array}  [params=[]]  Array of params which should be passed to the callback function
         * @return {Boolean}
         * @static
         */
        get: function(script, callback, scope, params) {
    //console.log('conLoadFile, script', script);
            callback = ('undefined' === $.type(callback)) ? function() {} : callback;
            scope = ('undefined' === $.type(scope)) ? this : scope;
            params = ('undefined' === $.type(params)) ? [] : params;

            // check if callback has to be called on the scope object
            var isObjectCallback = (typeof callback === 'function' && typeof scope === 'object');

            // only load the script if it has not been loaded yet
            if (Con.Loader._loaded[script] !== 'true') {
                // initialise callback stack
                if ('undefined' === $.type(Con.Loader._stack[script])) {
                    Con.Loader._stack[script] = [];
                }

                // push new entry onto the callback stack depending on the callback type
                if (isObjectCallback) {
                    var newCallback = {
                        callback: callback,
                        scope: scope,
                        params: params
                    };
                    Con.Loader._stack[script].push(newCallback);
                } else {
                    Con.Loader._stack[script].push(callback);
                }

                // if script is not already loading, load it and evaluate the callbacks
                if (Con.Loader._loaded[script] !== 'pending') {
                    Con.Loader._loaded[script] = 'pending';
                    $.getScript(script).done(function() {

                        // A loaded script doesn't mean that the required code vas initialized and
                        // processes imediately. Wait few ms before coninue with it...
                        window.setTimeout(function() {
                            Con.Loader._loaded[script] = 'true';
                            Con.Loader._conEvaluateCallbacks(Con.Loader._stack[script]);
    //console.log('conLoadFile, done', script);
                        }, 50);
                    }).fail(function(jqxhr, settings, exception) {
                        //console.log('failed to load ' + script);
                        //console.log(jqxhr);
                        //console.log(settings);
                        //console.log(exception);
                    });
                }
            } else {
    //console.log('conLoadFile, loaded', script);
                // script is already loaded, so just evaluate the callback
                if (isObjectCallback) {
                    callback.apply(scope, params);
                } else {
                    eval(callback);
                }
            }

            return true;
        }
    };

    // @deprecated [2013-10-15] Assign to windows scope (downwards compatibility)
    window.conEvaluateCallbacks = Con.Loader._conEvaluateCallbacks;
    window.conLoadFile = Con.Loader.get;

})(Con, Con.$);


// ############################################################################


(function(Con, $, scope) {

    var NAME = 'frame-left-top';

    /**
     * @property $_container
     * @type {HTMLElement[]}
     * @private
     */
    var $_container = null;

    /**
     * FrameLeftTop class
     * @submodule  base-frame-left-top
     * @class  FrameLeftTop
     * @static
     */
    Con.FrameLeftTop = {

        /**
         * Resize top left frame.
         * Retrieves the container element top_left_container and it's data attributes to handle the
         * resize.
         *
         * Following attributes are supported:
         * - data-resizegap: (Number)  The amount of exta pixels to add to the detected content height of top left frame
         * - data-resizeinitcb: (String) Optional a callback to call which does the initial frame resizing
         *
         * @method resize
         * @param  {Object}  [options={}]  Additional options for resizing as follows.
         * <pre>
         *     options.initial  (Boolean)  Flag for initial call of resize, e. on document ready
         *     options.resizegap  (Number)  The resize gab passed manually.
         * </pre>
         *
         * @param  {Boolean}  [initial=false]  Flag to initial call of resize, e. on document ready
         */
        resize: function(options) {
            var opt = $.extend({
                initial: false,
                resizegap: null
            }, options || {});

            var $container = Con.FrameLeftTop._getContainer(),
                callback, gap;

            if (!$container[0]) {
                Con.log("resize: Couldn't get container element!", NAME, 'warn');
                return;
            }

            if (opt.initial) {
                // Check for data-resizeinitcb for initial resizing
                callback = ($container.data('resizeinitcb')) ? $container.data('resizeinitcb') : null;
            }

            if ('number' === $.type(opt.resizegap)) {
                gap = opt.resizegap;
            } else {
                gap = (false === isNaN($container.data('resizegap'))) ? $container.data('resizegap') : 0;
            }

            if (callback && 'function' === $.type(scope[callback])) {
                scope[callback]();
            } else {
                Con.getFrame('content').frameResize.resizeTopLeftFrame($container.height() + gap);
            }
        },

        /**
         * @method _getContainer
         * @return {HTMLElement[]}
         * @private
         */
        _getContainer: function() {
            if (null === $_container) {
                $_container = $('#top_left_container');
            }
            return $_container;
        }
    };

})(Con, Con.$, window);


// ############################################################################


(function(Con, $, scope) {

    var NAME = 'util-url';

    /**
     * URL utily class
     * @submodule  base-util-url
     * @class  UtilUrl
     * @static
     */
    Con.UtilUrl = {

        /**
         * Builds a CONTENIDO backend url, adds also the contenido parameter to it,
         * if it's not passed with params.
         *
         * Example:
         * <pre>
         * // result: main.php?area=con&action=new&frame=4&contenido=123434
         * var url = Con.UtilUrl.build("main.php", {area: 'con', action: 'new', frame: 4});
         * </pre>
         *
         * @method build
         * @param {String} page
         * @param {Object}  [params={}]
         * @return {String}
         * @static
         */
        build: function(page, params) {
            params = params || {};

            var query = [];

            $.each(params, function(name, value) {
                query.push(name + '=' + value);
            });

            if (!params.contenido) {
                query.push('contenido=' + Con.sid);
            }

            return page + '?' + query.join('&');
        },

        /**
         * Returns protocol + hostname + path (without filename & query string) from a given url.
         * @method getUrlWithPath
         * @param  {String}  [url]  Url to determine params from, uses window.location.href by default
         * @return {String}  The folder starting like 'http://hostname/some/path/'
         * @static
         */
        getUrlWithPath: function(url) {
            url = url || scope.location.href;
            return decodeURI(url.substring(0, (url.lastIndexOf('/', url.indexOf('?')) + 1)));
        },

        /**
         * Extracs all query parameters from a given url and returns them back.
         * Example:
         * <pre>
         * // result: {foobar: '1', user: 'JaneDoe'}
         * var params = Con.UtilUrl.getParams("/page.html?foobar=1&user=JaneDoe");
         * </pre>
         * @method getParams
         * @param  {String} [url]  Url to determine params from, uses window.location.href by default
         * @return {Object}
         * @static
         */
        getParams: function(url) {
            url = url || scope.location.href;

            var params = {},
                parts = url.split('?');

            if (2 === parts.length) {
                var queryString = parts[1];
                queryString = queryString.split('&');
                $.each(queryString, function(pos, value) {
                    var query = value.split('=');
                    if (2 === query.length) {
                        params[query[0]] = query[1];
                    }
                });
            }

            return params;
        },

        /**
         * Adds parameters to the url, overwrites existing parameters or removes them from the url.
         * @method replaceParams
         * @param   {String}  url  The url to change the query parameters
         * @param   {Object}  params  Key value pairs of params to update or remove.
         *                            NB: A null value will remove the parameter!
         *                                e. g. {action: null} will remove existing action parameter.
         * @return  {String}
         * @static
         */
        replaceParams: function(url, params) {
            var parts = url.split('?'),
                paramsOrg = Con.UtilUrl.getParams(url),
                query = '';

            if (2 === parts.length) {
                $.each(params, function(key, value) {
                    if (null === value) {
                        if ('undefined' !== $.type(paramsOrg[key])) {
                            delete paramsOrg[key];
                        }
                    } else {
                        paramsOrg[key] = value;
                    }
                });

                $.each(paramsOrg, function(key, value) {
                    if ('' !== query) {
                        query += '&';
                    }
                    query += key + '=' + value;
                });

                url = parts[0] + '?' + query;
            }

            return url;
        },

        /**
         * Returns true if the parameter is a valid URL
         * @method validate
         * @param {String}  value  The string which will be checked
         * @return {Boolean} True if value is a URL
         * @static
         */
        validate: function(value) {
            var urlregex = /(http:\/\/www.|https:\/\/www.|www.|http:\/\/|https:\/\/){1}(([0-9A-Za-z]+\.))|(localhost)/;
            if (urlregex.test(value)) {
                return true;
            }
            return false;
        }
    };

    // @deprecated [2013-10-15] Assign to windows scope (downwards compatibility)
    window.getUrlParams = Con.UtilUrl.getParams;
    window.validateURL = Con.UtilUrl.validate;

})(Con, Con.$, window);


// ############################################################################


(function(Con, $) {

    /**
     * Miscellaneous/common functions, extends Con.
     * @class Common
     * @extends Contenido
     * @module     contenido
     * @submodule  base-common
     * @static
     */

    // Fallback for not being able to load translations
    var TRANSLATIONS = {
        OK: 'OK',
        Cancel: 'Cancel',
        'Confirmation Required': 'Confirmation Required'
    };

    /**
     * Javascript Multilink Example:
     * <pre>
     * Con.multiLink (
     *     'frame1', 'link',
     *     'frame2', 'link',
     *     ...,
     *     'simpleFrame'
     * );
     * </pre>
     *
     * @method multiLink
     * @param [arguments*]
     *            optional amount of arguments used pairwise for assigning URLs to
     *            frame names in CONTENIDO. The last argument is optional but must
     *            (!) be 'simpleFrame' if used to specify that the complete frame
     *            structure is not available.
     */
    Con.multiLink = function() {
        // get last argument
        var tmp = arguments[arguments.length - 1];
        // check by last argument if reduced frame structure is used
        var simpleFrame = (tmp === 'simpleFrame') ? true : false;
        // change for-loop counter if last parameter is used to identify simple
        // frame multilinks
        var len = (simpleFrame) ? arguments.length - 1 : arguments.length;

        var frame, f, l, i;

        for (i = 0; i < len; i += 2) {
            f = arguments[i];
            l = arguments[i + 1];

            if (simpleFrame) {
                // use simple frame
                parent.frames[f].location.href = l;
            } else {
                // use classic multilink structure
                frame = Con.getFrame(f);
                if (frame) {
                    frame.location.href = l;
                }
            }
        }
    };

    /**
     * Returns the registry object, from top.header or top frame
     * @method getRegistry
     * @return {Registry|NULL}
     */
    Con.getRegistry = function() {
        var frame = Con.getFrame('header');
        if (frame && frame.Con && frame.Con.Registry) {
            return frame.Con.Registry;
        }

        frame = window.top;
        if (frame && frame.Con && frame.Con.Registry) {
            return window.top.Con.Registry;
        } else {
            return null;
        }
    };

    /**
     * Determines the window in which all the content is being displayed and returns it.
     * @method getContentWindow
     * @return {Window} The window object in which all content is being displayed
     */
    Con.getContentWindow = function() {
        var frame = Con.getFrame('right_bottom');
        if (frame) {
            return frame;
        } else {
            return window;
        }
    };

    /**
     * Loads the translations from the server once and just returns them if they
     * have already been loaded.
     * @method getTranslations
     * @param {Function}  [callback]  The callback function to call after retrieving translations
     * @param {Object}  [context]
     */
    Con.getTranslations = function(callback, context) {
        callback = callback || function() {};
        context = context || this;

        var registry = Con.getRegistry();

        if (!registry) {
            setTimeout(function() {
                Con.getTranslations(callback, context);
            }, 50);
            return;
        }

        // If the translations have not been loaded yet, do it now
        if (null === registry.get('translations')) {
            if (!Con.sid) {
                registry.set('translations', {});
            } else {
                $.ajax({
                    async: false,
                    url: 'ajaxmain.php',
                    data: 'ajax=generaljstranslations&contenido=' + Con.sid,
                    dataType: 'json',
                    success: function(data) {
                        registry.set('translations', data);
                        callback.call(context, data);
                    },
                    error: function(data) {
                        callback.call(context, null);
                        Con.log('getTranslations: Could not get translations', 'general.js', 'error');
                    }
                });
            }
        }

        callback.call(context, registry.get('translations'));
    };

    /**
     * Shows a confirmation box with the help of jQuery UI Dialog.
     * @method showConfirmation
     * @param  {String}  description  The text which is displayed in the dialog
     * @param  {Function}  callback  A callback function which is called if the user confirmed
     * @param  {Object}  additionalOptions  Options which can be used to customise the behaviour
     *            of the dialog box
     */
    Con.showConfirmation = function(description, callback, additionalOptions) {
        // Get the translations so that we can use them
        Con.getTranslations(function(translations) {
            if (null === translations) {
                // Use fallback
                translations = TRANSLATIONS;
            }

            // Define the options and extend them with the given ones
            var contentWindow = getContentWindow(),
                buttons = {};

            buttons[translations.OK] = function() {
                if (typeof callback === 'function') {
                    callback();
                }
                contentWindow.$('#single_dialog').dialog('close');
            };
            buttons[translations.Cancel] = function() {
                contentWindow.$('#single_dialog').dialog('close');
            };

            var options = {
                modal: true,
                buttons: buttons,
                position: ['center', 50],
                resizable: false,
                close: function(event, ui) {
                    contentWindow.$('html').find('#single_dialog').remove();
                },
                title: translations['Confirmation Required']
            };
            options = $.extend(options, additionalOptions);

            // show the dialog in the content window
            if (0 === contentWindow.$('html').find('#single_dialog').length) {
                contentWindow.$('html').find('div.ui-dialog').remove();
                contentWindow.$('html').find('div.ui-widget-overlay').remove();
                contentWindow.$('html').find('#single_dialog').remove();

                contentWindow.$('<div id="single_dialog">' + description + '</div>').dialog(options);
            }

        }, this);
    };

    /**
     * Shows a notification box with the help of jQuery UI Dialog.
     * @method showNotification
     * @param  {String}  title The title of the box
     * @param  {String}  description  The text which is displayed in the box
     * @param  {Object}  additionalOptions  Options which can be used to customise the
     *           behaviour of the dialog box
     * @param  {Boolean} hideButtons
     */
    Con.showNotification = function(title, description, additionalOptions, hideButtons) {
        // Get the translations so that we can use them
        Con.getTranslations(function(translations) {
            if (null === translations) {
                // Use fallback
                translations = TRANSLATIONS;
            }

            // Define the options and extend them with the given ones
            var buttons = {};
            if (!hideButtons) {
                buttons[translations.OK] = function() {
                    // unfortunately, the following line does not work if the dialog is
                    // opened from another frame
                    // $(this).dialog('close');
                    // so use this ugly workaround
                    $(this).parent().remove();
                };
            }
            var options = {
                buttons: buttons,
                position: ['center', 50],
                title: title,
                modal: true
            };
            options = $.extend(options, additionalOptions);
            // show the dialog in the content window
            var contentWindow = getContentWindow();

            contentWindow.$('html').find('div.ui-dialog').remove();
            contentWindow.$('html').find('div.ui-widget-overlay').remove();

            contentWindow.$('<div>' + description + '</div>').dialog(options);

        }, this);
    };

    /**
     * Marks submenu item in header, handles also context of different frames
     * @method markSubmenuItem
     * @param {String} subMenu  The position of submenu or data-name value
     * @return  {Boolean}
     */
    Con.markSubmenuItem = function(subMenu) {
        var frame = Con.getFrame('right_top'),
            selector, menuItem;

        if (frame) {
//console.log(cElm.search(sub._posPrefix), 'cElm.search(sub._posPrefix)');
//console.log("#navlist [data-name='" + cElm + "'] a", 'data selektor');
            if (0 === subMenu.search('c_')) {
                selector = "#" + subMenu + " a:first";
            } else {
                selector = "#navlist [data-name='" + subMenu + "'] a:first";
            }

            menuItem = $(selector, frame.document)[0];
            if (menuItem) {
                frame.sub.clicked(menuItem);
                return true;
            }
        }

        return false;
    };

    // @deprecated [2013-10-15] Assign to windows scope (downwards compatibility)
    window.conMultiLink = Con.multiLink;
    window.getRegistry = Con.getRegistry;
    window.getContentWindow = Con.getContentWindow;
    window.getTranslations = Con.getTranslations;
    window.showConfirmation = Con.showConfirmation;
    window.showNotification = Con.showNotification;
    window.conMarkSubmenuItem = Con.markSubmenuItem;

})(Con, Con.$);
