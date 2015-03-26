/* global Con: true, jQuery: true */

/**
 * This file contains the cContentTypeAbstractTabbed JS class.
 *
 * @module     content-type
 * @submodule  content-type-cms-abstract-tabbed
 * @package    Core
 * @subpackage Content Type
 * @version    SVN Revision $Rev$
 * @author     Simon Sprankel, Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {
//    'use strict';

    var NAME = 'content-type-cms-abstract-tabbed';

    /**
     * Creates a new cContentTypeAbstractTabbed with the given properties.
     * You most probably want to call initialise() after creating a new object of this class.
     * @class cContentTypeAbstractTabbed
     * @constructor
     * @property {String} frameId The ID of the frame in which the content type can be set up.
     * @property {String} imageId The ID of the button on which one clicks in order to edit the content type.
     * @property {String} pathBackend The path to the CONTENIDO backend.
     * @property {String} pathFrontend The path to the CONTENIDO frontend.
     * @property {Number} idArtLang The idArtLang of the article which is currently being edited.
     * @property {Number} id The ID of the content type, e.g. 3 if CMS_TEASER[3] is used.
     * @property {Array} fields Form element names which are used by this content type.
     * @property {String} prefix The prefix of the content type.
     * @property {String} session The session ID of the admin user.
     * @property {Object|String} settings The settings of this content type.
     */
    function cContentTypeAbstractTabbed(frameId, imageId, pathBackend, pathFrontend, idArtLang, id, fields, prefix, session, settings) {

        /**
         * ID of the frame in which all settings are made.
         * @property frameId
         * @type {String}
         */
        this.frameId = frameId;

        /**
         * ID of the edit image.
         * @property imageId
         * @type {String}
         */
        this.imageId = imageId;

        /**
         * The HTTP path to the CONTENIDO backend.
         * @property pathBackend
         * @type {String}
         */
        this.pathBackend = pathBackend;

        /**
         * The HTTP path to the CONTENIDO frontend.
         * @property pathFrontend
         * @type {String}
         */
        this.pathFrontend = pathFrontend;

        /**
         * IdArtLang of the article which is currently in edit- or viewmode.
         * @property idArtLang
         * @type {Number}
         */
        this.idArtLang = idArtLang;

        /**
         * ID of the content type, e.g. 3 if CMS_TEASER[3] is used.
         * @property id
         * @type {Number}
         */
        this.id = id;

        /**
         * Array of form field names which are used by this content type.
         * @property fields
         * @type Array
         */
        this.fields = fields;

        /**
         * The prefix of this content type.
         * @property prefix
         * @type {String}
         */
        this.prefix = prefix;

        /**
         * The CONTENIDO session.
         * @property session
         * @type {String}
         */
        this.session = session;

        /**
         * The old settings.
         * @property settings
         * @type {Object|String}
         */
        this.settings = settings;

        /**
         * Reference to tabbed frame node
         * @property $frame
         * @type {HTMLElement[]}
         */
        this.$frame = $(this.frameId);
    }

    /**
     * Initialises the content type by adding event handlers etc.
     * @method initialise
     */
    cContentTypeAbstractTabbed.prototype.initialise = function() {
        // append the whole frame to the end of the body for better positioning
        this.$frame.appendTo($('body'));
        this.loadExternalFiles();
        this.addFrameShowEvent();
        this.addTabbingEvents();
        this.addSaveEvent();
        this.addFrameCloseEvents();
    };

    /**
     * Loads external styles and scripts so that they are only loaded when they are
     * really needed.
     * @method loadExternalFiles
     */
    cContentTypeAbstractTabbed.prototype.loadExternalFiles = function() {
        // Dependencies to load
        var files = [];
        files.push(this.pathBackend + 'styles/content_types/cms_abstract_tabbed.css');
        // jquery-ui.js is already added via $cfg['backend_template']['js_files']
        //files.push(this.pathBackend + 'scripts/jquery/jquery-ui.js');
        
        Con.Loader.get(files, cContentTypeAbstractTabbed.prototype.jQueryUiCallback, this);
    };

    /**
     * Callback function which is executed when jQuery UI has successfully been
     * loaded. Makes the frames draggable.
     * @method jQueryUiCallback
     */
    cContentTypeAbstractTabbed.prototype.jQueryUiCallback = function() {
        this.$frame.draggable({handle: '.head'});
        this.$frame.find('.head').css('cursor', 'move');
    };

    /**
     * Adds the given name/value pair as a hidden field to the editform so that it
     * is submitted to CONTENIDO. If a hidden field with the given name already
     * exists, the value is overriden.
     *
     * @method appendFormField
     * @param {String} name The name of the form field which should be added.
     * @param {String} value The value of the form field which should be added.
     */
    cContentTypeAbstractTabbed.prototype.appendFormField = function(name, value) {
    	// CON-2142
        // jQuery transforms special strings like &auml; to ä during append
        // if a hidden input field with the given name already exists, just set the value
        if ($('form[name="editcontent"] input[type="hidden"][name="' + name + '"]').length > 0) {
            $('form[name="editcontent"] input[type="hidden"][name="' + name + '"]').val(value);
        } else {
            // otherwise append a new field to the form

            $('form[name="editcontent"]').append('<input type="hidden" name="' + name + '"/>');
            $('form[name="editcontent"] input:last-child').attr('value', value);
        }
    };

    /**
     * Adds event which fades in the edit form when editbutton is clicked.
     * @method addFrameShowEvent
     */
    cContentTypeAbstractTabbed.prototype.addFrameShowEvent = function() {
        var self = this;
        $(this.imageId).css('cursor', 'pointer');
        $(this.imageId).click(function() {
            var top = $(document).scrollTop()+($(window).height()/2);
            self.$frame.fadeIn('normal');
            self.$frame.css({
                position: 'absolute',
                top: top,
                left: '50%'
            });
        });
    };

    /**
     * Adds tabbing events to menubar of content type edit form. Lets the user
     * switch between the different tabs.
     * @method addTabbingEvents
     */
    cContentTypeAbstractTabbed.prototype.addTabbingEvents = function() {
        var self = this,
            $items = this.$frame.find('.menu li');
        // add layer click events
        $items.click(function() {
            $items.removeClass('active');
            // hide all tabs but the active one
            self.$frame.find('.tabs > div:not(#' + $(this).attr('class') + ')').hide();
            // add smooth animation
            self.$frame.find('#' + $(this).attr('class')).fadeIn('normal');
            $(this).addClass('active');
        });
        // trigger the click event on the first tab so that the others are hidden etc.
        self.$frame.find('.menu li:first').click();
    };

    /**
     * Adds save event to the save button of content type edit form.
     * @method addSaveEvent
     */
    cContentTypeAbstractTabbed.prototype.addSaveEvent = function() {
        var self = this,
            $elem = this.$frame.find('.save_settings');
        $elem.css('cursor', 'pointer');
        $elem.click(function() {
            for (var i = 0; i < self.fields.length; i++) {
                var value = '',
                    name = self.fields[i] + '_' + self.id,
                    $item = self.$frame.find('#' + name);
                if ($item.is('input[type="checkbox"]')) {
                    // special behaviour for checkboxes
                    value = $item.prop('checked');
                } else if ($item.is('select')) {
                    // in case of select field implode the options, use ',' as separator
                    $item.find('option:selected').each(function() {
                        if (value === '') {
                            value = $(this).val();
                        } else {
                            value += ',' + $(this).val();
                        }
                    });
                    // if multiple options have been selected, add the array prefix to the name
                    if (value.split(',').length > 1) {
                        name = name.replace(self.prefix, self.prefix + '_array');
                    }
                } else {
                    // default value for select boxes and text boxes
                    value = $item.val();
                }
                name = name.replace('_' + self.id, '');
                self.appendFormField(name, value);
            }
            self.appendFormField(self.prefix + '_action', 'store');
            self.appendFormField(self.prefix + '_id', self.id);
            Con.Tiny.setContent(self.idArtLang);
        });
    };

    /**
     * Adds event for closing content type edit window.
     * @method addFrameCloseEvents
     */
    cContentTypeAbstractTabbed.prototype.addFrameCloseEvents = function() {
        var self = this,
            $close = this.$frame.find('.close'),
            $cancel = this.$frame.find('.cancel_settings');

        // add cancel image event
        $close.css('cursor', 'pointer').click(function() {
            self.$frame.fadeOut('normal');
        });

        // add cancel button event
        $cancel.css('cursor', 'pointer').click(function() {
            self.$frame.fadeOut('normal');
        });
    };


    Con.cContentTypeAbstractTabbed = cContentTypeAbstractTabbed;

})(Con, Con.$);
