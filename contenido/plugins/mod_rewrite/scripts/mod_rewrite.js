/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Plugin Advanced Mod Rewrite JavaScript functions.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Plugins
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2011-04-11
 *
 *   $Id$:
 * }}
 *
 */


var mrPlugin = {
    lng: {
        more_informations: "More informations"
    },

    toggle: function(id) {
        // do some animation ;-)
        $('#' + id).slideToggle("slow");
    },

    showReadme: function() {
    },

    initializeSettingsPage: function() {
        $(document).ready(function() {
            $("#mr_use_language").change(function() {
                if (true == $(this).attr("checked")) {
                    $("#mr_use_language_name").removeAttr("disabled");
                } else {
                    $("#mr_use_language_name").attr("disabled", "disabled");
                }
            });

            $("#mr_use_client").change(function() {
                if (true == $(this).attr("checked")) {
                    $("#mr_use_client_name").removeAttr("disabled");
                } else {
                    $("#mr_use_client_name").attr("disabled", "disabled");
                }
            });

            $("#mr_add_startart_name_to_url").change(function() {
                if (true == $(this).attr("checked")) {
                    $("#mr_default_startart_name").removeAttr("disabled")
                                                  .removeClass("disabled");
                } else {
                    $("#mr_default_startart_name").attr("disabled", "disabled")
                                                  .addClass("disabled");
                }
            });

            mrPlugin._initializeTooltip();
        });
    },

    initializeExterpPage: function() {
        $(document).ready(function() {
            mrPlugin._initializeTooltip();
        });
    },

    _initializeTooltip: function() {
        $(".mrPlugin a.i-link").each(function() {
            $(this).attr("href", "javascript:void(0);");
            $(this).attr("title", mrPlugin.lng.more_informations);
            var id = $(this).attr("id").substring(0, $(this).attr("id").indexOf("-link"));
            $(this).aToolTip({
                clickIt:    true,
                xOffset:    -20,
                yOffset:    4,
                outSpeed:   250,
                tipContent: $("#" + id).html()
            });
        });
    }
};

