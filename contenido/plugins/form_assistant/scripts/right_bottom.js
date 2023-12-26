
(function(Con, $) {

    /**
     * TODO add graphic for loading when executing action
     */
    $(function() {

        // Get reference to FormAssistant
        var formAssistant = Con.Plugin.FormAssistant;

        /**
         * list & form (dialog) as global jQuery-objects
         */
        var $pifaFormFieldList = $('#pifa-form-field-list');
        var $pifaFormFieldForm = $('#pifa-form-field-dialog');

        var $body = $('body');

        /**
         * Flag about already running Pifa form request.
         * The droppable event "drop" will be fired twice due to the usage of
         * sortable and droppable, and this flag prevents the double loading of the form fields.
         * @type {boolean}
         */
        var formRequestIsRunning = false;

        $('#pifa-form #name').focus();

        /**
         * If an edit button in the list of fields is clicked
         * then its form is requested via AJAX and displayed
         * as a dialog.
         */
        $body.delegate('.pifa-icon-edit-field', 'click', function(event) {
            event.preventDefault();
            var href = $(this).attr('href');
            // If no href is given user lacks rights to add field.
            if (0 === href.length) {
                return;
            }
            $.ajax({
                type: 'GET',
                url: href,
                success: function(data, textStatus, jqXHR) {
                    if (Con.checkAjaxResponse(data) === false)  {
                        return false;
                    }
                    $pifaFormFieldForm.html(data);
                    pifaShowFormFieldDialog($pifaFormFieldForm, null);

                    $pifaFormFieldForm.find('.pseudo-fieldset').find('#deselectCss').click(function() {
                        $('#css_class option:selected').removeAttr('selected');
                    });
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $(jqXHR.responseText).appendTo('body').dialog({
                        modal: true,
                        title: errorThrown,
                        buttons: [{
                            text: formAssistant.getTrans('cancel'),
                            click: function() {
                                $(this).dialog('close');
                            }
                        }]
                    });
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
        $body.delegate('.pifa-icon-delete-field', 'click', function(event) {
            event.preventDefault();
            if (false === confirm(formAssistant.getTrans('confirm_delete_field'))) {
                return;
            }
            var $li = $(this).parent().parent();
            var href = $(this).attr('href');
            // If no href is given user lacks rights to delete field.
            if (0 === href.length) {
                return;
            }
            $.ajax({
                type: 'GET',
                url: href,
                success: function(data, textStatus, jqXHR) {
                    if (Con.checkAjaxResponse(data) === false)  {
                        return false;
                    }

                    $li.hide('slide', function() {
                        $(this).remove();
                    }, 'fast');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $(jqXHR.responseText).appendTo('body').dialog({
                        modal: true,
                        title: errorThrown,
                        buttons: [{
                            text: formAssistant.getTrans('cancel'),
                            click: function() {
                                $(this).dialog('close');
                            }
                        }]
                    });
                }
            });
        });

        /**
         * Sortable PIFA form fields.
         * Further params to be send (area, frame, contenido, idform) are read
         * from a hidden input field #sortParams which is filled serverside.
         */
        if ($pifaFormFieldList.hasClass('sortable')) {
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
        }

        /**
         * Make field type icons draggable.
         */
        $('.img-draggable').draggable({
            connectToSortable: '#pifa-form-field-list',
            // make a copy of the dragged icon
            helper: 'clone',
            revert: 'invalid'
        })
        .disableSelection()
        .on('click', function(event) {
            // append to list when clicked
            event.preventDefault();
            var href = $(this).attr('href');
            // If no href is given user lacks rights to add field.
            if (0 === href.length) {
                return;
            }
            $.ajax({
                type: 'GET',
                url: 'main.php',
                data: href,
                success: function(data, textStatus, jqXHR) {
                    if (Con.checkAjaxResponse(data) === false)  {
                        return false;
                    }

                    $pifaFormFieldForm.html(data);
                    $('#field_rank', $pifaFormFieldForm).val($pifaFormFieldList.find('li').length + 1);
                    pifaShowFormFieldDialog($pifaFormFieldForm, null);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $(jqXHR.responseText).appendTo('body').dialog({
                        modal: true,
                        title: errorThrown,
                        buttons: [{
                            text: formAssistant.getTrans('cancel'),
                            click: function() {
                                $(this).dialog('close');
                            }
                        }]
                    });
                }
            });
        });

        /**
         * Make form field list droppable.
         */
        $pifaFormFieldList.droppable({
            accept: '.img-draggable', // accept only field type icons
            drop: function(event, ui) {
                // Prevent multiple form field requests in a row.
                if (formRequestIsRunning) {
                    return;
                }
                formRequestIsRunning = true;

                var href = $(ui.draggable).attr('href');
                // If no href is given user lacks rights to add field.
                if (0 === href.length) {
                    return;
                }

                // Determine the position of the dropped placeholder element
                var droppedPosition = pifaGetDroppedElementPosition($pifaFormFieldList);

                $.ajax({
                    type: 'GET',
                    url: 'main.php',
                    data: href,
                    success: function(data, textStatus, jqXHR) {
                        formRequestIsRunning = false;
                        $pifaFormFieldForm.html(data);
                        $('#field_rank', $pifaFormFieldForm).val(droppedPosition);
                        pifaShowFormFieldDialog($pifaFormFieldForm, ui.draggable);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        formRequestIsRunning = false;
                        $(jqXHR.responseText).appendTo('body').dialog({
                            modal: true,
                            title: errorThrown,
                            buttons: [{
                                text: formAssistant.getTrans('cancel'),
                                click: function() {
                                    $(this).dialog('close');
                                }
                            }]
                        });
                    }
                });
            }
        });

        /**
         * Returns the position of the dropped placeholder element.
         *
         * @param {jQuery} $pifaFormFieldList
         * @returns {number}
         */
        function pifaGetDroppedElementPosition($pifaFormFieldList) {
            var position = 0;

            $pifaFormFieldList.children().each(function (pos, element) {
                if ($(element).hasClass('ui-state-highlight')) {
                    position = pos + 1;
                    return false;
                }
            });

            return position > 0 ? position : $pifaFormFieldList.children().length + 1;
        }

        /**
         * Displays the PIFA form field dialog.
         *
         * This function is called when an edit icon of an existing form field is clicked
         * and when a field type icon is dragged into the list of form fields in order to
         * create a new form field. When creating a new form field the dragged icon is
         * passed as $draggedItem and will be removed just before dialog is closed. When
         * the dialog is called to edit an existing form field NULL is passed instead.
         *
         * After the dialog is opened its #label is focused.
         *
         * @see https://docs.jquery.com/UI/API/1.8/Dialog
         * @var $dialog to be displayed as dialog
         * @var $draggedItem to be removed
         */
        function pifaShowFormFieldDialog($dialog, $draggedItem) {
            var opt = {
                width: 520,
                height: 'auto',
                modal: true,
                resizable: true,
                open: function(event, ui) {
                    $pifaFormFieldForm = $(event.target);
                    // focus label
                    $pifaFormFieldForm.find('#label').focus();
                    $pifaFormFieldForm.find('#column_name').on('blur change keyup', function(e) {
                        pifaValidateFormFieldElement($(e.currentTarget));
                    });
                },
                close: function(event, ui) {
                    // remove dragged item
                    if (null !== $draggedItem) {
                        $draggedItem.remove();
                    }
                }
            };

            // form has no hidden action when user lacks rights to save form field
            // add buttons only if user has appropriate rights
            if (0 < $pifaFormFieldForm.find('#action').length) {
                opt.buttons= [{
                    text: ' ',
                    click: function() {
                        if (!pifaValidateFormFieldDialog($pifaFormFieldForm)) {
                            $(this).dialog('close').submit();
                        }
                    }
                }];
            }

            $dialog.dialog(opt);
        }

        /**
         * Validates a single form field element, checks if its value is empty,
         * and sets/removes proper css class.
         *
         * @param {jQuery} $field The form field to validate
         */
        function pifaValidateFormFieldElement($field) {
            if ($field.val().trim()) {
                $field.removeClass('pifa-form-field-error');
            } else {
                $field.addClass('pifa-form-field-error');
            }
        }

        /**
         * Validates the form field dialog, checks the values of some form elements,
         * and adds proper css class if they are empty.
         *
         * @param {jQuery} $form The for to check the elements.
         */
        function pifaValidateFormFieldDialog($form) {
            // column_name is mandatory
            var $element = $('#column_name', $pifaFormFieldForm),
                error = false;

            if ($element.length && !$element.val().trim()) {
                $element.addClass('pifa-form-field-error');
                error = true;
            }

            return error;
        }

        /**
         * Get new options row via Ajax and insert them at the end of list of options.
         */
        $body.delegate('#icon-add-option', 'click', function(event) {
            event.preventDefault();
            var href = $(this).attr('href');
            // If no href is given user lacks rights to add option.
            if (0 === href.length) {
                return;
            }
            var $optionsList = $('#options-list');
            $.ajax({
                type: 'GET',
                url: href,
                data: 'index=' + ($optionsList.children().length + 1),
                success: function(data, textStatus, jqXHR) {
                    if (Con.checkAjaxResponse(data) === false)  {
                        return false;
                    }
                    $optionsList.append(data);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $(jqXHR.responseText).appendTo('body').dialog({
                        modal: true,
                        title: errorThrown,
                        buttons: [{
                            text: formAssistant.getTrans('cancel'),
                            click: function() {
                                $(this).dialog('close');
                            }
                        }]
                    });
                }
            });
        });

        /**
         * Delete option row. In order for this action to take effect the form has to be saved!
         */
        $body.delegate('.del-option a', 'click', function(event) {
            event.preventDefault();
            $(this).parents('.option-outer').hide('slide', function() {
                $(this).remove();
            }, 'fast');

        });

        /**
         * Submit form via AJAX.
         * The response is the row for the edited form field to be shown in the list of form fields.
         */
        $pifaFormFieldForm.on('submit', function(event) {
            event.preventDefault();
            $.ajax({
                type: 'POST',
                url: 'main.php',
                data: $(this).serialize(),
                success: function(data, textStatus, jqXHR) {
                    if (Con.checkAjaxResponse(data) === false)  {
                        return false;
                    }
                    // get idfield & field_rank of current item and list of existing items
                    var idfield = parseInt($('#idfield').val(), 10);
                    var fieldRank = parseInt($('#field_rank').val(), 10);
                    var $items = $pifaFormFieldList.find('li');

                    if (!isNaN(idfield) && idfield !== 0) {
                        // Replace item when editing an existing field
                        $items.eq(fieldRank - 1).replaceWith(data);
                    } else if (0 < $items.length) {
                        if (fieldRank === 1) {
                            // Add to the first position
                            $pifaFormFieldList.prepend(data);
                        } else {
                            // Add by using the fieldRank (position)
                            $items.eq(fieldRank - 2).after(data);
                        }
                    } else {
                        // List is empty append new element
                        $pifaFormFieldList.append(data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $(jqXHR.responseText).appendTo('body').dialog({
                        modal: true,
                        title: errorThrown,
                        buttons: [{
                            text: formAssistant.getTrans('cancel'),
                            click: function() {
                                $(this).dialog('close');
                            }
                        }]
                    });
                }
            });
        });

        // On flip mark click
        $('a.invert_selection').click(function() {
            $('input.mark_data').each(function() {
                $(this).prop('checked', !$(this).prop('checked'));
            });
        });

        /**
         * Submit form via Ajax and delete form data.
         */
        $('#right_bottom img.delete').on('click', function(event) {
            event.preventDefault();
            var iddatas = [];
            if ($(event.target).data('action') === 'delete_form_data') {
                // Delete single form data
                var iddata = $(event.target).closest('tr').data('form-data-id');
                if (iddata) {
                    iddatas.push(iddata);
                }
            } else {
                // Delete selected (one or multiple)
                $('input.mark_data').each(function() {
                    if ($(this).prop('checked')) {
                        iddatas.push($(this).val());
                    }
                });
            }
            if (!iddatas.length){
                return;
            }

            var deleteUrl = $('input.deleteUrl').val();
            $.ajax({
                type: 'POST',
                url:  deleteUrl,
                data: 'iddatas=' + iddatas.join(','),
                success: function(msg) {
                    if (Con.checkAjaxResponse(msg) === false)  {
                        return false;
                    }
                    document.location.reload();
                }
            });
        });

    });

})(Con, Con.$);
