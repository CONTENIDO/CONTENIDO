/* global Con: true, jQuery: true */

/**
 * This file contains the cContentTypeDate JS class.
 *
 * @module     content-type
 * @requires   jQuery, Con, Con.Base64
 * @submodule  content-type-cms-date
 * @package    Core
 * @subpackage Content Type
 * @author     Simon Sprankel
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

(function(Con, $) {
//    'use strict';

    var NAME = 'content-type-cms-date';

    /**
     * @class cContentTypeDate
     * @constructor
     * @property {String} frameId The ID of the frame in which the content type can be set up.
     * @property {String} prefix The prefix of the content type.
     * @property {Number} id The ID of the content type, e.g. 3 if CMS_TEASER[3] is used.
     * @property {Number} idArtLang The idArtLang of the article which is currently being edited.
     * @property {String} pathBackend The path to the CONTENIDO backend.
     * @property {String} lang The language which is used (de or en).
     * @property {Object|String} settings The settings of this content type.
     * @property {String} belang The backend language (e.g. de_DE).
     */
    function cContentTypeDate(frameId, prefix, id, idArtLang, pathBackend, lang, settings, belang) {

        /**
         * Reference to cms type node
         * @property $frame
         * @type {jQuery}
         */
        this.$frame = $(frameId);

        /**
         * ID of the frame in which all settings are made.
         * @property frameId
         * @type {String}
         */
        this.frameId = frameId;

        /**
         * The prefix of this content type.
         * @property prefix
         * @type {String}
         */
        this.prefix = prefix;

        /**
         * ID of the content type, e.g. 3 if CMS_TEASER[3] is used.
         * @property id
         * @type {Number}
         */
        this.id = id;

        /**
         * IdArtLang of the article which is currently in edit- or viewmode.
         * @property idArtLang
         * @type {Number}
         */
        this.idArtLang = idArtLang;

        /**
         * The HTTP path to the CONTENIDO backend.
         * @property pathBackend
         * @type {String}
         */
        this.pathBackend = pathBackend;

        /**
         * The language which should be used.
         * @property lang
         * @type {String}
         */
        this.lang = lang;

        /**
         * The old settings.
         * @property settings
         * @type {Object|String}
         */
        this.settings = settings;

        /**
         * The backend language.
         * @property belang
         * @type {String}
         */
        this.belang = belang;

        /**
         * Reference to the current content type element
         * @property $element
         * @type {HTMLElement[]}
         */
        this.$element = this.$frame.find('.con_element.date_timestamp');

        /**
         * Reference to the current content type element
         * @property $dateFormatSelect
         * @type {HTMLElement[]}
         */
        this.$dateFormatSelect = this.$frame.find('.con_select.date_format_select');

        /**
         * Reference to the save settings button
         * @property $saveSettings
         * @type {HTMLElement[]}
         */
        this.$saveSettings = this.$frame.find('.save_settings');
    }

    /**
     * Initialises the content type by adding event handlers etc.
     * @method initialise
     * @param {String} calendarPic
     */
    cContentTypeDate.prototype.initialise = function(calendarPic) {
        this.loadExternalFiles(calendarPic);
        this.addSaveEvent();
    };

    /**
     * Loads external styles and scripts so that they are only loaded when they are
     * really needed.
     * @method loadExternalFiles
     * @param {String} calendarPic
     */
    cContentTypeDate.prototype.loadExternalFiles = function(calendarPic) {
        // Dependencies to load
        var files = [
            this.pathBackend + 'styles/content_types/cms_date.css',
            this.pathBackend + 'styles/jquery/jquery-ui.css',
            this.pathBackend + 'scripts/jquery/plugins/timepicker.js'
        ];

        // Callback to call after the main dependencies have been loaded, loads additional
        // language files if needed
        var _loadCallback = function() {
            if (this.lang !== 'en') {
                var files = [
                    this.pathBackend + 'scripts/jquery/plugins/datepicker-' + this.lang + '.js',
                    this.pathBackend + 'scripts/jquery/plugins/timepicker-' + this.lang + '.js'
                ];
                Con.Loader.get(files, this.jQueryUiCallback, this, [calendarPic]);
            } else {
                this.jQueryUiCallback(calendarPic);
            }
        };

        // Fist load main dependencies
        Con.Loader.get(files, _loadCallback, this, [calendarPic]);
    };

    /**
     * Callback function which is executed when jQuery UI has successfully been
     * loaded. Loads the appropriate language.
     * @method jQueryUiCallback
     * @param {String} calendarPic
     */
    cContentTypeDate.prototype.jQueryUiCallback = function(calendarPic) {
        this.jQueryUiTimepickerCallback(calendarPic);
    };

    /**
     * Callback function which is executed when jQuery UI has successfully been
     * loaded. Loads the appropriate language.
     * @method jQueryUiTimepickerCallback
     * @param {String} calendarPic
     */
    cContentTypeDate.prototype.jQueryUiTimepickerCallback = function(calendarPic) {
        var self = this;
        // initialise the datepicker
        this.$element.datetimepicker({
            buttonImage: calendarPic,
            buttonImageOnly: true,
            changeYear: true,
            showOn: 'both',
            beforeShow: function () {
                $('body').addClass('cms_has_overlay');
                setTimeout(function(){
                    $('.ui-datepicker').css('z-index', 20000);
                }, 0);
            },
            onClose: function () {
                $('body').removeClass('cms_has_overlay');
                $('.ui-datepicker').css('z-index', 'auto');
            }
        });
        $(function() {
            // set the initial date
            var date = new Date();
            if (!isNaN(self.settings.date_timestamp)) {
                date = new Date(self.settings.date_timestamp * 1000);
            }
            self.$element.datetimepicker('setDate', date);
            // set the format
            var dateFormat = 'yy-mm-dd';
            var timeFormat = 'hh:mm:ssTT';
            if (self.belang == 'de_DE') {
                dateFormat = 'dd.mm.yy';
                timeFormat = 'hh:mm:ss';
            }
            self.$element.datetimepicker('option', 'dateFormat', dateFormat);
            self.$element.datetimepicker('option', 'timeFormat', timeFormat);
        });
    };

    /**
     * Adds save event to the save button of content type edit form.
     * @method addSaveEvent
     */
    cContentTypeDate.prototype.addSaveEvent = function() {
        var self = this;
        this.$saveSettings.css('cursor', 'pointer');
        this.$saveSettings.click(function() {
            var date = self.$element.datetimepicker('getDate') || self.$element.datepicker('getDate') || self.$element.timepicker('getDate');
            var timestamp = Math.floor(date.getTime() / 1000);
            var format = self.$dateFormatSelect.val();
            format = Con.Base64.encode(format);
            self.appendFormField(self.prefix + '_timestamp', timestamp);
            self.appendFormField(self.prefix + '_format', format);
            self.appendFormField(self.prefix + '_action', 'store');
            self.appendFormField(self.prefix + '_id', self.id);
            Con.Tiny.setContent(self.idArtLang);
        });
    };

    /**
     * Adds the given name/value pair as a hidden field to the editform so that it
     * is submitted to CONTENIDO. If a hidden field with the given name already
     * exists, the value is overridden.
     *
     * @method appendFormField
     * @param {String} name The name of the form field which should be added.
     * @param {String} value The value of the form field which should be added.
     */
    cContentTypeDate.prototype.appendFormField = function(name, value) {
        // if a hidden input field with the given name already exists, just set the value
        var $elem = $('form[name="editcontent"] input[type="hidden"][name="' + name + '"]');
        if ($elem.length > 0) {
            $elem.val(value);
        } else {
            // otherwise append a new field to the form
            $('form[name="editcontent"]').append('<input type="hidden" value="' + value + '" name="' + name + '"/>');
        }
    };


    Con.cContentTypeDate = cContentTypeDate;

})(Con, Con.$);
