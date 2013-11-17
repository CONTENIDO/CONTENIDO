/* global Con: true, jQuery: true */

/**
 * This file contains the cContentTypeAbstractTabbed JS class.
 *
 * @module  content-type
 * @submodule  content-type-cms-abstract-tabbed
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel, Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
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
         *
         * @type String
         */
        this.frameId = frameId;

        /**
         * ID of the edit image.
         *
         * @type String
         */
        this.imageId = imageId;

        /**
         * The HTTP path to the CONTENIDO backend.
         *
         * @type String
         */
        this.pathBackend = pathBackend;

        /**
         * The HTTP path to the CONTENIDO frontend.
         *
         * @type String
         */
        this.pathFrontend = pathFrontend;

        /**
         * IdArtLang of the article which is currently in edit- or viewmode.
         *
         * @type Number
         */
        this.idArtLang = idArtLang;

        /**
         * ID of the content type, e.g. 3 if CMS_TEASER[3] is used.
         *
         * @type Number
         */
        this.id = id;

        /**
         * Array of form field names which are used by this content type.
         *
         * @type Array
         */
        this.fields = fields;

        /**
         * The prefix of this content type.
         *
         * @type String
         */
        this.prefix = prefix;

        /**
         * The CONTENIDO session.
         *
         * @type String
         */
        this.session = session;

        /**
         * The old settings.
         *
         * @type Object|String
         */
        this.settings = settings;

    }

    /**
     * Initialises the content type by adding event handlers etc.
     */
    cContentTypeAbstractTabbed.prototype.initialise = function() {
        // append the whole frame to the end of the body for better positioning
        $(this.frameId).appendTo($('body'));
        this.loadExternalFiles();
        this.addFrameShowEvent();
        this.addTabbingEvents();
        this.addSaveEvent();
        this.addFrameCloseEvents();
    };

    /**
     * Loads external styles and scripts so that they are only loaded when they are
     * really needed.
     */
    cContentTypeAbstractTabbed.prototype.loadExternalFiles = function() {
        if ($('#cms_abstract_tabbed_styles').length === 0) {
            $('head').append('<link rel="stylesheet" id="cms_abstract_tabbed_styles" href="' + this.pathBackend + 'styles/content_types/cms_abstract_tabbed.css" type="text/css" media="all" />');
        }
        conLoadFile(this.pathBackend + 'scripts/jquery/jquery-ui.js', cContentTypeAbstractTabbed.prototype.jQueryUiCallback, this);
    };

    /**
     * Callback function which is executed when jQuery UI has successfully been
     * loaded. Makes the frames draggable.
     */
    cContentTypeAbstractTabbed.prototype.jQueryUiCallback = function() {
        $(this.frameId).draggable({handle: '.head'});
        $(this.frameId + ' .head').css('cursor', 'move');
    };

    /**
     * Adds the given name/value pair as a hidden field to the editform so that it
     * is submitted to CONTENIDO. If a hidden field with the given name already
     * exists, the value is overriden.
     *
     * @param {String} name The name of the form field which should be added.
     * @param {String} value The value of the form field which should be added.
     */
    cContentTypeAbstractTabbed.prototype.appendFormField = function(name, value) {
        // if a hidden input field with the given name already exists, just set the value
        if ($('form[name="editcontent"] input[type="hidden"][name="' + name + '"]').length > 0) {
            $('form[name="editcontent"] input[type="hidden"][name="' + name + '"]').val(value);
        } else {
            // otherwise append a new field to the form
            $('form[name="editcontent"]').append('<input type="hidden" value="' + value + '" name="' + name + '"/>');
        }
    };

    /**
     * Adds event which fades in the edit form when editbutton is clicked.
     */
    cContentTypeAbstractTabbed.prototype.addFrameShowEvent = function() {
        var self = this;
        $(self.imageId).css('cursor', 'pointer');
        $(self.imageId).click(function() {
             var top = $(document).scrollTop()+($(window).height()/2);
            $(self.frameId).fadeIn('normal');
            $(self.frameId).css('position', 'absolute');
            $(self.frameId).css('top', top);
            $(self.frameId).css('left', '50%');
        });
    };

    /**
     * Adds tabbing events to menubar of content type edit form. Lets the user
     * switch between the different tabs.
     */
    cContentTypeAbstractTabbed.prototype.addTabbingEvents = function() {
        var self = this;
        // add layer click events
        $(self.frameId + ' .menu li').click(function() {
            $(self.frameId + ' .menu li').removeClass('active');
            // hide all tabs but the active one
            $(self.frameId + ' .tabs > div:not(#' + $(this).attr('class') + ')').hide();
            // add smooth animation
            $(self.frameId + ' #' + $(this).attr('class')).fadeIn('normal');
            $(this).addClass('active');
        });
        // trigger the click event on the first tab so that the others are hidden etc.
        $(self.frameId + ' .menu li:first').click();
    };

    /**
     * Adds save event to the save button of content type edit form.
     */
    cContentTypeAbstractTabbed.prototype.addSaveEvent = function() {
        var self = this;
        $(self.frameId + ' .save_settings').css('cursor', 'pointer');
        $(self.frameId + ' .save_settings').click(function() {
            for (var i = 0; i < self.fields.length; i++) {
                var value = '';
                var name = self.fields[i] + '_' + self.id;
                if ($(self.frameId + ' #' + name).is('input[type="checkbox"]')) {
                    // special behaviour for checkboxes
                    value = $(self.frameId + ' #' + name).prop('checked');
                } else if ($(self.frameId + ' #' + name).is('select')) {
                    // in case of select field implode the options, use ',' as separator
                    $(self.frameId + ' #' + name + ' option:selected').each(function() {
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
                    value = $(self.frameId + ' #' + name).val();
                }
                name = name.replace('_' + self.id, '');
                self.appendFormField(name, value);
            }
            self.appendFormField(self.prefix + '_action', 'store');
            self.appendFormField(self.prefix + '_id', self.id);
            setcontent(self.idArtLang);
        });
    };

    /**
     * Adds event for closing content type edit window.
     */
    cContentTypeAbstractTabbed.prototype.addFrameCloseEvents = function() {
        var self = this;
        // add cancel image event
        $(self.frameId + ' .close').css('cursor', 'pointer');
        $(self.frameId + ' .close').click(function() {
            $(self.frameId).fadeOut('normal');
        });

        // add cancel button event
        $(self.frameId + ' .cancel_settings').css('cursor', 'pointer');
        $(self.frameId + ' .cancel_settings').click(function() {
            $(self.frameId).fadeOut('normal');
        });
    };


    Con.cContentTypeAbstractTabbed = cContentTypeAbstractTabbed;

    // @deprecated [2013-11-10] Assign to windows scope (downwards compatibility)
    window.cContentTypeAbstractTabbed = cContentTypeAbstractTabbed;

})(Con, Con.$);
