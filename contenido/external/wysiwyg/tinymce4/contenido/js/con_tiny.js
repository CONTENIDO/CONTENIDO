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
        settings: {},
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
         * Idartlang which is currently edited
         * @property idartlang
         * @type {Number}
         */
        idartlang: 0,
        /**
         * is editor currently changing fullscreen
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
         * Custom content setup callback function for TinyMCE, see TinyMCE setting
         * 'setupcontent_callback'.
         * setup content is fired after the editor initialised
         * @method customSetupContentCallback
         * @param  {String}  editorId
         * @static
         */
        customSetupContentCallback: function(editorId) {
            tinymce.get(editorId).setContent(tinymce.get(editorId).getContent());
        },

        /**
         * Custom cleanup callback function for TinyMCE, see TinyMCE setting
         * 'cleanup_callback'.
         * Converts a given content string (callback of tiny)
         * @method customCleanupCallback
         * @param  {String}  type  Type of content
         * @param  {String}  value  String of content
         * @return  {String}  Converted content
         * @static
         */
        customCleanupCallback: function(type, value) {
            switch (type) {
                case 'get_from_editor':
                case 'insert_to_editor':
                    // Remove xhtml styled tags
                    value = value.replace(/[\s]*\/>/g,'>');
                    break;
            }

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
         * Custom save callback function for TinyMCE, see TinyMCE setting
         * 'save_callback'.
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
         * NOTE: This is not used at the moment.
         * @method customURLConverterCallback
         *
         * @param unknown ...
         * @static
         */
        customURLConverterCallback: function() {
            // could be implemented if needed
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

            // Forach content in js object editData
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
//            // Check if tiny is currently open
//            Con.Tiny.closeTiny();
//
//            // Set tinymce configs
//            tinymce.settings = Con.Tiny.settings;
//
//            // Set clicked object as active object
//            Con.Tiny.activeId = obj.id;
//            Con.Tiny.activeObject = obj;
//
//            // Show tiny and focus it
//            if (Con.Tiny.activeId) {
//
//            	// check if tinymce editor instance for current id already exists
//            	if (!tinymce.get(Con.Tiny.activeId)) {
//            		// build a new instance because there is no editor instance
//	            	var ed = new tinymce.Editor(Con.Tiny.activeId, tinymce.settings, tinymce.EditorManager);
//
//	            	// bind init event to a function
//	            	ed.on('init', function(e) {
//	            		// make sure dom has been built
//	                    window.setTimeout(function () {
//	                    	// fire focus event
//	                    	// ed.focus() does not work because there's an internal tinymce problem
//	                    	ed.fire('focus');
//	                    }, 0);
//	            	});
//	            	// inject newly built editor into page
//	            	ed.render();
//	
//	                // Remove height information of clicked div
//	                $('#' + Con.Tiny.activeId).css('height', '');
//            	}
//            }
        },

        /**
         * Function like storeCurrentTinyContent() which stores original content to
         * property editDataOrg for a later decision if content has changed
         *
         * @method updateContent
         * @param {String} sContent - original content string
         * @static
         */
        updateContent: function(sContent) {
            // If original content was already set do not overwrite
            // this happens if tiny is reopened on same content
            if ('undefined' === $.type(Con.Tiny.editDataOrg[Con.Tiny.activeId])) {
                sContent = sContent.replace(Con.Tiny.frontendPath, '');
                Con.Tiny.editDataOrg[Con.Tiny.activeId] = sContent;
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

            // The proerty checkOnLeave is false when user clicks save button.
            // This is also a case in which he leaves this page but by pressing
            // save button he also saves all changes
            if (false === Con.Tiny.checkOnLeave) {
                // There is no need to perform the check...
                return;
            }

            // Check if any content in editData was changed
            var hasChanges = false;
            $.each(Con.Tiny.editData, function(id, val) {
                if (Con.Tiny.editDataOrg[id] !== val) {
                    hasChanges = true;
                    return false; // break the loop by return false
                }
            });

            // If content was changed ask user if he wants to save content.
            if (hasChanges) {
                var check = confirm(Con.Tiny.txtQuestion);
                // If he wants to save content call function setContent();
                if (true === check) {
                    Con.Tiny.setContent(Con.Tiny.idartlang);
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

            if ('undefined' === typeof(wysiwygSettings['file_browser_callback'])) {
                wysiwygSettings['file_browser_callback'] = 
                    function(field_name, url, type, win) {
                        Con.Tiny.customFileBrowserCallback(field_name, url, type, win);
                }
            }

            if ('undefined' !== typeof(options)) {
                // Create ClosePlugin
                tinymce.create('tinymce.plugins.ClosePlugin', {
                    init: function(ed, url) {
                        ed.addButton('save', {
                            title: options.saveTitle,
                            image: options.saveImage,
                            icons: false,
                            onclick: function(ed) {
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
        	}
            tinymce.settings = wysiwygSettings;
            
            // inject setup into settings
            tinymce.settings.setup = function(ed) {
            	
            	ed.on('LoadContent', function(e) {
            		Con.Tiny.customSetupContentCallback(ed.id);
            		Con.Tiny.updateContent(ed.getContent());
                });
            	ed.on('SaveContent', function (e) {
            		Con.Tiny.customSaveCallback(ed.getContent());
            	});
            }
            if ('undefined' !== typeof(tinymce.settings.fullscreen_settings)) {
            	tinymce.settings.fullscreen_settings['file_browser_callback'] = tinymce.settings['file_browser_callback'];
            }
            
            // init set of editors
            tinymce.init(tinymce.settings);            
        },
        
        handleFullscreen: function(ed) {
			// fullscreen in inline mode not supported
			// we can not change inline mode of existing editor
			// remove old editor instance and create a new one
			var id = ed.id;
			ed.remove();
			
			// build a new editor instance with fullscreen_settings
			var set = tinymce.settings;
			ed = new tinymce.Editor(id, set.fullscreen_settings, tinymce.EditorManager);
			ed.on('init', function () {
				// put new editor into focus
				ed.fire('focus');
				changingFullscreen = true;
				ed.execCommand('mceFullScreen');
			});
			ed.on('FullscreenStateChanged', function () {
				if (changingFullscreen) {
					changingFullscreen = false;
					return;
				}
				var id = ed.id;
				ed.remove();
				
				// build a new editor instance with original settings
				ed = new tinymce.Editor(id, set, tinymce.EditorManager);
				ed.on('init', function () {
					// put new editor into focus
					ed.fire('focus');
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
            $(window).unload(function() {
                Con.Tiny.leaveCheck();
            });
        }
    };
})(Con, Con.$);
