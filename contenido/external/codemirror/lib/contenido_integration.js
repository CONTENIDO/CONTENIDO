/**
 * CONTENIDO CodeMirror integration helper module
 *
 * @package    TODO
 * @subpackage TODO
 * @requires   jQuery
 * @author     Unknown
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */


(function(Con, $) {
    "use strict";

    // Some variables to store editor elements
    var _cmEditor = [],
        _cmDiv = [],
        _cmFullscreen = [];

    /**
     * CodeMirror integration helper class
     * @class  CodeMirrorHelper
     * @namespace  Con
     * @static
     */
    Con.CodeMirrorHelper = {

        /**
         * Initializes an element as a CodeMirror editor.
         * @param  {String}  textAreaId
         * @param  {Object}  properties
         */
        init: function(textAreaId, properties) {
            var $textArea = $('#' + textAreaId);
            if (!$textArea[0]) {
                Con.log("init: Failed to get textarea with id " + textAreaId, "contenido_integration.js", "warn");
                return;
            }

            _cmEditor[textAreaId] = CodeMirror.fromTextArea($textArea[0], properties);
            _cmDiv[textAreaId] = $('div.cm-s-' + textAreaId + '.CodeMirror-scroll');
            _cmFullscreen[textAreaId] = {
                height: $(_cmEditor[textAreaId].getWrapperElement()).height(),
                width: $(_cmEditor[textAreaId].getWrapperElement()).width()
            };
            var codeWidth = $(_cmEditor[textAreaId].getWrapperElement()).width();

            $textArea.next().resizable({
                resize: function() {
                    var minWidth = _cmFullscreen[textAreaId].width;
                    var newWidth = ($(this).width() < minWidth) ? minWidth : $(this).width();
                    _cmEditor[textAreaId].setSize(newWidth, $(this).height());
                    _cmEditor[textAreaId].refresh();
                }
            });
        },

        /**
         * Toggles the fullscreen state of a specific editor element
         * @param  {String}  editorId
         */
        toggleFullscreenEditor: function(editorId) {
            if (!_cmDiv[editorId]) {
                Con.log("toggleFullscreenEditor: Missing editor element with id " + editorId, "contenido_integration.js", "warn");
                return;
            }

            var editorElement = $(_cmEditor[editorId].getWrapperElement());
            // @TODO: Display the editor in real full screen mode!
            // @TODO 1: Remove the line above
            // @TODO 2: Move styles to CSS, it's enough to set a CSS class attribute
            if (!_cmDiv[editorId].hasClass('con-CodeMirror-fullscreen')) {
                _cmDiv[editorId].addClass('con-CodeMirror-fullscreen');
                editorElement.css("position", "absolute");
                editorElement.css("top", "0");
                editorElement.css("left", "0");
                editorElement.css("width", "100%");
                editorElement.css("height", window.innerHeight + "px");
                editorElement.css("background-color", "white");
                editorElement.css("z-index", "3000");
                editorElement.resizable('destroy');

            } else {
                _cmDiv[editorId].removeClass('con-CodeMirror-fullscreen');
                editorElement.css("position", "");
                editorElement.css("top", "0");
                editorElement.css("left", "0");
                editorElement.css("width", _cmFullscreen[editorId].width + 'px');
                editorElement.css("height", _cmFullscreen[editorId].height + 'px');
                editorElement.css("background-color", "");
                editorElement.css("z-index", "0");
                editorElement.resizable({
                    resize: function() {
                        var minWidth = _cmFullscreen[editorId].width;
                        var newWidth = ($(this).width() < minWidth) ? minWidth : $(this).width();
                        _cmEditor[editorId].setSize(newWidth, $(this).height());
                        _cmEditor[editorId].refresh();
                    }
                });
            }

            _cmEditor[editorId].setSize(editorElement.width(), editorElement.height());
            _cmEditor[editorId].refresh();
        }

    };
})(Con, Con.$);
