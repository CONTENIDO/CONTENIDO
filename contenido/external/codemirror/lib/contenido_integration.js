var cm_editor = new Array();
var cm_div = new Array();
var cm_fullscreen = new Array();

function toggleCodeMirrorFullscreenEditor(editorId) {
    if (!cm_div[editorId].hasClass('CodeMirror-fullscreen')) {
        cm_div[editorId].addClass('CodeMirror-fullscreen');
        cm_div[editorId].height('100%');
        cm_div[editorId].width('100%');
    } else {
        cm_div[editorId].removeClass('CodeMirror-fullscreen');
        cm_div[editorId].height(cm_fullscreen[editorId].height + 'px');
        cm_div[editorId].width(cm_fullscreen[editorId].width + 'px');
    }

    cm_editor[editorId].refresh();
}

function initCodeMirror(textAreaId, properties) {
    cm_editor[textAreaId] = CodeMirror.fromTextArea(document.getElementById(textAreaId), properties);
    cm_div[textAreaId] = $('div.cm-s-' + textAreaId + '.CodeMirror-scroll');
    cm_fullscreen[textAreaId] = { height: cm_div[textAreaId].height(), width: cm_div[textAreaId].width() }
    var codeWidth = $(cm_editor[textAreaId].getWrapperElement()).width();

    $('#' + textAreaId).next().resizable({
        resize: function() {
            cm_editor[textAreaId].setSize(codeWidth, $(this).height());
            cm_editor[textAreaId].refresh();
        }
    });
}