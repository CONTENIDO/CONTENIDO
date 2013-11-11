/**
 * CONTENIDO CodeMirror integration helper module
 *
 * @package    TODO
 * @subpackage TODO
 * @version    SVN Revision $Rev:$
 * @requires   jQuery
 *
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
                height: _cmDiv[textAreaId].height(),
                width: _cmDiv[textAreaId].width()
            };
            var codeWidth = $(_cmEditor[textAreaId].getWrapperElement()).width();

            $textArea.next().resizable({
                resize: function() {
                    _cmEditor[textAreaId].setSize(codeWidth, $(this).height());
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

            // @TODO: Display the editor in real full screen mode!
            if (!_cmDiv[editorId].hasClass('con-CodeMirror-fullscreen')) {
                _cmDiv[editorId].addClass('con-CodeMirror-fullscreen');
                _cmDiv[editorId].height('100%');
                _cmDiv[editorId].width('100%');
            } else {
                _cmDiv[editorId].removeClass('con-CodeMirror-fullscreen');
                _cmDiv[editorId].height(_cmFullscreen[editorId].height + 'px');
                _cmDiv[editorId].width(_cmFullscreen[editorId].width + 'px');
            }

            _cmEditor[editorId].refresh();
        }

    };

    // @deprecated [2013-10-22] Assign to windows scope (downwards compatibility)
    window.initCodeMirror = Con.CodeMirrorHelper.init;
    window.toggleCodeMirrorFullscreenEditor = Con.CodeMirrorHelper.toggleFullscreenEditor;

})(Con, Con.$);
