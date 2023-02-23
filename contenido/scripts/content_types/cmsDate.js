/* global Con: true, jQuery: true */

/**
 * This file contains the cContentTypeDate JS class.
 *
 * @module     content-type
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
        this.$element = $('#date_timestamp_' + this.id);

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
            showOn: 'both'
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
        var self = this,
            $elem = $(this.frameId).find(' .save_settings');
        $elem.css('cursor', 'pointer');
        $elem.click(function() {
            var date = self.$element.datetimepicker('getDate') || self.$element.datepicker('getDate') || self.$element.timepicker('getDate');
            var timestamp = Math.floor(date.getTime() / 1000);
            var format = $(self.frameId + ' #date_format_select_' + self.id).val();
            format = Base64.encode(format);
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
     * exists, the value is overriden.
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


/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/

var Base64 = {

    // private property
    _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

    // public method for encoding
    encode: function(input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        input = Base64._utf8_encode(input);

        while (i < input.length) {

            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
            this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
            this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

        }

        return output;
    },

    // public method for decoding
    decode: function(input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;

        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

        while (i < input.length) {

            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));

            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;

            output = output + String.fromCharCode(chr1);

            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }

        }

        output = Base64._utf8_decode(output);

        return output;

    },

    // private method for UTF-8 encoding
    _utf8_encode: function(string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            } else if ((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            } else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }

        return utftext;
    },

    // private method for UTF-8 decoding
    _utf8_decode: function(utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;

        while (i < utftext.length) {

            c = utftext.charCodeAt(i);

            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            } else if ((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i+1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            } else {
                c2 = utftext.charCodeAt(i+1);
                c3 = utftext.charCodeAt(i+2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }

        }

        return string;
    }

};