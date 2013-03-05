$(function() {

    /**
     * Open cookie note dialog when present.
     */
    $("#cookieNote").dialog({
        height : 300,
        width : 350,
        modal : true,
        closeOnEscape : false,
        open: function(event, ui) {
            // hide close icon in upper right corner
            $(this).closest('.ui-dialog').find('.ui-dialog-titlebar-close').hide();
        },
//        close: function(event, ui) {
//            // fade dialog out
//            $(this).fadeOut();
//        },
        buttons : [{
            text : $('#accept').val(),
            click : function() {
                window.location.href = $('#pageUrlAccept').val();
                $(this).dialog("close");
            }
        },
        {
            text : $('#decline').val(),
            click : function() {
                window.location.href = $('#pageUrlDeny').val();
                $(this).dialog("close");
            }
        }]
    });

});
