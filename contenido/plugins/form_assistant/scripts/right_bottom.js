
/**
 * TODO add graphic for loading when executing action
 */
$(function() {

    /**
     */
    function getTrans(key) {
        // get translations
        var value = pifaTranslations[key];
        // htmldecode value
        value = $('<div/>').html(value).text();
        return value;
    }

    /**
     * list & form (dialog) as global jQuery-objects
     */
    var $pifaFormFieldList = $('#pifa-form-field-list');
    var $pifaFormFieldForm = $('#pifa-form-field-dialog');

    $('#pifa-form #name').focus();

    /**
     * If an edit button in the list of fields is clicked
     * then its form is requested via AJAX and displayed
     * as a dialog.
     */
    $('body').delegate('.pifa-icon-edit-field', 'click', function(event) {
        event.preventDefault();
        $.ajax({
            type: 'GET',
            url: $(this).attr('href'),
            success: function(data, textStatus, jqXHR) {
                $pifaFormFieldForm.html(data);
                pifaShowFormFieldDialog($pifaFormFieldForm, null);
            }
        });
    });

    /**
     * If a delete button in the list of fields is clicked
     * then a dialog is displayed asking if the user really
     * wants to delete the field and, upon confirmation, an
     * Ajax request is send which deletes the field. Eventually
     * the field is removed via a hide animation.
     */
    $('body').delegate('.pifa-icon-delete-field', 'click', function(event) {
        event.preventDefault();
        if (false === confirm(getTrans('confirm_delete_field'))) {
            return;
        }
        var $li = $(this).parent().parent();
        var href = $(this).attr('href');
        $.ajax({
            type: 'GET',
            url: href,
            success: function(data, textStatus, jqXHR) {
                $li.hide('slide', function() {
                    $(this).remove();
                }, 'fast');
            },
            error: function(jqXHR, textStatus, errorThrown) {
                // TODO show error message
            }
        });
    });

    /**
     * Sortable PIFA form fields.
     * Further params to be send (area, frame, contenido, idform) are read
     * from a hidden input field #sortParams which is filled serverside.
     */
    $pifaFormFieldList.sortable({
        placeholder: 'ui-state-highlight',
        items: 'li:not(.header)',
        axis: 'y',
        //containment: 'parent',
        start: function(e, ui) {
            ui.placeholder.height(ui.item.height());
        },
        revert: true,
        update: function(event, ui) {
            var idfields = [];
            $.each($('li', this), function() {
                idfields.push($(this).attr('id'));
            });
            var sortParams = $('#sortParams').val();
            if (sortParams) {
                $.ajax({
                    type: 'POST',
                    url: 'main.php',
                    data: sortParams + '&idfields=' + idfields.join(',')
                });
            }
        }
    });

    /**
     * Make field type icons draggable.
     */
    $(".img-draggable").draggable({
        connectToSortable: '#pifa-form-field-list',
        // make a copy of the dragged icon
        helper: 'clone',
        revert: 'invalid'
    }).disableSelection().on('click', function(event) {
        // append to list when clicked
        event.preventDefault();
        $.ajax({
            type: 'GET',
            url: 'main.php',
            data: $(this).attr('href'),
            success: function(data, textStatus, jqXHR) {
                $pifaFormFieldForm.html(data);
                $("#field_rank", $pifaFormFieldForm).val($pifaFormFieldList.find('li').length + 1);
                pifaShowFormFieldDialog($pifaFormFieldForm, null);
            }
        });
    });

    /**
     * Make form field list droppable.
     */
    $pifaFormFieldList.droppable({
        accept: '.img-draggable', // accept only field type icons
        drop: function(event, ui) {
            $.ajax({
                type: 'GET',
                url: 'main.php',
                data: $(ui.draggable).attr('href'),
                success: function(data, textStatus, jqXHR) {
                    $pifaFormFieldForm.html(data);
                    $("#field_rank", $pifaFormFieldForm).val(ui.draggable.index() + 1);
                    pifaShowFormFieldDialog($pifaFormFieldForm, ui.draggable);
                }
            });
        }
    });

    /**
     * Displays the PIFA form field dialog.
     *
     * This function is called when an edit icon of an existing form field is clicked
     * and when a field type icon is dragged into the list of form fields in order to
     * create a new form field. When creating a new form field the dragged icon is
     * passed as $draggedItem and will be removed just before dialog is closed. When
     * the dialog is called to edit an exisiting form field NULL is passed instead.
     *
     * After the dialog is opened its #label is focused.
     *
     * @see http://docs.jquery.com/UI/API/1.8/Dialog
     * @var $dialog to be displayed as dialog
     * @var $draggedItem to be removed
     */
    function pifaShowFormFieldDialog($dialog, $draggedItem) {
        $dialog.dialog({
            width: 520,
            height: 'auto',
            modal: true,
            resizable: true,
            open: function(event, ui) {
                // focus label
                $('#label').focus();
            },
            close: function(event, ui) {
                // remove dragged item
                if (null !== $draggedItem) {
                    $draggedItem.remove();
                }
            },
            buttons: [
//            {
//                text: getTrans('cancel'),
//                click: function() {
//                    // close dialog
//                    $(this).dialog('close');
//                }
//            },
            {
                //text: getTrans('save'),
                text: ' ',
                click: function() {
                    $(this).dialog('close').submit();
                }
            }]
        });
    }

    /**
     * Get new options row via Ajax and insert them at the end of list of options.
     */
    $('body').delegate('#icon-add-option', 'click', function(event) {
        event.preventDefault();
        var $optionsList = $('#options-list');
        $.ajax({
            type: 'GET',
            url: $(this).attr('href'),
            data: 'index=' + ($optionsList.children().length + 1),
            success: function(data, textStatus, jqXHR) {
                $optionsList.append(data);
            }
        });
    });

    /**
     * Delete option row. In order for this action to take effect the form has to be saved!
     */
    $('body').delegate('.del-option a', 'click', function(event) {
        event.preventDefault();
        $(this).parents('.option-outer').hide('slide', function() {
            $(this).remove();
        }, 'fast');

    });

    /**
     * Submit form via AJAX.
     * The response is the row for the edited form field to be shown in the list of form fields.
     */
    $('#pifa-form-field-dialog').on('submit', function(event) {
        event.preventDefault();
        $.ajax({
            type: 'POST',
            url: 'main.php',
            data: $(this).serialize(),
            success: function(data, textStatus, jqXHR) {
                // get idfield & field_rank of current item and list of existing items
                var idfield = parseInt($('#idfield').val(), 10);
                var fieldRank = parseInt($('#field_rank').val(), 10);
                var $items = $pifaFormFieldList.find('li');
                // either replace item when editing existing field
                // or append new item to predecessor when creating new field and list is not empty
                // or append new item to list when creating new field and list is empty
                if (!isNaN(idfield)) {
                    $items.eq(fieldRank - 1).replaceWith(data);
                } else if (0 < $items.length) {
                    $items.eq(fieldRank - 2).after(data);
                } else {
                    $pifaFormFieldList.append(data);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $(jqXHR.responseText).appendTo('body').dialog({
                    modal: true,
                    title: errorThrown,
                    buttons: [{
                        text: getTrans('cancel'),
                        click: function() {
                            $(this).dialog('close');
                        }
                    }]
                });
            }
        });
    });

});
