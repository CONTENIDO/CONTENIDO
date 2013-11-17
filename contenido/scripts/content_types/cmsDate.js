/* global Con: true, jQuery: true */

/**
 * This file contains the cContentTypeDate JS class.
 *
 * @module  content-type
 * @submodule  content-type-cms-date
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev$
 *
 * @author Simon Sprankel, Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
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
         *
         * @type String
         */
        this.frameId = frameId;

        /**
         * The prefix of this content type.
         *
         * @type String
         */
        this.prefix = prefix;

        /**
         * ID of the content type, e.g. 3 if CMS_TEASER[3] is used.
         *
         * @type Number
         */
        this.id = id;

        /**
         * IdArtLang of the article which is currently in edit- or viewmode.
         *
         * @type Number
         */
        this.idArtLang = idArtLang;

        /**
         * The HTTP path to the CONTENIDO backend.
         *
         * @type String
         */
        this.pathBackend = pathBackend;

        /**
         * The language which should be used.
         *
         * @type String
         */
        this.lang = lang;

        /**
         * The old settings.
         *
         * @type Object|String
         */
        this.settings = settings;

        /**
         * The backend language.
         *
         * @type String
         */
        this.belang = belang;

    }

    /**
     * Initialises the content type by adding event handlers etc.
     */
    cContentTypeDate.prototype.initialise = function(calendarPic) {
        this.loadExternalFiles(calendarPic);
        this.addSaveEvent();
    };

    /**
     * Loads external styles and scripts so that they are only loaded when they are
     * really needed.
     */
    cContentTypeDate.prototype.loadExternalFiles = function(calendarPic) {
        // Dependencies to load
        var files = [
            this.pathBackend + 'styles/content_types/cms_date.css', this.pathBackend + 'styles/jquery/jquery-ui.css',
            this.pathBackend + 'scripts/jquery/jquery-ui.js', this.pathBackend + 'scripts/jquery/plugins/timepicker.js'
        ];

        // only load the localisation file if the language is not english
        if (this.lang !== 'en') {
            files.push(this.pathBackend + 'scripts/jquery/plugins/datepicker-' + this.lang + '.js');
            files.push(this.pathBackend + 'scripts/jquery/plugins/timepicker-' + this.lang + '.js');
        }

        Con.Loader.get(files, cContentTypeDate.prototype.jQueryUiCallback, this, calendarPic]);
    };

    /**
     * Callback function which is executed when jQuery UI has successfully been
     * loaded. Loads the appropriate language.
     */
    cContentTypeDate.prototype.jQueryUiCallback = function(calendarPic) {
        this.jQueryUiTimepickerCallback(calendarPic);
    };

    /**
     * Callback function which is executed when jQuery UI has successfully been
     * loaded. Loads the appropriate language.
     */
    cContentTypeDate.prototype.jQueryUiTimepickerCallback = function(calendarPic) {
        var self = this;
        // initialise the datepicker
        $('#date_timestamp_' + self.id).datetimepicker({
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
            $('#date_timestamp_' + self.id).datetimepicker('setDate', date);
            // set the format
            var dateFormat = 'yy-mm-dd';
            var timeFormat = 'hh:mm:ssTT';
            if (self.belang == 'de_DE') {
                dateFormat = 'dd.mm.yy';
                timeFormat = 'hh:mm:ss';
            }
            $('#date_timestamp_' + self.id).datetimepicker('option', 'dateFormat', dateFormat);
            $('#date_timestamp_' + self.id).datetimepicker('option', 'timeFormat', timeFormat);
        });
        // only load the localisation file if the language is not english
        if (self.lang !== 'en') {
            conLoadFile(self.pathBackend + 'scripts/jquery/plugins/datepicker-' + self.lang + '.js');
            conLoadFile(self.pathBackend + 'scripts/jquery/plugins/timepicker-' + self.lang + '.js');
        }
    };

    /**
     * Adds save event to the save button of content type edit form.
     */
    cContentTypeDate.prototype.addSaveEvent = function() {
        var self = this;
        $(self.frameId + ' .save_settings').css('cursor', 'pointer');
        $(self.frameId + ' .save_settings').click(function() {
            var date = $('#date_timestamp_' + self.id).datetimepicker('getDate') || $('#date_timestamp_' + self.id).datepicker('getDate') || $('#date_timestamp_' + self.id).timepicker('getDate');
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
     * @param {String} name The name of the form field which should be added.
     * @param {String} value The value of the form field which should be added.
     */
    cContentTypeDate.prototype.appendFormField = function(name, value) {
        // if a hidden input field with the given name already exists, just set the value
        if ($('form[name="editcontent"] input[type="hidden"][name="' + name + '"]').length > 0) {
            $('form[name="editcontent"] input[type="hidden"][name="' + name + '"]').val(value);
        } else {
            // otherwise append a new field to the form
            $('form[name="editcontent"]').append('<input type="hidden" value="' + value + '" name="' + name + '"/>');
        }
    };


    Con.cContentTypeDate = cContentTypeDate;

    // @deprecated [2013-11-10] Assign to windows scope (downwards compatibility)
    window.cContentTypeDate = cContentTypeDate;

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