/**
 * This file contains general functions which are potentially helpful for every
 * backend page. The file should therefore be included in every backend page.
 */


// #################################################################################################


/**
 * Registry class
 *
 * @class  ContenidoRegistry
 * @static
 */
ContenidoRegistry = {
    _instances: [],
    set: function(key, value) {
        this._instances[key] = value;
    },
    get: function(key) {
        if (this._instances[key] === "undefined") {
            throw("No entry is registered for key '" + key + "'");
        }
        return this._instances[key];
    },
    isRegistered: function(key) {
        return (this._instances[key] == "undefined");
    },
    remove: function(key) {
        this._instances = this._instances.splice(this._instances.indexOf(key), 1);
    }
};


// #################################################################################################


$(function() {
    // get the translations once, so that they are already loaded
    getTranslations();
});

/**
 * Section for script loader
 */

var loaded = new Object();
var stack = new Object();

/**
 * Evaluates the given callbacks.
 *
 * @param array callbacks - array of callbacks. A callback is either a simple string
 *             which can be evaluated or an object with callback, scope and params
 *             properties.
 */
function conEvaluateCallbacks(callbacks) {
    $.each(callbacks, function(index, value) {
        if (typeof value === 'object') {
            // object callback, call it with the appropriate scope
            value['callback'].apply(value['scope'], value['params']);
        } else if (typeof value === 'string') {
            // simple callback, just evaluate it
            eval(value);
        }
    });
}

/**
 * Loads the given script and evaluates the given callback function
 * when the script has been loaded successfully. The callback can be
 * a simple string which is evaluated or a function which is called with
 * the given scope and params.
 *
 * @param {String} script - the path to the script which should be loaded
 * @param {String|Function} callback - code which should be evaluated
 *             after the script has been loaded or a callback function
 *             which is called with the given params and the given scope
 * @param {Object} scope - the scope with which the callback function should be called
 * @param {Array} params - array of params which should be passed to the callback function
 * @returns {Boolean}
 */
function conLoadFile(script, callback, scope, params) {
    if (!callback) {
        callback = '';
    }

    if (params === undefined) {
        params = [];
    }

    // check if callback has to be called on the scope object
    var isObjectCallback = (typeof callback === 'function' && typeof scope === 'object');

    // only load the script if it has not been loaded yet
    if (loaded[script] != 'true') {
        // initialise callback stack
        if (stack[script] == undefined) {
            stack[script] = Array();
        }

        // push new entry onto the callback stack depending on the callback type
        if (isObjectCallback) {
            var newCallback = new Object();
            newCallback['callback'] = callback;
            newCallback['scope'] = scope;
            newCallback['params'] = params;
            stack[script].push(newCallback);
        } else {
            stack[script].push(callback);
        }

        // if script is not already loading, load it and evaluate the callbacks
        if (loaded[script] != 'pending') {
            loaded[script] = 'pending';
            $.getScript(script)
            .done(function() {
                loaded[script] = 'true';
                conEvaluateCallbacks(stack[script]);
            })
            .fail(function(jqxhr, settings, exception) {
                //console.log('failed to load ' + script);
                //console.log(jqxhr);
                //console.log(settings);
                //console.log(exception);
            });
        }
    } else {
        // script is already loaded, so just evaluate the callback
        if (isObjectCallback) {
            callback.apply(scope, params);
        } else {
            eval(callback);
        }
    }

    return true;
}

/**
 * Javascript Multilink Example: <code>
 *    conMultiLink (
 *         "frame",
 *        "link",
 *         "frame",
 *        "link",
 *         ...,
 *        "simpleFrame"
 *    )
 * </code>
 *
 * @param [arguments*]
 *            optional amount of arguments used pairwise for assigning URLs to
 *            frame names in CONTENIDO. The last argument is optional but must
 *            (!) be "simpleFrame" if used to specify that the complete frame
 *            structure is not available.
 * @return void
 */
function conMultiLink() {
    // get last argument
    var tmp = arguments[arguments.length - 1];
    // check by last argument if reduced frame structure is used
    var simpleFrame = (tmp == "simpleFrame") ? true : false;
    // change for-loop counter if last parameter is used to identify simple
    // frame multilinks
    var len = (simpleFrame) ? arguments.length - 1 : arguments.length;

    for (var i = 0; i < len; i += 2) {
        f = arguments[i];
        l = arguments[i + 1];

        if (f == "left_bottom" || f == "left_top") {
            parent.parent.frames["left"].frames[f].location.href = l;
        } else {
            if (simpleFrame) { // use simple frame
                parent.frames[f].location.href = l;
            } else { // use classic multilink structure
                parent.parent.frames["right"].frames[f].location.href = l;
            }
        }
    }
}

function getRegistry() {
    if (window.top.header) {
        return window.top.header.ContenidoRegistry;
    }
    return window.top.ContenidoRegistry;
}

/**
 * Determines the window in which all the content is being displayed and returns
 * it.
 *
 * @returns the window object in which all content is being displayed
 */
function getContentWindow() {
    if (!window.top.content) {
        return window;
    }
    if (typeof window.top.content.right !== 'undefined') {
        return window.top.content.right.right_bottom;
    }
    return window.top.content.right_bottom;
}

/**
 * Returns an URLs params as array.
 *
 * @param {String} url to determine params from
 * @returns array
 */
function getUrlParams(url) {
    var params = [];
    var parts = url.split('?');
    if (1 < parts.length) {
        var queryString = parts[1];
        var queryString = queryString.split('&');
        for (var i in queryString) {
            var query = queryString[i].split('=');
            params[query[0]] = query[1];
        }
    }
    return params;
}

/**
 * Loads the translations from the server once and just returns them if they
 * have already been loaded.
 *
 * @returns {Object}
 */
function getTranslations() {

    var registry = getRegistry();

    if (!registry) {
        setTimeout(function() {

            getTranslations();

        }, 50);

        return;
    }

    // if the translations have not been loaded yet, do it now
    if (typeof registry.get('translations') === 'undefined') {
        // get param 'contenido'
        var params = getUrlParams(window.location.search);
        $.ajax({
            async: false,
            url: 'ajaxmain.php',
            data: 'ajax=generaljstranslations&contenido=' + params['contenido'],
            dataType: 'json',
            success: function(data) {
                registry.set('translations', data);
            },
            error: function(data) {
                alert('could not get translations');
            }
        });
    }

    return registry.get('translations');
}

/**
 * Shows a confirmation box with the help of jQuery UI Dialog.
 *
 * @param  {String}  description  The text which is displayed in the dialog
 * @param  {Function}  callback  A callback function which is called if the user confirmed
 * @param  {Object}  additionalOptions  Options which can be used to customise the behaviour
 *            of the dialog box
 */
function showConfirmation(description, callback, additionalOptions) {
    // get the translations so that we can use them
    var translations = getTranslations();
    // define the options and extend them with the given ones
    var contentWindow = getContentWindow();
    var buttons = {};
    buttons[translations['OK']] = function() {
        if (typeof callback === 'function') {
            callback();
        }
        contentWindow.$('#single_dialog').dialog('close');
    };
    buttons[translations['Cancel']] = function() {
        contentWindow.$('#single_dialog').dialog('close');
    };
    var options = {
        modal: true,
        buttons: buttons,
        position: ['center', 50],
        resizable: false,
        close: function(event, ui) {
            contentWindow.$("html").find("#single_dialog").remove();
        },
        title: translations['Confirmation Required']
    };
    options = $.extend(options, additionalOptions);
    // show the dialog in the content window
    if (0 == contentWindow.$("html").find("#single_dialog").length) {
        contentWindow.$("html").find("div.ui-dialog").remove();
        contentWindow.$("html").find("div.ui-widget-overlay").remove();
        contentWindow.$("html").find("#single_dialog").remove();

        contentWindow.$('<div id="single_dialog">' + description + '</div>').dialog(options);
    }

}

/**
 * Shows a notification box with the help of jQuery UI Dialog.
 *
 * @param  {String}  title The title of the box
 * @param  {String}  description  The text which is displayed in the box
 * @param  {Object}  additionalOptions  Options which can be used to customise the
 *           behaviour of the dialog box
 */
function showNotification(title, description, additionalOptions, hideButtons) {
    // get the translations so that we can use them
    var translations = getTranslations();
    // define the options and extend them with the given ones
    var buttons = {};
    if (!hideButtons) {
        buttons[translations['OK']] = function() {
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

    contentWindow.$("html").find("div.ui-dialog").remove();
    contentWindow.$("html").find("div.ui-widget-overlay").remove();

    contentWindow.$('<div>' + description + '</div>').dialog(options);
}


/**
 * Marks submenu item in header, handles also context of different frames
 * @param {String} id  The id of submenu
 */
function conMarkSubmenuItem(id) {
    var menuItem;
    try {
        // Check if we are in a dual-frame or a quad-frame
        if (parent.parent.frames[0].name == "header") {
            if ($("#" + id, parent.frames["right_top"].document)) {
                menuItem = $("#" + id + " a:first", parent.frames["right_top"].document).get(0);
                if (menuItem) {
                    parent.frames["right_top"].sub.clicked(menuItem);
                }
            }
        } else {
            // Check if submenuItem is existing and mark it
            if ($("#" + id, parent.parent.frames["right"].frames["right_top"].document)) {
                menuItem = $("#" + id + " a:first", parent.parent.frames["right"].frames["right_top"].document).get(0);
                if (menuItem) {
                    parent.parent.frames["right"].frames["right_top"].sub.clicked(menuItem);
                }
            }
        }
    } catch (e) {
    }
}


/**
 * Returns true if the parameter is a valid URL
 *
 * @param {String} value The string which will be checked
 * @returns {Boolean} True if value is a URL
 */
function validateURL(value) {
    var urlregex = /(http:\/\/www.|https:\/\/www.|www.|http:\/\/|https:\/\/){1}(([0-9A-Za-z]+\.))|(localhost)/
    if (urlregex.test(value)) {
        return true;
    }
    return false;
}

// Assigns the tooltip to backend info boxes
$(document).ready(function() {
    $("a.i-link").each(function() {
        var id = $(this).attr("id").substring(0, $(this).attr("id").indexOf("-link"));
        $(this).aToolTip({
            clickIt:    true,
            xOffset:    -20,
            yOffset:    4,
            outSpeed:   250,
            tipContent: $("#" + id).html()
        });
    });
});
