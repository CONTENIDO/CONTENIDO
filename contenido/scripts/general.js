/* global Con: true, jQuery: true */

/**
 * This file contains general modules which are potentially helpful for every
 * backend page. The file should therefore be included in every backend page.
 * Following modules are implemented here: - Registry - Loader - UtilUrl -
 * FrameLeftTop
 *
 * @module contenido
 * @requires jQuery, Con
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

(function(Con, $) {

    var NAME = 'registry';

    // define forEach loops on arrays for browsers who do not understand this (e.g. IE 8)
    // use definition from Mozilla
    // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/forEach
    if ('function' !== typeof Array.prototype.forEach) {
        Array.prototype.forEach = function(callback, thisArg) {

            var T, k;

            if (this == null) {
                throw new TypeError(' this is null or not defined');
            }

            // 1. Let O be the result of calling ToObject passing the |this| value as the argument.
            var O = Object(this);

            // 2. Let lenValue be the result of calling the Get internal method of O with the argument "length".
            // 3. Let len be ToUint32(lenValue).
            var len = O.length >>> 0;

            // 4. If IsCallable(callback) is false, throw a TypeError exception.
            // See: http://es5.github.com/#x9.11
            if (typeof callback !== "function") {
                throw new TypeError(callback + ' is not a function');
            }

            // 5. If thisArg was supplied, let T be thisArg; else let T be undefined.
            if (arguments.length > 1) {
                T = thisArg;
            }

            // 6. Let k be 0
            k = 0;

            // 7. Repeat, while k < len
            while (k < len) {

                var kValue;

                // a. Let Pk be ToString(k).
                //   This is implicit for LHS operands of the in operator
                // b. Let kPresent be the result of calling the HasProperty internal method of O with argument Pk.
                //   This step can be combined with c
                // c. If kPresent is true, then
                if (k in O) {

                    // i. Let kValue be the result of calling the Get internal method of O with argument Pk.
                    kValue = O[k];

                    // ii. Call the Call internal method of callback with T as the this value and
                    // argument list containing kValue, k, and O.
                    callback.call(T, kValue, k, O);
                }
                // d. Increase k by 1.
                k++;
            }
        // 8. return undefined
        };
    }

    // define indexOf on arrays for browsers who do not understand this (e.g. IE 8, IE 9)
    // use definition from Mozilla
    // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/indexOf#Polyfill
    if (!Array.prototype.indexOf) {
        Array.prototype.indexOf = function(searchElement, fromIndex) {

            var k;

            // 1. Let O be the result of calling ToObject passing
            //    the this value as the argument.
            if (this == null) {
                throw new TypeError('"this" is null or not defined');
            }

            var O = Object(this);

            // 2. Let lenValue be the result of calling the Get
            //    internal method of O with the argument "length".
            // 3. Let len be ToUint32(lenValue).
            var len = O.length >>> 0;

            // 4. If len is 0, return -1.
            if (len === 0) {
                return -1;
            }

            // 5. If argument fromIndex was passed let n be
            //    ToInteger(fromIndex); else let n be 0.
            var n = +fromIndex || 0;

            if (Math.abs(n) === Infinity) {
                n = 0;
            }

            // 6. If n >= len, return -1.
            if (n >= len) {
                return -1;
            }

            // 7. If n >= 0, then Let k be n.
            // 8. Else, n<0, Let k be len - abs(n).
            //    If k is less than 0, then let k be 0.
            k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);

            // 9. Repeat, while k < len
            while (k < len) {
                // a. Let Pk be ToString(k).
                //   This is implicit for LHS operands of the in operator
                // b. Let kPresent be the result of calling the
                //    HasProperty internal method of O with argument Pk.
                //   This step can be combined with c
                // c. If kPresent is true, then
                //    i.  Let elementK be the result of calling the Get
                //        internal method of O with the argument ToString(k).
                //   ii.  Let same be the result of applying the
                //        Strict Equality Comparison Algorithm to
                //        searchElement and elementK.
                //  iii.  If same is true, return k.
                if (k in O && O[k] === searchElement) {
                    return k;
                }
                k++;
            }
            return -1;
        };
    }

    // define Object.keys for browser that don't implement it (e.g. IE 8)
    // use definition from Mozilla
    // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/keys
    if (!Object.keys) {
        Object.keys = (function() {
            'use strict';
            var hasOwnProperty = Object.prototype.hasOwnProperty,
            hasDontEnumBug = !({ toString: null }).propertyIsEnumerable('toString'),
                dontEnums = [
                    'toString',
                    'toLocaleString',
                    'valueOf',
                    'hasOwnProperty',
                    'isPrototypeOf',
                    'propertyIsEnumerable',
                    'constructor'
                ],
                dontEnumsLength = dontEnums.length;

            return function(obj) {
                if (typeof obj !== 'object' && (typeof obj !== 'function' || obj === null)) {
                    throw new TypeError('Object.keys called on non-object');
                }

                var result = [], prop, i;

                for (prop in obj) {
                    if (hasOwnProperty.call(obj, prop)) {
                        result.push(prop);
                    }
                }

                if (hasDontEnumBug) {
                    for (i = 0; i < dontEnumsLength; i++) {
                        if (hasOwnProperty.call(obj, dontEnums[i])) {
                            result.push(dontEnums[i]);
                        }
                    }
                }
                return result;
            };
        }());
    }

    /**
     * Registry class
     *
     * @submodule registry
     * @class Registry
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
         * Usage:
         * <pre>
         * Con.Registry.set('my_key', 'my_value');
         * </pre>
         *
         * @method set
         * @param {String} key
         * @param {*} value
         */
        set: function(key, value) {
            this._instances[key] = value;
        },

        /**
         * Usage:
         * <pre>
         * var data = Con.Registry.get('my_key');
         * </pre>
         *
         * @method get
         * @param {String} key
         * @return {*} The value or NULL
         */
        get: function(key) {
            if ('undefined' === $.type(this._instances[key])) {
                // Con.log('Registry.get: No entry is registered for key ' +
                // key, NAME, 'warn');
                return null;
            }
            return this._instances[key];
        },

        /**
         * Usage:
         * <pre>
         * if (Con.Registry.isRegistered('my_key')) {
         *     // do something here...
         * }
         * </pre>
         *
         * @method isRegistered
         * @param {String} key
         * @return {Boolean}
         */
        isRegistered: function(key) {
            return ('undefined' === $.type(this._instances[key]));
        },

        /**
         * Usage:
         * <pre>
         * Con.Registry.remove('my_key');
         * </pre>
         *
         * @method remove
         * @param {String} key
         */
        remove: function(key) {
            delete this._instances[key];
        }
    };

})(Con, Con.$);

// ############################################################################

(function(Con, $) {

    var NAME = 'loader';

    /**
     * Asset (script and style) loader class. Supports loading of one or
     * multiple assets (css and/or js files at once).
     *
     * @submodule base-loader
     * @class Loader
     * @static
     */

    /**
     * @const STATE_LOADING
     * @type {Number}
     * @private
     */
    var STATE_LOADING = 1;

    /**
     * @const STATE_LOADED
     * @type {Number}
     * @private
     */
    var STATE_LOADED = 2;

    /**
     * Reference to head HTML element
     *
     * @property _head
     * @type {HTMLElement}
     * @private
     */
    var _head = $('head')[0];

    /**
     * Map of loaded files, keeps the state of all files and their loaded state.
     *
     * @property _cache
     * @type {Object}
     * @private
     */
    var _cache = {};

    /**
     * Stack to process for each Loader.get() call.
     *
     * @property _queue
     * @type {Array.<Object>}
     * @private
     */
    var _queue = [];

    /**
     * Delay for evaluation of loaded scripts.
     *
     * @property _lateEvalDelay
     * @type {Number}
     * @private
     */
    var _lateEvalDelay = 250;

    /**
     * Load file
     *
     * @method _loadFile
     * @param {String} file
     * @private
     */
    function _loadFile(file) {
        if (typeof _cache[file] === 'undefined') {
            _cache[file] = STATE_LOADING;

            if (file.search(/\.css\b/) > 0) {
                _loadCss(file, function () {
                    _cache[file] = STATE_LOADED;
                    _processQueue();
                })
            } else {
                _loadJs(file, function () {
                    _cache[file] = STATE_LOADED;
                    _processQueue();
                })
            }
        }
    }

    /**
     * Loads CSS file by appending a link node to the head
     *
     * @method _loadCss
     * @param {String} file
     * @param {Function} callback
     * @private
     */
    function _loadCss(file, callback) {
        var link = document.createElement('link');
        link.href = file;
        link.rel = 'stylesheet';
        link.type = 'text/css';
        _head.appendChild(link);
        callback();
    }

    /**
     * Tries to evaluate JavaScript with a timeout so that
     * dependencies can be loaded correctly.
     * This function will try 3 times with 250ms in between
     * before it gives up
     *
     * @method _lateEval
     * @param {String} fileContent JS to be evaluated
     * @param {Function} callback Callback after successful eval
     * @param {Number} tries Number of times the eval has failed already
     * @param {String} fileName Name of the file (for debugging)
     * @param {jqXHR} jqXHR jQuery XHR Object of the request (for debugging)
     * @param {Object} settings Settings Object (for debugging)
     * @private
     */
    function _lateEval(fileContent, callback, tries, fileName, jqXHR, settings) {
        try {
            eval(fileContent);
        } catch(err) {
            if (tries >= 3) {
                Con.log("failed 3 times for " + fileName, NAME);
                Con.log(jqXHR, NAME);
                Con.log(settings, NAME);
                Con.log(err, NAME);
            } else {
                setTimeout(function() {
                    _lateEval(fileContent, callback, tries + 1, fileName, jqXHR, settings);
                }, _lateEvalDelay);
            }
            return;
        }
        callback();
    }

    /**
     * Loads JavaScript file by using $.getScript
     *
     * @method _loadJs
     * @param {String} file
     * @param {Function} callback
     * @private
     */
    function _loadJs(file, callback) {
        // We use $.ajax instead $.getScript, since we want to cache the file!
        $.ajax({
            dataType: 'script',
            cache: true,
            url: file
        }).done(function () {
            callback();
        }).fail(function (jqXHR, settings, exception) {
            if (jqXHR.status === "200" && jqXHR.responseText !== "") {
                // Give other files a little bit of time to load in case there are dependencies
                // Try to evaluate the file after 250ms
                setTimeout(function () {
                    _lateEval(jqXHR.responseText, callback, 1, file, jqXHR, settings);
                }, _lateEvalDelay);
            } else {
                Con.log('fail ' + file, NAME);
                Con.log(jqXHR, NAME);
                Con.log(settings, NAME);
                Con.log(exception, NAME);
            }
        });
    }

    /**
     * Check queue on file load.
     * Loops through the queue entries and loads the next file, if not loaded before.
     * If all files from one queue entry have been loaded the queue entry will be removed
     * from the queue stack and its callback will be called.
     *
     * @method _processQueue
     * @private
     */
    function _processQueue() {
        var toCall = [];

        _queue = _queue.filter(function (item) {
            var state = _getState(item.files);

            if (state.loaded.length === item.files.length) {
                toCall.push(item);
                return false;
            }

            // Load next
            if (state.load.length) {
                _loadFile(state.load.shift());
            }

            return true;
        });

        // Invoke functions to call
        for (var i = 0; i < toCall.length; i++) {
            toCall[i].callback.apply(toCall[i].scope, toCall[i].params);
        }
    }

    /**
     * Add callback to queue
     *
     * @method _addToQueue
     * @param {Array.<String>} files
     * @param {Function} [callback]
     * @param {Object} [scope]
     * @param {Array} [params]
     * @return {Boolean}
     * @static
     */
    function _addToQueue(files, callback, scope, params) {
        _queue.push({
            files: files,
            callback: callback,
            scope: scope,
            params: params
        });
    }

    /**
     * Get current loading state for callback files
     *
     * @method _getState
     * @param {Array.<String>} files
     * @returns {{load: Array, loaded: Array, loading: Array}}
     * @private
     */
    function _getState(files) {
        var out = {
            load: [],
            loaded: [],
            loading: []
        };

        for (var i = 0; i < files.length; i++) {
            var f = files[i];

            if (typeof _cache[f] === 'undefined') {
                out.load.push(f);
            } else if (_cache[f] === STATE_LOADED) {
                out.loaded.push(f);
            } else if (_cache[f] === STATE_LOADING) {
                out.loading.push(f);
            }
        }

        return out;
    }

    /**
     * Add callback to queue
     *
     * @method _push
     * @param {Array.<String>} files
     * @param {Function} [callback]
     * @param {Object} [scope]
     * @param {Array} [params]
     * @return {Boolean}
     * @static
     */
    function _push(files, callback, scope, params) {
        _addToQueue(files, callback, scope, params);
        _processQueue();
    }

    Con.Loader = {
        /**
         * Loads one or more JS- and or CSS files and invokes the given callback
         * function when the files have been loaded successfully. The callback
         * should be a function which is called with in the given scope and
         * params. Example:
         *
         * <pre>
         * // Loading of 4 files at once
         * Con.Loader.get(['path/to/file.js', 'path/to/file2.js', 'path/to/file.css',
         *         'path/to/file2.css'], function() {
         *     // To do when everything was loaded...
         *     });
         * </pre>
         *
         * @method get
         * @param {String|String[]}  files  One or more files (JS or CSS) to load
         * @param {Function} [callback=function(){}]  Callback to call after the files
         *            where loaded which is called with the given params and the
         *            given scope
         * @param {Object} [scope=this]  The scope in which the callback function
         *            should be called
         * @param {Array} [params=[]]  Array of params to pass to the callback
         *            function
         * @return {Boolean}
         * @static
         */
        get: function(files, callback, scope, params) {
            callback = ('undefined' === $.type(callback)) ? function() {} : callback;
            scope = ('undefined' === $.type(scope)) ? this : scope;
            params = ('undefined' === $.type(params)) ? [] : params;

            if ('array' !== $.type(files)) {
                files = [files];
            }

            _push(files, callback, scope, params);
        }
    };

    window.conLoadFile = Con.Loader.get;

})(Con, Con.$);

// ############################################################################

(function(Con, $, scope) {

    var NAME = 'frame-left-top';

    /**
     * FrameLeftTop class
     *
     * @submodule base-frame-left-top
     * @class FrameLeftTop
     * @static
     */

    /**
     * @property $_container
     * @type {HTMLElement[]}
     * @private
     */
    var $_container = null;

    Con.FrameLeftTop = {
        /**
         * Resize top left frame. Retrieves the container element
         * top_left_container and it's data attributes to handle the resize.
         * Following attributes are supported: - data-resizegap: (Number) The
         * amount of extar pixels to add to the detected content height of top
         * left frame - data-resizeinitcb: (String) Optional a callback to call
         * which does the initial frame resizing Example:
         *
         * <pre>
         * // Resize left top fame, add additional height of 10 pixel
         * Con.FrameLeftTop.resize({
         *     resizegap : 10
         * });
         * </pre>
         *
         * @method resize
         * @param {Object} [options={}] Additional options for resizing as follows.
         * <pre>
         * options.initial  (Boolean)  Flag for initial call of resize, e. on document ready
         * options.resizegap  (Number)  The resize gab passed manually.
         * </pre>
         */
        resize: function(options) {
            var opt = $.extend({
                initial: false,
                resizegap: null
            }, options || {});

            var $container = Con.FrameLeftTop._getContainer(), callback, gap;

            if (!$container[0]) {
                Con
                        .log("resize: Couldn't get container element!", NAME,
                                'warn');
                return;
            }

            if (opt.initial) {
                // Check for data-resizeinitcb for initial resizing
                callback = ($container.data('resizeinitcb'))
                    ? $container.data('resizeinitcb')
                    : null;
            }

            if ('number' === $.type(opt.resizegap)) {
                gap = opt.resizegap;
            } else {
                gap = (false === isNaN($container.data('resizegap')))
                    ? $container.data('resizegap')
                    : 0;
            }

            if (callback && 'function' === $.type(scope[callback])) {
                scope[callback]();
            } else {
                Con.getFrame('content')
                    .frameResize
                    .resizeTopLeftFrame($container.height() + gap);
            }
        },

        /**
         * @method _getContainer
         * @return {HTMLElement[]}
         * @private
         */
        _getContainer: function() {
            if (null === $_container) {
                $_container = $('#top_left_container', Con.getFrame('left_top').document);
            }
            return $_container;
        }
    };

})(Con, Con.$, window);

// ############################################################################

(function(Con, $, scope) {

    var NAME = 'util-url';

    /**
     * URL utility class
     *
     * @submodule base-util-url
     * @class UtilUrl
     * @static
     */
    Con.UtilUrl = {
        /**
         * Builds a CONTENIDO backend url, adds also the contenido parameter to
         * it, if it's not passed with params. Example:
         *
         * <pre>
         * // result: main.php?area=con&amp;action=new&amp;frame=4&amp;contenido=123434
         * var url = Con.UtilUrl.build(&quot;main.php&quot;, {
         *     area : 'con',
         *     action : 'new',
         *     frame : 4
         * });
         * </pre>
         *
         * @method build
         * @param {String} page
         * @param {Object} [params={}]
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
         * Returns protocol + hostname + path (without filename & query string)
         * from a given url. Example:
         *
         * <pre>
         * var url = 'http://hostname/some/path/page.html?foobar=1&amp;user=JaneDoe';
         * // result: 'http://hostname/some/path/'
         * var newUrl = Con.UtilUrl.getUrlWithPath(url);
         * </pre>
         *
         * @method getUrlWithPath
         * @param {String} [url] Url to determine params from, uses
         *            window.location.href by default
         * @return {String} The folder starting like 'http://hostname/some/path/'
         * @static
         */
        getUrlWithPath: function(url) {
            url = url || scope.location.href;
            return decodeURI(url.substring(0, (url.lastIndexOf('/', url.indexOf('?')) + 1)));
        },

        /**
         * Extracts all query parameters from a given url and returns them back.
         * Example:
         *
         * <pre>
         * // result: {foobar: '1', user: 'JaneDoe'}
         * var params = Con.UtilUrl.getParams('/page.html?foobar=1&amp;user=JaneDoe');
         * </pre>
         *
         * @method getParams
         * @param {String} [url] Url to determine params from, uses
         *            window.location.href by default
         * @return {Object}
         * @static
         */
        getParams: function(url) {
            url = url || scope.location.href;

            var params = {}, parts = url.split('?');

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
         * Adds parameters to the url, overwrites existing parameters or removes
         * them from the url. Examples:
         *
         * <pre>
         * var url = 'page.html?foobar=1';
         * // Add parameter 'user', result: 'page.html?foobar=1&amp;user=JaneDoe'
         * var newUrl = Con.UtilUrl.getUrlWithPath(url, {
         *     user : 'JaneDoe'
         * });
         *
         * var url = 'page.html?foobar=1&amp;user=JaneDoe';
         * // Remove parameter 'user', result: 'page.html?foobar=1'
         * var newUrl = Con.UtilUrl.getUrlWithPath(url, {
         *     user : null
         * });
         * </pre>
         *
         * @method replaceParams
         * @param {String} url  The url to change the query parameters
         * @param {Object} params  Key value pairs of params to update or remove. NB:
         *            A null value will remove the parameter! e. g. {action:
         *            null} will remove existing action parameter.
         * @return {String}
         * @static
         */
        replaceParams: function(url, params) {
            var parts = url.split('?'), paramsOrg = Con.UtilUrl.getParams(url), query = '';

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
         * Returns true if the parameter seems to be a valid URL. Example:
         *
         * <pre>
         * var url = 'http://hostname/some/path/page.html?foobar=1&amp;user=JaneDoe';
         * if (Con.UtilUrl.validate(url)) {
         *     // do something here...
         * }
         * </pre>
         *
         * @method validate
         * @param {String} value  The string which will be checked
         * @return {Boolean} True if value is a URL
         * @static
         */
        validate: function(value) {
            var urlregex = /(http:\/\/www.|https:\/\/www.|www.|http:\/\/|https:\/\/){1}([0-9A-Za-z]+\.)|(localhost)/;
            return urlregex.test(value);

        }
    };

})(Con, Con.$, window);

// ############################################################################

(function(Con, $) {

    /**
     * Miscellaneous/common functions, extends Con.
     *
     * @class Common
     * @extends Contenido
     * @module contenido
     * @submodule base-common
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
     *
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
     *            optional amount of arguments used pairwise for assigning URLs
     *            to frame names in CONTENIDO. The last argument is optional but
     *            must (!) be 'simpleFrame' if used to specify that the complete
     *            frame structure is not available.
     */

    Con.multiLink = function() {
        // get last argument
        var tmp = arguments[arguments.length - 1];
        // check by last argument if reduced frame structure is used
        var simpleFrame = tmp === 'simpleFrame';
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
                // fix for IE11 popup:
                // selecting link with tiny out of popup does not work with
                // Con.getFrame in IE11
                // reverting to the old call solves this problem
                // Con.getFrame(f);
                if (f.substr(0, 4) === 'left') {
                    frame = parent.parent.frames["left"].frames[f];
                } else {
                    frame = parent.parent.frames["right"].frames[f];
                }

                if (frame) {
                    frame.location.href = l;
                }
            }
        }
    };

    /**
     * Returns the registry object, from top.header or top frame. Example:
     *
     * <pre>
     * var registry = Con.getRegistry();
     * </pre>
     *
     * @method getRegistry
     * @return {Registry|Null}
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
     * Determines the window in which all the content is being displayed and
     * returns it. Example:
     *
     * <pre>
     * var win = Con.getContentWindow();
     * </pre>
     *
     * @method getContentWindow
     * @return {Window} The window object in which all content is being
     *         displayed
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
     * have already been loaded. Example:
     *
     * <pre>
     * Con.getTranslations(function() {
     *     // translations a loaded, continue with your task here...
     *     });
     * </pre>
     *
     * @method getTranslations
     * @param {Function} [callback] The callback function to call after retrieving
     *            translations
     * @param {Object} [context]
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
                        if (Con.checkAjaxResponse(data) === false) {
                            return false;
                        }

                        registry.set('translations', data);
                        callback.call(context, data);
                    },
                    error: function() {
                        callback.call(context, null);
                        Con.log('getTranslations: Could not get translations',
                                'general.js', 'error');
                    }
                });
            }
        }

        callback.call(context, registry.get('translations'));
    };

    /**
     * Shows a confirmation box with the help of jQuery UI Dialog. Example:
     *
     * <pre>
     * Con.showConfirmation('The description', function() {
     *     // user clicked on 'ok', do the action here ...
     *     });
     * </pre>
     *
     * @method showConfirmation
     * @param {String} description  The text which is displayed in the dialog
     * @param {Function} callback  A callback function which is called if the user
     *            confirmed
     * @param {Object} additionalOptions  Options which can be used to customise the
     *            behaviour of the dialog box
     */
    Con.showConfirmation = function(description, callback, additionalOptions) {
        // Get the translations so that we can use them
        Con.getTranslations(function(translations) {
            if (null === translations) {
                // Use fallback
                translations = TRANSLATIONS;
            }

            // Define the options and extend them with the given ones
            var contentWindow = Con.getContentWindow(), buttons = {};

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
                close: function () {
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

                contentWindow
                    .$('<div id="single_dialog">' + description + '</div>')
                    .dialog(options);
            }

        }, this);
    };

    /**
     * Shows a notification box with the help of jQuery UI Dialog. Example:
     *
     * <pre>
     * Con.showNotification('The title', 'Some description');
     * </pre>
     *
     * @method showNotification
     * @param {String} title  The title of the box
     * @param {String} description  The text which is displayed in the box
     * @param {Object} additionalOptions  Options which can be used to customise the
     *            behaviour of the dialog box, see
     *            http://api.jqueryui.com/dialog/
     * @param {Boolean} hideButtons
     */
    Con.showNotification = function(title, description, additionalOptions,
            hideButtons) {
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
                    // unfortunately, the following line does not work if the
                    // dialog is opened from another frame
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
            var contentWindow = Con.getContentWindow();

            contentWindow.$('html').find('div.ui-dialog').remove();
            contentWindow.$('html').find('div.ui-widget-overlay').remove();

            contentWindow.$('<div>' + description + '</div>').dialog(options);

        }, this);
    };

    /**
    * Check Ajax response and located user to login page
    * if authentication failed (e. g. user timeout)
    *
    * @method checkAjaxResponse
    * @param {String|Object} response
    * @return {Boolean}
    */
    Con.checkAjaxResponse = function(response) {

        if (typeof response === 'string' && response.indexOf('authentication_failure') > -1) {

            var json = $.parseJSON(response);

            if (json !== null && json.state === "error" && json.code === 401) {
                window.location.href = 'index.php';
                return false;
            }
        } else {
            return true;
        }
    };

    /**
     * Marks submenu item in header, handles also context of different frames.
     * It supports to mark a submenu (aka subnav) item by it's position and also
     * by it's data-name attribute value. Examples:
     *
     * <pre>
     * // Mark second submenu item (index starts at 0)
     * Con.markSubmenuItem('c_1');
     *
     * // Mark submenu item by it's data-name attribute, e. g. data-name=&quot;con_editart&quot;
     * Con.markSubmenuItem('con_editart');
     * </pre>
     *
     * @method markSubmenuItem
     * @param {String} subMenu  The position of submenu or data-name value
     * @return {Boolean}
     */
    Con.markSubmenuItem = function(subMenu) {
        var frame = Con.getFrame('right_top'), selector, menuItem;

        if (frame) {
            if (0 === subMenu.search('c_')) {
                selector = "#" + subMenu + " a:first";
            } else {
                selector = "#navlist [data-name='" + subMenu + "'] a:first";
            }

            menuItem = $(selector, frame.document)[0];
            if (menuItem) {
                frame.Con.Subnav.clicked(menuItem, true);
                return true;
            }
        }

        return false;
    };

})(Con, Con.$);
