/**
 * This file contains general functions which are potentially helpful for every
 * backend page. The file should therefore be included in every backend page.
 */

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
 * Loads the given script and evaluates the given callback function
 * when the script has been loaded successfully. The callback can be
 * a simple string which is evaluated or a function which is called with
 * the given scope and params.
 *
 * @param string script - the path to the script which should be loaded
 * @param string|function callback - code which should be evaluated
 *             after the script has been loaded or a callback function
 *             which is called with the given params and the given scope
 * @param object scope - the scope with which the callback function should be called
 * @param array params - array of params which should be passed to the callback function
 * @returns {Boolean}
 */
function conLoadFile(script, callback, scope, params) {
    if (!callback) {
        callback = '';
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
            $.getScript(script, function() {
                loaded[script] = 'true';
                conEvaluateCallbacks(stack[script]);
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

    for ( var i = 0; i < len; i += 2) {
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

/**
 * @deprecated 2012-09-17 This function does not do anything and should not be
 *             used.
 */
function handleErrors() {

    return true;
}

function getRegistry() {
    return window.top.header.ContenidoRegistry;
}

/**
 * Determines the window in which all the content is being displayed and returns
 * it.
 *
 * @returns the window object in which all content is being displayed
 */
function getContentWindow() {
    if (typeof window.top.content.right !== 'undefined') {
        return window.top.content.right.right_bottom;
    }
    return window.top.content.right_bottom;
}

/**
 * Returns an URLs params as array.
 *
 * @param url to determine params from
 * @returns array
 */
function getUrlParams(url) {
    var params = [];
    var parts = url.split('?');
    if (0 < parts.length) {
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
    // if the translations have not been loaded yet, do it now
    if (typeof registry.get('translations') === 'undefined') {
        // get param 'contenido'
        var params = getUrlParams(window.location.search);
        $.ajax({
            async : false,
            url : 'ajaxmain.php',
            data : 'ajax=generaljstranslations&contenido=' + params['contenido'],
            dataType : 'json',
            success : function(data) {
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
 * @param description
 *            {String} the text which is displayed in the dialog
 * @param callback
 *            a callback function which is called if the user confirmed
 * @param additionalOptions
 *            {Object} options which can be used to customise the behaviour of
 *            the dialog box
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
        // unfortunately, the following line does not work if the dialog is
        // opened from another frame
        // $(this).dialog('close');
        // so use this ugly workaround
        $(this).parent().remove();
    };
    buttons[translations['Cancel']] = function() {
        // unfortunately, the following line does not work if the dialog is
        // opened from another frame
        // $(this).dialog('close');
        // so use this ugly workaround
        $(this).parent().remove();
    };
    var options = {
        buttons : buttons,
        position : ['center', 50],
        resizable: false,
        title : translations['Confirmation Required']
    };
    options = $.extend(options, additionalOptions);
    // show the dialog in the content window
    contentWindow.$('<div>' + description + '</div>').dialog(options);
}

/**
 * Shows a notification box with the help of jQuery UI Dialog.
 *
 * @param title
 *            {String} the title of the box
 * @param description
 *            {String} the text which is displayed in the box
 * @param additionalOptions
 *            {Object} options which can be used to customise the behaviour of
 *            the dialog box
 */
function showNotification(title, description, additionalOptions) {
    // get the translations so that we can use them
    var translations = getTranslations();
    // define the options and extend them with the given ones
    var buttons = {};
    buttons[translations['OK']] = function() {
        // unfortunately, the following line does not work if the dialog is
        // opened from another frame
        // $(this).dialog('close');
        // so use this ugly workaround
        $(this).parent().remove();
    };
    var options = {
        buttons : buttons,
        position : ['center', 50],
        title : title
    };
    options = $.extend(options, additionalOptions);
    // show the dialog in the content window
    var contentWindow = getContentWindow();
    contentWindow.$('<div>' + description + '</div>').dialog(options);
}
