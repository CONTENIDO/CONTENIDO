
//This function adds new hidden element to the form and submits the form.
$(function() {

    $('[data-content-link-list-action]').live('click', function() {
        var $element = $(this),
            action = $element.data('content-link-list-action');
        if (action === 'create_link_fields') {
            var linkCount = parseInt($element.parent().find('input[name=link_count]').val(), 10),
                $form = $('form[name="editcontent"]');
            $form.append('<input type="hidden" value="' + linkCount + '" name="linkCount">');
            $form.submit();
        }
    });

});