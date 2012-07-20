/**
 * This file contains the cContentTypeDate JS class.
 *
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 *
 * @constructor
 * @property {String} frameId The ID of the frame in which the content type can be set up.
 * @property {String} prefix The prefix of the content type.
 * @property {Number} id The ID of the content type, e.g. 3 if CMS_TEASER[3] is used.
 * @property {Number} idArtLang The idArtLang of the article which is currently being edited.
 * @property {String} pathBackend The path to the CONTENIDO backend.
 * @property {String} lang The language which is used (de or en).
 * @property {Object|String} settings The settings of this content type.
 */
function cContentTypeDate(frameId, prefix, id, idArtLang, pathBackend, lang, settings) {

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
    if ($('#cms_date_styles').length === 0) {
        $('head').append('<link rel="stylesheet" id="cms_date_styles" href="' + this.pathBackend + 'styles/content_types/cms_date.css" type="text/css" media="all" />');
    }
    if ($('#jquery_ui_styles').length === 0) {
        $('head').append('<link rel="stylesheet" id="jquery_ui_styles" href="' + this.pathBackend + 'styles/smoothness/jquery-ui-1.8.20.custom.css" type="text/css" media="all" />');
    }
    conLoadFile(this.pathBackend + 'scripts/jquery/jquery-ui.js', cContentTypeDate.prototype.jQueryUiCallback, this, new Array(calendarPic));
};

/**
 * Callback function which is executed when jQuery UI has successfully been
 * loaded. Loads the appropriate language.
 */
cContentTypeDate.prototype.jQueryUiCallback = function(calendarPic) {
    conLoadFile(this.pathBackend + 'scripts/datetimepicker/jquery-ui-timepicker-addon.js', cContentTypeDate.prototype.jQueryUiTimepickerCallback, this, new Array(calendarPic));
};

/**
 * Callback function which is executed when jQuery UI has successfully been
 * loaded. Loads the appropriate language.
 */
cContentTypeDate.prototype.jQueryUiTimepickerCallback = function(calendarPic) {
    var self = this;
    // initialise the datepicker
    $('#date_timestamp_' + self.id).datetimepicker({
        alwaysSetTime: false,
        buttonImage: calendarPic,
        buttonImageOnly: true,
        showOn: 'both'
    });
    $(function() {
        // set the initial date
        var date = new Date();
        if (!isNaN(self.settings.date_timestamp)) {
            date = new Date(self.settings.date_timestamp * 1000);
        }
        $('#date_timestamp_' + self.id).datetimepicker('setDate', date);
        // set the initial format
        var format = $('#date_format_select_' + self.id).val();
        format = format.replace(/\\"/g, '\"');
        format = $.parseJSON(format);
        $('#date_timestamp_' + self.id).datetimepicker('option', 'dateFormat', format.dateFormat);
        $('#date_timestamp_' + self.id).datetimepicker('option', 'timeFormat', format.timeFormat);
        // change the format when a new format is selected
        $('#date_format_select_' + self.id).change(function() {
            var format = $(this).val();
            format = format.replace(/\\"/g, '\"');
            format = $.parseJSON(format);
            $('#date_timestamp_' + self.id).datetimepicker('option', 'dateFormat', format.dateFormat);
            $('#date_timestamp_' + self.id).datetimepicker('option', 'timeFormat', format.timeFormat);
        });
    });
    // only load the localisation file if the language is not english
    if (self.lang !== 'en') {
        conLoadFile(self.pathBackend + 'scripts/jquery/jquery.ui.datepicker-' + self.lang + '.js');
        conLoadFile(self.pathBackend + 'scripts/datetimepicker/jquery-ui-timepicker-' + self.lang + '.js');
    }
};

/**
 * Adds save event to the save button of content type edit form.
 */
cContentTypeDate.prototype.addSaveEvent = function() {
    var self = this;
    $(self.frameId + ' .save_settings').css('cursor', 'pointer');
    $(self.frameId + ' .save_settings').click(function() {
        var date = $('#date_timestamp_' + self.id).datetimepicker('getDate');
        var timestamp = date.getTime() / 1000;
        var format = $(self.frameId + ' #date_format_select_' + self.id).val();
        alert(date);
        alert(format);
        self.appendFormField(self.prefix + '_timestamp', timestamp);
        self.appendFormField(self.prefix + '_format', format);
        self.appendFormField(self.prefix + '_action', 'store');
        self.appendFormField(self.prefix + '_id', self.id);
        return;
        setcontent(self.idArtLang, '0');
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
        alert('<input type="hidden" value="' + value + '" name="' + name + '"/>');
        $('form[name="editcontent"]').append('<input type="hidden" value="' + value + '" name="' + name + '"/>');
    }
};
