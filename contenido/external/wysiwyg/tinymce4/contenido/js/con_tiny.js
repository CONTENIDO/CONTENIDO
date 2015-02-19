/* global Con: true, tinymce: true */

/**
 * Integration of TinyMCE to handle it as an insight-editor
 *
 * @module     tiny
 * @version    SVN Revision $Rev$
 * @requires   jQuery, Con
 * @package    CONTENIDO Backend includes
 * @author     Timo Trautmann
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.9
 */

(function(Con, $) {
//    'use strict';

    var NAME = 'tiny';

    /**
     * Edit content form reference
     * @property _form
     * @type {HTMLElement}
     * @private
     */
    var $_form = null;

    /**
     * Data field in form
     * @property _dataField
     * @type {HTMLElement}
     * @private
     */
    var $_dataField = null;

    /**
     * TinyMCE editor handler
     * @class  Tiny
     * @static
     */
    Con.Tiny = {

        /**
         * Id of div on which tiny is active
         * @property activeId
         * @type {String|NULL}
         */
        activeId: null,
        /**
         * Object of div on which tiny is active
         * @property activeObject
         * @type {HTMLElement|NULL}
         */
        activeObject: null,
        /**
         * Object to store edited content
         * @property editData
         * @type {Object}
         */
        editData: {},
        /**
         * Object to store to store original content (Importent for decision if content has changed)
         * @property editDataOrg
         * @type {Object}
         */
        editDataOrg: {},
        /**
         * Flag to ask the user to store changes when the window
         * @property checkOnLeave
         * @type {Boolean}
         */
        checkOnLeave: true,
        /**
         * Tiny settings, used during swap
         * @property settings
         * @type {Object}
         */
        tinySettings: {},
        /**
         * Filebrowser popup field name
         * @property fbFieldName
         * @type {String}
         */
        fbFieldName: null,
        /**
         * Filebrowser popup window
         * @property fbPopupWindow
         * @type {Window}
         */
        fbPopupWindow: null,
        /**
         * Filebrowser popup interval handle
         * @property fbIntervalHandle
         * @type {Numeric}
         */
        fbIntervalHandle: null,
        /**
         * Filebrowser popup window
         * @property fbWindow
         * @type {Window}
         */
        fbWindow: null,
        /**
         * Url to CONTENIDO file browser
         * @property fileUrl
         * @type {String}
         */
        fileUrl: '',
        /**
         * Url to CONTENIDO image browser
         * @property imageUrl
         * @type {String}
         */
        imageUrl: '',
        /**
         * Url to CONTENIDO media browser
         * @property mediaUrl
         * @type {String}
         */
        mediaUrl: '',
        /**
         * Url to current clients frontend
         * @property frontendPath
         * @type {String}
         */
        frontendPath: '',
        /**
         * Confirmation text to save
         * @property txtQuestion
         * @type {String}
         */
        txtQuestion: '',
        /**
         * Whether typing changes for each editor are added using AddUndo to editor for
         * @property typingUndo
         * @type {Array}
         */
        typingUndo : [],
        /**
         * The current undo level for each editor
         * @property undoLvl
         * @type {Array}
         */
        undoLvl : [],
        /**
         * Idartlang which is currently edited
         * @property idartlang
         * @type {Number}
         */
        idartlang: 0,
        /**
         * @property is editor currently changing fullscreen
         * @type {Boolean}
         */
        changingFullscreen: false,

        /**
         * Initializer
         * @method init
         * @param {Object}  options  Several options to initialize static Con.Tiny object
         * @static
         */
        init: function(options) {
            $.each(options, function(key, value) {
                Con.Tiny[key] = value;
            });
            // Get reference to editcontent form
            $_form = $('form[name="editcontent"]');
            $_dataField = $_form.find('[name="data"]');
        },

        /**
         * Custom content setup callback function for TinyMCE, see TinyMCE event
         * 'LoadContent'. It is called after the editor initialised.
         * When retrieving the content w/ getContent(), cleaned up content will be returned
         * that eventually will be reinserted into the editor.
         * 
         * @see http://www.tinymce.com/wiki.php/api4:event.tinymce.Editor.LoadContent
         * @method customSetupContentCallback
         * @param  {String}  editorId
         * @static
         */
        customSetupContentCallback: function(editorId) {
            var cleanContent = tinymce.get(editorId).getContent();
            tinymce.get(editorId).setContent(cleanContent);
        },

        /**
         * Custom cleanup callback function for TinyMCE
         * Converts a given content string (callbacks registered in tinymceInit)
         * @method customCleanupCallback
         * @param  {String}  value  String of content
         * @return  {String}  Converted content
         * @static
         */
        customCleanupCallback: function(value) {
            // Remove xhtml styled tags
            value = value.replace(/[\s]*\/>/g,'>');

            return value;
        },

        /**
         * Custom file browser callback function for TinyMCE, see TinyMCE setting
         * 'file_browser_callback'.
         * Opens CONTENIDO file browser in popup.
         *
         * @method customFileBrowserCallback
         * @param {String} fieldName  Name of relevant HTML field
         * @param {String} url  Tiny default but not used in function
         * @param {String} type  Type of content to add (image, file, ..)
         * @param {Window} win  Corresponding window object
         * @static
         */
        customFileBrowserCallback: function(fieldName, url, type, win) {
            switch (type) {
                case 'image':
                    Con.Tiny.fbPopupWindow = window.open(Con.Tiny.imageUrl, 'filebrowser', 'dialog=yes,resizable=yes');
                    Con.Tiny.fbFieldName = fieldName;
                    Con.Tiny.fbWindow = win;
                    Con.Tiny.fbIntervalHandle = window.setInterval(function() {
                        Con.Tiny.updateImageFilebrowser();
                    }, 250);
                    break;
                case 'file':
                    Con.Tiny.fbPopupWindow = window.open(Con.Tiny.fileUrl, 'filebrowser', 'dialog=yes,resizable=yes');
                    Con.Tiny.fbFieldName = fieldName;
                    Con.Tiny.fbWindow = win;
                    Con.Tiny.fbIntervalHandle = window.setInterval(function() {
                        Con.Tiny.updateImageFilebrowser();
                    }, 250);
                    break;
                case 'media':
                    Con.Tiny.fbPopupWindow = window.open(Con.Tiny.mediaUrl, 'filebrowser', 'dialog=yes,resizable=yes');
                    Con.Tiny.fbFieldName = fieldName;
                    Con.Tiny.fbWindow = win;
                    Con.Tiny.fbIntervalHandle = window.setInterval(function() {
                        Con.Tiny.updateImageFilebrowser();
                    }, 250);
                    break;
                default:
                    alert(type);
                    break;
            }
        },

        /**
         * Custom save callback function for TinyMCE, see TinyMCE event
         * 'SaveContent'.
         * @method customSaveCallback
         * @param  {String}  html
         * @return {String}
         * @static
         */
        customSaveCallback: function(html) {
            return html.replace(Con.Tiny.frontendPath, '');
        },

        /**
         * Custom url converter callback function for TinyMCE, see TinyMCE setting
         * 'urlconverter_callback'.
         * http://www.tinymce.com/wiki.php/Configuration3x:urlconverter_callback
         * NOTE: This function does nothing but return the input url back at the moment.
         * @method customURLConverterCallback
         *
         * @param unknown ...
         * @static
         */
        customURLConverterCallback: function(url) {
            // could be implemented if needed
            return url;
        },

        /**
         * Callback function for tiny which gets a selected image in CONTENIDO
         * image browser, close browser and set this selected image in tiny
         * @method updateImageFilebrowser
         * @static
         */
        updateImageFilebrowser: function() {
            // Check for popup window Error handling
            if (!Con.Tiny.fbPopupWindow.left) {
                return;
            } else if (!Con.Tiny.fbPopupWindow.left.left_top) {
                return;
            } else if (!Con.Tiny.fbPopupWindow.left.left_top.document.getElementById('selectedfile')) {
                return;
            }

            var leftTopFrame = Con.Tiny.fbPopupWindow.left.left_top;

            if (leftTopFrame.document.getElementById('selectedfile').value !== '') {
                // Get selected image from popup and close it
                Con.Tiny.fbWindow.document.getElementById(Con.Tiny.fbFieldName).value = leftTopFrame.document.getElementById('selectedfile').value;

                Con.Tiny.fbPopupWindow.close();
                window.clearInterval(Con.Tiny.fbIntervalHandle);
                // Set this selected image in tiny
                if (Con.Tiny.fbWindow.ImageDialog && "function" === $.type(Con.Tiny.fbWindow.ImageDialog.showPreviewImage)) {
                    Con.Tiny.fbWindow.ImageDialog.showPreviewImage(Con.Tiny.fbWindow.document.forms[0].elements[Con.Tiny.fbFieldName].value);
                }
            }
        },

        /**
         * Function stores content of current opened tiny into property editData
         * this content is later stored by submitting setcontent()
         * @method storeCurrentTinyContent
         * @static
         */
        storeCurrentTinyContent: function() {
            // Store last tiny changes if tiny is still open
            var editor = tinymce.get(Con.Tiny.activeId);

            if (editor) {
                var content = editor.getContent();
                content = content.replace(Con.Tiny.frontendPath, '');
                Con.Tiny.editData[Con.Tiny.activeId] = content;
            }
        },

        /**
         * Function gets all content stored in editData and sends it as string to server
         * for storage it into database
         *
         * @method setContent
         * @param  {Number}  idartlang - idartlang of article which is currently edited
         * @param  {String}  [action=''] - actionurl of form (optional)
         * @static
         */
        setContent: function(idartlang, action) {
            action = action || '';

            // Do not ask user for storage
            Con.Tiny.checkOnLeave = false;

            // Check if there is still a tiny open and get its content
            Con.Tiny.storeCurrentTinyContent();

            var str = '';

            // Foreach content in js object editData
            $.each(Con.Tiny.editData, function(id, val) {
                // Check if content has changed, if it has serialize it to string
                if (Con.Tiny.editDataOrg[id] != val) {
                    // data[0] = fieldname, data[1] = idtype, data[2] = typeid
                    var data = id.split('_');
                    // Build the string which will be send
                    str += Con.Tiny.buildDataEntry(idartlang , data[0] , data[2] , Con.Tiny.prepareString(val));
                }
            });

            // Set the string
            $_dataField.val(str + $_dataField.val());

            // Set the action, but check for invalid values
            if (action !== 0 && action !== '' && action !== '0') {
                $_form.attr('action', action);
            }

            // Submit the form
            $_form.submit();
        },

        /**
         * Function escapes chars in content for inserting into submit string.
         * An empty content &nbsp; is replaced by %$%EMPTY%$%
         * | were seperators in string and were replaced by %$%SEPERATOR%$%
         *
         * @method prepareString
         * @param {String} str  Content string which should be escaped
         * @return {String}  String with escaped chars
         * @static
         */
        prepareString: function(str) {
            if (str === '&nbsp;' || str === '') {
                str = '%$%EMPTY%$%';
            } else {
                // if there is an | in the text set a replacement chr because we use it later as isolator
                while (str.search(/\|/) != -1) {
                    str = str.replace(/\|/, '%$%SEPERATOR%$%');
                }
            }

            return str;
        },

        /**
         * Function serializes given args to string and return it. Seperator is |
         *
         * @method buildDataEntry
         * @param {Number} idartlang  Idartlang of article which is currently edited
         * @param {String} type  Type name of content (CMS_HTML)
         * @param {Number} typeid  Id of content (CMS_HTML[4] => 4)
         * @param {String} value  Value of content
         * @return {String}  Serialized vars
         * @static
         */
        buildDataEntry: function(idartlang, type, typeid, value) {
            return idartlang + '|' + type + '|' + typeid + '|' + value + '||';
        },

        /**
         * Function adds a custom content type to submit strings, adds all other content
         * information and submits it to server using setContent()
         *
         * @method addDataEntry
         * @param {Number} idartlang  Idartlang of article which is currently edited
         * @param {String} type  Type name of content (CMS_HTML)
         * @param {Number} typeid  Id of content (CMS_HTML[4] => 4)
         * @param {String} value  Value of content
         * @static
         */
        addDataEntry: function(idartlang, type, typeid, value) {
            $_dataField.val(
                Con.Tiny.buildDataEntry(idartlang, type, typeid, Con.Tiny.prepareString(value))
            );
            Con.Tiny.setContent(idartlang);
        },

        /**
         * Function closes currently opened tiny
         * @method closeTiny
         * @static
         */
        closeTiny: function() {
            //check if tiny is currently open
            if (Con.Tiny.activeId && tinymce.get(Con.Tiny.activeId)) {
                //save current tiny content to js var
                Con.Tiny.storeCurrentTinyContent();

                //if content was empty set div height. Empty divs were ignored by most browsers
                if (Con.Tiny.editData[Con.Tiny.activeId] === '') {
                    //document.getElementById(Con.Tiny.activeId).style.height = '15px';
                }

                //close current open tiny and set active vars to null
                var tmpId = Con.Tiny.activeId;
                setTimeout(function() {
                    if (tmpId) {
                        // use jQuery to deselect any element with the id (thus also deselecting the editor)
                        jQuery('#' + tmpId).blur();
                    }
                }, 0);

                Con.Tiny.activeId = null;
                Con.Tiny.activeObject = null;
            }
        },

        /**
         * Function swaps tiny to a content editable div. If tiny is already open on
         * another div, this tiny was swapped to current div by closing it first
         * tiny swaps on click
         *
         * @method swapTiny
         * @param {HTMLElement} obj - div object which was clicked
         * @static
         */
        swapTiny: function(obj) {
            // adjust currently active id
            Con.Tiny.activeId = obj.id;
            Con.Tiny.activeObject = obj;
        },

        /**
         * Function like storeCurrentTinyContent() which stores original content to
         * property editDataOrg for a later decision if content has changed
         *
         * @method updateContent
         * @param {String} sContent - original content string
         * @static
         */
        updateContent: function(sContent, iEditorId) {
            // do nothing if tinymce instance gets rebuilt because of fullscreen change
            if (Con.Tiny.changingFullscreen) {
                return;
            }
            // If original content was already set do not overwrite
            // this happens if tiny is reopened on same content
            if ('undefined' === typeof(Con.Tiny.editDataOrg[iEditorId])) {
                sContent = sContent.replace(Con.Tiny.frontendPath, '');
                Con.Tiny.editDataOrg[iEditorId] = sContent;
            }
        },

        /**
         * Function checks if content has changed if user leaves page.
         * Then he has the possiblity to save this content. So there is no
         * guess, that changes get lost.
         * @method leaveCheck
         * @static
         */
        leaveCheck: function() {
            // If tiny is still open store its content
            Con.Tiny.storeCurrentTinyContent();

            // The property checkOnLeave is false when user clicks save button.
            // This is also a case in which he leaves this page but by pressing
            // save button he also saves all changes
            if (false === Con.Tiny.checkOnLeave) {
                // There is no need to perform the check...
                return;
            }

            // Check if any content in editData was changed
            var hasChanges = false;
            jQuery.each(Con.Tiny.editData, function(id, val) {
                if (Con.Tiny.editDataOrg[id] !== val) {
                    hasChanges = true;
                    return false; // break the loop by return false
                }
            });

            // return true if changes are found
            if (hasChanges) {
                return true;
            }
        },

        /**
         * Converts contents of objects recursively to json, if possible
         * @method convertToJson
         * @param {Object} obj  The object that contains content to be changed to JSON
         * @static
         */
        convertToJson: function(obj) {
            // convert wysiwygSetting's properties into json
            for (prop in obj) {
                // check if property is not a prototype of object
                if (false === obj.hasOwnProperty(prop)) {
                    // no property of object
                    continue;
                }
                // iterate through sub-object
                if ('object' === typeof(obj[prop]) && null !== obj[prop]) {
                    Con.Tiny.convertToJson(obj[prop]);
                    continue;
                }

                try {
                    obj[prop] = JSON.parse(obj[prop]);
                } catch (e) {
                    // prop is not valid json
                    continue;
                }
            }
        },

        /**
         * Initializes the TinyMCE instance, creates/registers addititional plugins and assigns
         * given wysiwygSettings to the TinyMCE instance.
         * @method tinymceInit
         * @param {Object} tinymce  The current TinyMCE instance
         * @param {Object} wysiwygSettings  Editor settins to assign to TinyMCE instance
         * @param {Object} options  Common options
         * @static
         */
        tinymceInit: function(tinymce, wysiwygSettings, options) {
            // abort tinymce init if tiny should not be used
            if ('undefined' === typeof(options)
            || ('undefined' !== typeof(options.useTiny)
            && '' === options.useTiny)) {
                return;
            }

            // create plugins for tinymce first as they do not relate to wysiwygSettings

            // Create ClosePlugin
            tinymce.create('tinymce.plugins.ClosePlugin', {
                init: function(ed, url) {
                    ed.addButton('save', {
                        title: options.saveTitle,
                        image: options.saveImage,
                        icons: false,
                        onclick: function() {
                            ed.fire('SaveContent');
                            Con.Tiny.setContent(Con.Tiny.idartlang);
                        }
                    });

                    ed.addButton('close', {
                        title: options.closeTitle,
                        image: options.closeImage,
                        icons: false,
                        onclick: function(ed) {
                            Con.Tiny.closeTiny();
                        }
                    });
                }
            });

            // Register plugin with a short name
            tinymce.PluginManager.add('close', tinymce.plugins.ClosePlugin);

            tinymce.create('tinymce.plugins.ConFullscreenPlugin', {
                init: function(ed) {
                    ed.addButton('fullscreen', {
                        tooltip: 'Fullscreen',
                        shortcut: 'Ctrl+Alt+F',
                        onclick: function() {
                            Con.Tiny.handleFullscreen(ed);
                        }
                    });
                }
            });
            // Register plugin with a short name
            tinymce.PluginManager.add('confullscreen', tinymce.plugins.ConFullscreenPlugin);

            // iterate through wysiwygSettings array and process its content
            Object.keys(wysiwygSettings).forEach(function(val, idx) {
            	// create copy of object to avoid side effects
            	var settings = [wysiwygSettings[val]].slice(0,1)[0];

                // convert all passed data to json format for configuration of tinymce 4
                Con.Tiny.convertToJson(settings);
                if ('undefined' === typeof(settings.fullscreen_settings)) {
                	settings.fullscreen_settings = {};
                }
                // load contenido plugins
                var contenidoPluginFolderUrl = options.backendUrl + 'external/wysiwyg/tinymce4/contenido/plugins/';
                var contenidoPlugins = [{'name': 'conabbr', 'path': contenidoPluginFolderUrl + 'con_abbr/plugin.js'}];
                contenidoPlugins.forEach(function(plugin) {
                    // load current add-on
                    // http://www.tinymce.com/wiki.php/api4:method.tinymce.AddOnManager.load
                    tinymce.PluginManager.load(plugin.name, plugin.path);
                    
                    if ('undefined' === typeof(settings.plugins)) {
                    	settings.plugins = "";
                    }
                    if ('undefined' === typeof(settings.fullscreen_settings.plugins)) {
                    	settings.fullscreen_settings.plugins = "";
                    }
                    // exclude plugin from later loading
                    settings.plugins += (' -' + plugin.name);
                    settings.fullscreen_settings.plugins += (' -' + plugin.name);
                });

                if ('undefined' === typeof(settings['file_browser_callback'])) {
                	settings['file_browser_callback'] = 
                        function(field_name, url, type, win) {
                            Con.Tiny.customFileBrowserCallback(field_name, url, type, win);
                    }
                }

                // check which plugins should be loaded
                if ('undefined' !== typeof(settings.externalplugins)) {
                    // check if setting is an array
                    // Array.isArray() can not be used because IE 8 does not implement it
                    if ('[object Array]' === Object.prototype.toString.call(settings.externalplugins)) {
                    	settings.externalplugins.forEach(function (plugin) {
                            // load current add-on
                            // http://www.tinymce.com/wiki.php/api4:method.tinymce.AddOnManager.load
                            tinymce.PluginManager.load(plugin.name, plugin.url);
                            // exclude plugin from later loading
                            settings.plugins += (' -' + plugin.name);
                            settings.fullscreen_settings.plugins += (' -' + plugin.name);
                        });
                    }
                }


                // inject setup into settings
                settings.setup = function(ed) {
                    ed.on('init', function() {
                    	// init into manager
                        ed.undoManager.data = [];
                        ed.undoManager.data.push({"content": ed.getContent({format: 'raw', no_events: 1})});
                    });
                    ed.on('undo', function(lvl) {
                        // make sure not to change undo history while fullscreen switching is in progress
                        if (false !== Con.Tiny.changingFullscreen) {
                            return;
                        }
                        if ('undefined' === typeof(Con.Tiny.undoLvl[ed.id])) {
                            Con.Tiny.undoLvl[ed.id] = ed.undoManager.data.length -2;
                            return;
                        }
                        Con.Tiny.undoLvl[ed.id] -= 1;
                    });
                    ed.on('redo', function(e) {
                        if ('undefined' === typeof(Con.Tiny.undoLvl[ed.id])) {
                            Con.Tiny.undoLvl[ed.id] = 0;
                            return;
                        }
                        Con.Tiny.undoLvl[ed.id] += 1;
                    });

                    // Fires after an undo level has been added to the editor.
                    // http://www.tinymce.com/wiki.php/api4:event.tinymce.Editor.AddUndo
                    ed.on('AddUndo', function(e) {
                        if (false !== Con.Tiny.changingFullscreen) {
                            return;
                        }
                        Con.Tiny.typingUndo[ed.id] = false;
                        if ('undefined' === typeof(Con.Tiny.undoLvl[ed.id])) {
                            Con.Tiny.undoLvl[ed.id] = ed.undoManager.data.length -1;
                            return;
                        }
                        Con.Tiny.undoLvl[ed.id] += 1;
                        ed.undoManager.data.push(e.level);
                    });
                    // Fires when content is changed in editor through typing but AddUndo has not been fired yet
                    ed.on('TypingUndo', function(e) {
                        if (false !== Con.Tiny.changingFullscreen) {
                            return;
                        }
                        // if we are not at latest undo level remove any further undo level later than at current position

                        // build a new data array to avoid side effects
                        var tmp = [];
                        jQuery.each(ed.undoManager.data, function(idx, k) {
                            if (idx < parseInt(Con.Tiny.undoLvl[ed.id]) + 1) {
                                tmp.push(k);
                            }
                        });
                        
                        ed.undoManager.data = tmp;
                        Con.Tiny.typingUndo[ed.id] = true;
                    });
                    // Fires before the contents is processed.
                    // http://www.tinymce.com/wiki.php/api4:event.tinymce.Editor.PreProcess
                    ed.on('PreProcess', function(ev) {
                        // ignore dirty state fullscreen state
                        if (false === Con.Tiny.changingFullscreen) {
                            // pre-process content before it gets inserted into editor
                        }
                            ev.node.innerHTML = Con.Tiny.customCleanupCallback(ev.node.innerHTML);
                    });
                    // Fires after the contents has been processed.
                    // http://www.tinymce.com/wiki.php/api4:event.tinymce.Editor.PostProcess
                    ed.on('PostProcess', function(ev) {
                        // ignore dirty state fullscreen state
                        if (false === Con.Tiny.changingFullscreen) {
                            // post-process content before it gets saved
                            ev.content = Con.Tiny.customCleanupCallback(ev.content);
                        }
                    });
                    // Fires after contents has been loaded into the editor.
                    // http://www.tinymce.com/wiki.php/api4:event.tinymce.Editor.LoadContent
                    ed.on('LoadContent', function(e) {
                        // dirty state fullscreen state is over when content is loaded
                        if (Con.Tiny.changingFullscreen) {
                            //Con.Tiny.changingFullscreen = false;
                            return;
                        }
                        Con.Tiny.customSetupContentCallback(ed.id);
                        // set variable with original content of editor for later comparision
                        // e.g. to check if content changed
                        Con.Tiny.updateContent(ed.getContent(), ed.id);
                    });
                    // Fires after contents has been saved/extracted from the editor.
                    // http://www.tinymce.com/wiki.php/api4:event.tinymce.Editor.SaveContent
                    ed.on('SaveContent', function (e) {
                        // ignore dirty state fullscreen state
                        if (false === Con.Tiny.changingFullscreen) {
                            Con.Tiny.customSaveCallback(ed.getContent());
                        }
                    });
                    ed.on('blur', function (e) {
                        Con.Tiny.storeCurrentTinyContent();
                    });
                }
                if ('undefined' !== typeof(settings.fullscreen_settings)) {
                	settings.fullscreen_settings.setup = settings.setup;
                	settings.fullscreen_settings['file_browser_callback'] = settings['file_browser_callback'];
                	settings.fullscreen_settings['valid_elements'] = settings['valid_elements'];
                }

                // register custom save command for save plugin
                // check if used in non-fullscreen mode
                if (settings.plugins.split(" ").indexOf("save") > -1) {
                    // use callback documented in
                    // http://www.tinymce.com/wiki.php/Plugin:save
                	settings.save_onsavecallback = function() {
                        Con.Tiny.setContent(Con.Tiny.idartlang);
                    };
                }
                // check if used in fullscreen mode
                if (settings.fullscreen_settings.plugins.split(" ").indexOf("save") > -1) {
                    // use callback documented in
                    // http://www.tinymce.com/wiki.php/Plugin:save
                	settings.fullscreen_settings.save_onsavecallback = function() {
                        Con.Tiny.setContent(Con.Tiny.idartlang);
                    };
                }
                
                jQuery(document).ready(function() {
                tinymce.init(settings);
                // copy settings into global variable for later access
                Con.Tiny.tinySettings[jQuery(settings.selector).attr("id")] = settings;
                });
            });
            

//            var tinyCmsHtmlHead = wysiwygSettings.slice(1, 2)[0];
//            tinyCmsHtmlHead.setup = tinymce.settings.setup;
//
//            // do not loose reference to tinymce settings
//            var set = tinymce.settings
//            tinymce.init(tinyCmsHtmlHead);
//            tinymce.settings = set;
//
//            tinymce.init(tinymce.settings);
        },

        handleFullscreen: function(ed) {
            // fullscreen in inline mode not supported
            // we can not change inline mode of existing editor
            // remove old editor instance and create a new one
            var id = ed.id;
            Con.Tiny.changingFullscreen = true;
            Con.Tiny.replacingEditor = true;

            // add undo step if user typed and no AddUndo event fired
            if (true === Con.Tiny.typingUndo[ed.id]) {
                Con.Tiny.typingUndo[ed.id] = false;
                Con.Tiny.undoLvl[ed.id]++;
                ed.undoManager.data.push({"content": ed.getContent({format: 'raw', no_events: 1})});
            }
            ed.remove();

            var undoData = ed.undoManager.data;

            // build a new editor instance with fullscreen_settings
            var set =  Con.Tiny.tinySettings[ed.id];

            ed = new tinymce.Editor(id, set.fullscreen_settings, tinymce.EditorManager);
            // fullscreen editor is removed when switching back to inline mode
            ed.on('remove', function () {
                // save current content to variable to be able to decide if its contents changed
                Con.Tiny.storeCurrentTinyContent();
            });

            // fullscreen editor initialised
            ed.on('init', function () {
                // put new editor into focus
                ed.fire('focus');
                // set new editor to fullscreen mode
                ed.execCommand('mceFullScreen');

                // clear undo history of current editor
                ed.undoManager.data = [];
                while (ed.undoManager.data.length) { ed.undoManager.data.pop(); }
                // Removes all undo levels
                ed.undoManager.clear();
                // replay undo history from old editor instance
                undoData.forEach( function (val, idx) {
                    // set the editor content to the content of editor during this undo history entry
                    ed.setContent(val.content);
                    // add the undo level to undo manager
                    var lvl = ed.undoManager.add(val);
                    if (null !== lvl) {
                        ed.undoManager.data.push(lvl);
                    }
                });

                // go back n steps in undo history
                // where n is the difference of undo level amount and current undo level
                // result: we go back until we reach the user selected undo level
                var n = (undoData.length -1) - Con.Tiny.undoLvl[ed.id];
                for (var i = 0; i < n; i++) {
                    ed.undoManager.undo();
                }
                // we are done changing fullscreen mode
                Con.Tiny.changingFullscreen = false;
            });

            // destroy fullscreen instance and build new instance with original settings
            ed.on('FullscreenStateChanged', function () {
                if (Con.Tiny.replacingEditor) {
                    Con.Tiny.replacingEditor = false;
                    return;
                }
                Con.Tiny.changingFullscreen = true;
                var id = ed.id;

                // add undo step if user typed and no AddUndo event fired
                if (true === Con.Tiny.typingUndo[ed.id]) {
                    Con.Tiny.typingUndo[ed.id] = false;
                    Con.Tiny.undoLvl[ed.id]++;
                    ed.undoManager.data.push({"content": ed.getContent({format: 'raw', no_events: 1})});
                }
                ed.remove();

                var undoData = ed.undoManager.data;

                // build a new editor instance with original settings
                ed = new tinymce.Editor(id, set, tinymce.EditorManager);
                ed.on('init', function () {
                    // put new editor into focus
                    ed.fire('focus');

                    // clear undo history of current editor
                    ed.undoManager.data = [];
                    ed.undoManager.clear();
                    // replay undo history from old editor instance
                    undoData.forEach(function(val, idx) {
                        // set the editor content to the content of editor during this undo history entry
                        ed.setContent(val.content);
                        // add the undo level to undo manager
                        var lvl = ed.undoManager.add(val);
                        if (null !== lvl) {
                            ed.undoManager.data.push(lvl);
                        }
                    });

                    // go back n steps in undo history
                    // where n is the difference of undo level amount and current undo level
                    // result: we go back until we reach the user selected undo level
                    var n = (undoData.length -1) - Con.Tiny.undoLvl[ed.id];
                    for (var i = 0; i < n; i++) {
                        ed.undoManager.undo();
                    }

                    // we are done changing fullscreen mode
                    Con.Tiny.changingFullscreen = false;

                });
                // add new editor to page
                ed.render();
            });

            // add new editor to page
            ed.render();
        },

        /**
         * Binds click actions on contenteditable elements and registers also a handler to check for
         * nonsaved content on page unload.
         * @method bindEvents
         * @param {Object} options  Options like
         * <pre>
         * - options.useTiny  (Boolean)  Flag to use (swap) tiny
         * </pre>
         * @static
         */
        bindEvents: function(options) {
            // Add tiny to elements which contains classname contentEditable
            // tiny toggles on click
            $('div[contenteditable=true]').each(function() {
                $(this).attr('contentEditable', 'false'); //remove contentEditable tags in order to disable special firefox behaviour
                $(this).on('click', function(e) {
                    if (options.useTiny) {
                        Con.Tiny.swapTiny(this);
                    }
                });
            });

            // Activate save confirmation on page leave
            jQuery(window).on('beforeunload ',function() {
                if (true === Con.Tiny.leaveCheck()) {
                    return Con.Tiny.txtQuestion;
                }
            });
        }
    };
})(Con, Con.$);
